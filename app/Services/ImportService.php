<?php

namespace App\Services;

use App\Models\Import;
use Illuminate\Http\UploadedFile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use App\Services\ImportServiceInterface;
use App\Services\InvoiceServiceInterface;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Client;
use Illuminate\Support\Facades\DB;

class ImportService implements ImportServiceInterface
{
    protected $invoiceService;

    public function __construct(InvoiceServiceInterface $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    public function importFromExcel(UploadedFile $file, int $userId): Import
    {
        // Validate file type
        if (!in_array($file->getClientOriginalExtension(), ['xlsx', 'xls'])) {
            throw new \Exception("Invalid file type. Only .xlsx and .xls files are allowed.");
        }

        // Store file using Laravel's storage system
        $path = $file->store('imports', 'local');
        $import = Import::create([
            'file_path' => $path,
            'status' => 'pending',
            'created_by' => $userId,
        ]);

        try {
            // Get the full path using Laravel's storage system
            $fullPath = Storage::disk('local')->path($path);
            
            // Check if file exists before processing
            if (!file_exists($fullPath)) {
                throw new \Exception("File [{$fullPath}] does not exist and can therefore not be imported. Please check file permissions and storage configuration.");
            }
            
            // Log the file path for debugging
            \Log::info("Processing Excel file: {$fullPath}");
            
            $rows = Excel::toArray([], $fullPath)[0];
            
            if (empty($rows) || count($rows) < 2) {
                throw new \Exception("Excel file is empty or contains no data rows.");
            }
            
            // Validate headers
            $expectedHeaders = [
                'client_name', 'invoice_number', 'invoice_date', 'total_without_tax', 
                'total_tax', 'total_with_tax', 'item_description', 'item_quantity', 
                'item_unit', 'item_price', 'item_tax', 'item_total'
            ];
            
            $headers = array_map('strtolower', array_map('trim', $rows[0]));
            if (count($headers) < 12) {
                throw new \Exception("Invalid Excel format. Expected 12 columns, found " . count($headers));
            }
            
            DB::beginTransaction();
            $processedCount = 0;
            $errors = [];
            
            foreach ($rows as $index => $row) {
                // Skip header row
                if ($index === 0) continue;
                
                try {
                    // Validate required fields
                    if (empty($row[0]) || empty($row[1]) || empty($row[2])) {
                        throw new \Exception("Missing required fields at row " . ($index + 1));
                    }
                    
                    // Validate invoice number uniqueness
                    if (Invoice::where('invoice_number', $row[1])->exists()) {
                        throw new \Exception("Invoice number '{$row[1]}' already exists at row " . ($index + 1));
                    }
                    
                    // Convert Excel date to proper format
                    $invoiceDate = $this->convertExcelDate($row[2]);
                    
                    // Validate date format
                    if (!$this->isValidDate($invoiceDate)) {
                        throw new \Exception("Invalid date format at row " . ($index + 1) . ": {$row[2]}");
                    }
                    
                    // Validate numeric fields
                    $numericFields = [3, 4, 5, 7, 9, 10, 11]; // total_without_tax, total_tax, total_with_tax, item_quantity, item_price, item_tax, item_total
                    foreach ($numericFields as $fieldIndex) {
                        if (isset($row[$fieldIndex]) && !is_numeric($row[$fieldIndex])) {
                            throw new \Exception("Invalid numeric value at row " . ($index + 1) . ", column " . ($fieldIndex + 1));
                        }
                    }
                    
                    // Example: [client_name, invoice_number, invoice_date, total_without_tax, total_tax, total_with_tax, item_description, item_quantity, item_unit, item_price, item_tax, item_total]
                    $client = Client::firstOrCreate([
                        'name' => trim($row[0]),
                    ]);
                    
                    $invoice = Invoice::create([
                        'client_id' => $client->id,
                        'invoice_number' => trim($row[1]),
                        'invoice_date' => $invoiceDate,
                        'total_without_tax' => (float)($row[3] ?? 0),
                        'total_tax' => (float)($row[4] ?? 0),
                        'total_with_tax' => (float)($row[5] ?? 0),
                        'created_by' => $userId,
                        'import_id' => $import->id,
                    ]);
                    
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'description' => trim($row[6] ?? ''),
                        'quantity' => (int)($row[7] ?? 1),
                        'unit' => trim($row[8] ?? ''),
                        'price' => (float)($row[9] ?? 0),
                        'tax' => (float)($row[10] ?? 0),
                        'total' => (float)($row[11] ?? 0),
                    ]);
                    
                    $processedCount++;
                    
                } catch (\Exception $rowError) {
                    $errors[] = "Row " . ($index + 1) . ": " . $rowError->getMessage();
                }
            }
            
            // If there are errors, throw exception with all errors
            if (!empty($errors)) {
                throw new \Exception("Import completed with errors:\n" . implode("\n", $errors));
            }
            
            $import->status = 'completed';
            $import->save();
            DB::commit();
            
            \Log::info("Import completed successfully. Import ID: {$import->id}, Processed: {$processedCount} invoices");
            
        } catch (\Exception $e) {
            $import->status = 'failed';
            $import->error_message = $e->getMessage();
            $import->save();
            DB::rollBack();
            
            \Log::error("Import failed. Import ID: {$import->id}, Error: " . $e->getMessage());
        }
        return $import;
    }

    /**
     * Convert Excel date serial number to proper date format
     */
    private function convertExcelDate($excelDate)
    {
        // If it's already a string date with time, return as is
        if (is_string($excelDate) && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $excelDate)) {
            return $excelDate;
        }
        
        // If it's already a string date without time, add current time
        if (is_string($excelDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $excelDate)) {
            return $excelDate . ' ' . date('H:i:s');
        }
        
        // If it's a numeric Excel date
        if (is_numeric($excelDate)) {
            // Excel dates are days since January 1, 1900
            // Convert to Unix timestamp (seconds since January 1, 1970)
            $unixTimestamp = ($excelDate - 25569) * 86400;
            
            // Format as Y-m-d H:i:s (preserve time)
            return date('Y-m-d H:i:s', $unixTimestamp);
        }
        
        // If it's already a Carbon instance or DateTime
        if ($excelDate instanceof \Carbon\Carbon || $excelDate instanceof \DateTime) {
            return $excelDate->format('Y-m-d H:i:s');
        }
        
        // Default fallback - use current date and time
        return date('Y-m-d H:i:s');
    }

    /**
     * Validate if a date string is in correct format
     */
    private function isValidDate($dateString)
    {
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', $dateString);
        return $date && $date->format('Y-m-d H:i:s') === $dateString;
    }

    public function fiscalizeImport(int $importId): array
    {
        $import = Import::with('invoices')->findOrFail($importId);
        
        if ($import->status !== 'completed') {
            return [
                'status' => 'error',
                'message' => 'Import must be completed before fiscalization',
                'import_id' => $importId
            ];
        }

        $results = [
            'import_id' => $importId,
            'total_invoices' => $import->invoices->count(),
            'successful' => 0,
            'failed' => 0,
            'results' => []
        ];

        foreach ($import->invoices as $invoice) {
            try {
                $fiscalResult = $this->invoiceService->fiscalizeInvoice($invoice->id);
                $results['results'][] = [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'status' => $fiscalResult['status'],
                    'data' => $fiscalResult['data'] ?? null,
                    'verification_url' => $fiscalResult['verification_url'] ?? null
                ];

                if ($fiscalResult['status'] === 'success') {
                    $results['successful']++;
                } else {
                    $results['failed']++;
                }
            } catch (\Exception $e) {
                $results['results'][] = [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'status' => 'error',
                    'error' => $e->getMessage()
                ];
                $results['failed']++;
            }
        }

        $results['overall_status'] = $results['failed'] === 0 ? 'success' : 'partial';
        
        return $results;
    }

    public function updateImport(int $id, array $data): Import
    {
        $import = Import::findOrFail($id);
        $import->update($data);
        return $import;
    }

    public function deleteImport(int $id): bool
    {
        $import = Import::findOrFail($id);
        return $import->delete();
    }

    public function listImports(int $perPage = 15): LengthAwarePaginator
    {
        return Import::orderByDesc('created_at')->paginate($perPage);
    }
} 