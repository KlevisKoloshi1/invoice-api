<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\InvoiceServiceInterface;

class TestFiscalization extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:fiscalization {invoice_id? : The ID of the invoice to fiscalize}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test fiscalization functionality with sample data or existing invoice';

    protected $invoiceService;

    public function __construct(InvoiceServiceInterface $invoiceService)
    {
        parent::__construct();
        $this->invoiceService = $invoiceService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $invoiceId = $this->argument('invoice_id');

        if ($invoiceId) {
            // Test with existing invoice
            $this->testExistingInvoice($invoiceId);
        } else {
            // Create test data and test
            $this->createTestDataAndFiscalize();
        }
    }

    private function testExistingInvoice($invoiceId)
    {
        $this->info("Testing fiscalization for invoice ID: {$invoiceId}");

        try {
            $invoice = Invoice::with(['client', 'items'])->findOrFail($invoiceId);
            
            $this->info("Invoice found:");
            $this->table(
                ['Field', 'Value'],
                [
                    ['ID', $invoice->id],
                    ['Number', $invoice->invoice_number],
                    ['Client', $invoice->client->name],
                    ['Total', $invoice->total_with_tax],
                    ['Items', $invoice->items->count()],
                    ['Fiscal Status', $invoice->fiscal_status ?? 'Not fiscalized']
                ]
            );

            if ($this->confirm('Do you want to fiscalize this invoice?')) {
                $this->performFiscalization($invoice);
            }
        } catch (\Exception $e) {
            $this->error("Invoice not found or error: " . $e->getMessage());
        }
    }

    private function createTestDataAndFiscalize()
    {
        $this->info("Creating test data for fiscalization...");

        // Create admin user if not exists
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Test Admin',
                'password' => bcrypt('password'),
                'role' => 'admin'
            ]
        );

        // Create test client
        $client = Client::firstOrCreate(
            ['email' => 'testclient@example.com'],
            [
                'name' => 'Test Client for Fiscalization',
                'tax_id' => '123456789',
                'address' => 'Test Address, Tirana',
                'phone' => '1234567890'
            ]
        );

        // Create test invoice
        $invoice = Invoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-TEST-' . time(),
            'invoice_date' => now()->format('Y-m-d'),
            'total_without_tax' => 100.00,
            'total_tax' => 20.00,
            'total_with_tax' => 120.00,
            'created_by' => $adminUser->id,
            'fiscal_status' => 'pending',
            'fiscal_response' => null
        ]);

        // Create invoice items
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Test Product 1',
            'quantity' => 2,
            'unit' => 'pcs',
            'price' => 50.00,
            'tax' => 10.00,
            'total' => 60.00
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Test Product 2',
            'quantity' => 1,
            'unit' => 'pcs',
            'price' => 50.00,
            'tax' => 10.00,
            'total' => 60.00
        ]);

        $this->info("Test invoice created with ID: {$invoice->id}");
        
        $this->table(
            ['Field', 'Value'],
            [
                ['Invoice ID', $invoice->id],
                ['Invoice Number', $invoice->invoice_number],
                ['Client', $client->name],
                ['Total', $invoice->total_with_tax],
                ['Items', $invoice->items->count()]
            ]
        );

        if ($this->confirm('Do you want to fiscalize this test invoice?')) {
            $this->performFiscalization($invoice);
        }
    }

    private function performFiscalization($invoice)
    {
        $this->info("Performing fiscalization...");

        try {
            // Set the authenticated user for the service
            auth()->login(User::find($invoice->created_by));

            $result = $this->invoiceService->fiscalizeInvoice($invoice->id);

            if ($result['status'] === 'success') {
                $this->info("✅ Fiscalization successful!");
                $this->info("QR Code URL: " . ($result['data']['body'][0]['qrcode_url'] ?? 'N/A'));
                $this->info("Fiscal Number: " . ($result['data']['body'][0]['fiscal_number'] ?? 'N/A'));
                $this->info("Verification URL: " . ($result['verification_url'] ?? 'N/A'));
                
                $this->table(
                    ['Field', 'Value'],
                    [
                        ['Status', 'Success'],
                        ['QR Code', $result['data']['body'][0]['qrcode_url'] ?? 'N/A'],
                        ['Fiscal Number', $result['data']['body'][0]['fiscal_number'] ?? 'N/A'],
                        ['Verification URL', $result['verification_url'] ?? 'N/A']
                    ]
                );
            } else {
                $this->error("❌ Fiscalization failed!");
                $this->error("Error: " . ($result['message'] ?? 'Unknown error'));
                
                if (isset($result['data'])) {
                    $this->error("API Response: " . json_encode($result['data'], JSON_PRETTY_PRINT));
                }
            }

            // Show updated invoice status
            $invoice->refresh();
            $this->info("Invoice fiscal status: " . $invoice->fiscal_status);

        } catch (\Exception $e) {
            $this->error("❌ Error during fiscalization: " . $e->getMessage());
        }
    }
} 