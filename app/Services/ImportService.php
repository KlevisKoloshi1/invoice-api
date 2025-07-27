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
        // Validate and store file
        $path = $file->store('imports');
        $import = Import::create([
            'file_path' => $path,
            'status' => 'pending',
            'created_by' => $userId,
        ]);

        try {
            $rows = Excel::toArray([], storage_path('app/' . $path))[0];
            DB::beginTransaction();
            foreach ($rows as $index => $row) {
                // Skip header row
                if ($index === 0) continue;
                // Validate required fields
                if (empty($row[0]) || empty($row[1]) || empty($row[2])) {
                    throw new \Exception("Missing required fields at row " . ($index + 1));
                }
                // Example: [client_name, invoice_number, invoice_date, total_without_tax, total_tax, total_with_tax, item_description, item_quantity, item_unit, item_price, item_tax, item_total]
                $client = Client::firstOrCreate([
                    'name' => $row[0],
                ]);
                $invoice = Invoice::create([
                    'client_id' => $client->id,
                    'invoice_number' => $row[1],
                    'invoice_date' => $row[2],
                    'total_without_tax' => $row[3] ?? 0,
                    'total_tax' => $row[4] ?? 0,
                    'total_with_tax' => $row[5] ?? 0,
                    'created_by' => $userId,
                    'import_id' => $import->id,
                ]);
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $row[6] ?? '',
                    'quantity' => $row[7] ?? 1,
                    'unit' => $row[8] ?? '',
                    'price' => $row[9] ?? 0,
                    'tax' => $row[10] ?? 0,
                    'total' => $row[11] ?? 0,
                ]);
            }
            $import->status = 'completed';
            $import->save();
            DB::commit();
        } catch (\Exception $e) {
            $import->status = 'failed';
            $import->error_message = $e->getMessage();
            $import->save();
            DB::rollBack();
        }
        return $import;
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