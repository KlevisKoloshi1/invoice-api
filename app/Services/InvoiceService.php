<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Services\InvoiceServiceInterface;
use Illuminate\Support\Facades\Http;

class InvoiceService implements InvoiceServiceInterface
{
    public function createInvoice(array $data): Invoice
    {
        // Set created_by to the authenticated user's ID
        $data['created_by'] = auth()->id();
        
        $invoice = Invoice::create($data);
        // Optionally create items if provided
        if (!empty($data['items'])) {
            foreach ($data['items'] as $item) {
                $invoice->items()->create($item);
            }
        }
        return $invoice;
    }

    public function updateInvoice(int $id, array $data): Invoice
    {
        $invoice = Invoice::findOrFail($id);
        $invoice->update($data);
        // Optionally update items
        return $invoice;
    }

    public function deleteInvoice(int $id): bool
    {
        $invoice = Invoice::findOrFail($id);
        return $invoice->delete();
    }

    public function listInvoices(int $perPage = 15): LengthAwarePaginator
    {
        return Invoice::orderByDesc('created_at')->paginate($perPage);
    }

    public function fiscalizeInvoice(int $id): array
    {
        $invoice = Invoice::with(['client', 'items'])->findOrFail($id);
        
        try {
            // Try to create the customer in the fiscalization system first
            $customerResult = $this->createFiscalizationCustomer([
                'name' => $invoice->client->name,
                'tax_id' => $invoice->client->tax_id ?? 'SKA',
                'address' => $invoice->client->address,
                'phone' => $invoice->client->phone,
                'email' => $invoice->client->email,
            ]);
            
            // Use a default customer ID if customer creation fails
            // Try different approaches: null, 1, or omit the field entirely
            $customerId = null; // Try null first
            
            if ($customerResult['status'] === 'success' && isset($customerResult['data']['body'][0]['customer_id'])) {
                $customerId = $customerResult['data']['body'][0]['customer_id'];
            }
            
            // If customer creation failed, try to get existing customers to find a valid ID
            if ($customerId === null) {
                $customersResult = $this->getFiscalizationCustomers();
                if ($customersResult['status'] === 'success' && 
                    isset($customersResult['data']['body']) && 
                    !empty($customersResult['data']['body'])) {
                    // Use the first available customer ID
                    $customerId = $customersResult['data']['body'][0]['customer_id'] ?? 1;
                }
            }
            
            // Prepare data for fiscalization API
            $payload = [
                'body' => [[
                    'cmd' => 'insert',
                    'sales_date' => $invoice->invoice_date,
                    'customer_name' => $invoice->client->name,
                    'exchange_rate' => 1,
                    'city_id' => 1,
                    'warehouse_id' => 1,
                    'automatic_payment_method_id' => -1, // From successful response: automatic_payment_method_id: -1
                    'currency_id' => 1,
                    'customer_id' => $customerId,
                    'sales_document_serial' => '',
                    'paid_amount' => null, // From successful response: paid_amount: null
                    'customer_tax_id' => $invoice->client->tax_id ?? 'SKA',
                    'cash_register_id' => null, // From successful response: cash_register_id: null
                    'fiscal_delay_reason_type' => null,
                    'fiscal_invoice_type_id' => 7, // From successful response: fiscal_invoice_type_id: 7
                    'fiscal_profile_id' => 1,
                    'details' => $invoice->items->map(function($item) {
                        return [
                            'item_name' => $item->description,
                            'item_quantity' => (float) $item->quantity,
                            'item_unit_id' => 21, // From successful response: item_unit_id: 21
                            'item_type_id' => 1, // From successful response: item_type_id: 1
                            'item_price_without_tax' => (float) $item->price,
                            'item_price_with_tax' => (float) $item->total,
                            'item_sales_tax_percentage' => 20, // Standard Albanian VAT rate
                            'item_total_without_tax' => (float) ($item->price * $item->quantity),
                            'item_total_tax' => (float) $item->tax,
                            'item_total_with_tax' => (float) $item->total,
                            'tax_rate_id' => 2, // From successful response: tax_rate_id: 2
                            'item_code' => 'art1', // From successful response: item_code: "art1"
                            'item_id' => 3, // From successful response: item_id: 3
                        ];
                    })->toArray(),
                ]],
                'IsEncrypted' => false,
                'ServerConfig' => config('services.fiscalization.server_config'),
                'App' => 'web',
                'Language' => 'sq-AL',
            ];
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post(config('services.fiscalization.url'), $payload);
            $data = $response->json();
            if ($response->successful() && isset($data['body'][0]['qrcode_url'])) {
                // Generate Albanian tax authority verification URL
                $verificationUrl = $this->generateVerificationUrl($invoice, $data);
                
                // Add verification URL to response
                $data['verification_url'] = $verificationUrl;
                
                $invoice->fiscal_status = 'sent';
                $invoice->fiscal_response = $data;
                $invoice->save();
                return ['status' => 'success', 'data' => $data, 'verification_url' => $verificationUrl];
            } else {
                $invoice->fiscal_status = 'error';
                $invoice->fiscal_response = $data;
                $invoice->save();
                return ['status' => 'error', 'data' => $data];
            }
        } catch (\Exception $e) {
            $invoice->fiscal_status = 'error';
            $invoice->fiscal_response = ['error' => $e->getMessage()];
            $invoice->save();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Create an item in the fiscalization system
     */
    public function createFiscalizationItem(array $itemData): array
    {
        try {
            $payload = [
                'body' => [[
                    'cmd' => 'insert',
                    'item_code' => $itemData['item_code'],
                    'item_name' => $itemData['item_name'],
                    'item_type_id' => 1, // Standard goods - confirmed to work
                    'item_unit_id' => 21, // From successful response
                    'tax_rate_id' => 2, // 20% VAT rate
                    'item_price_without_tax' => $itemData['price'] ?? 0,
                    'item_price_with_tax' => ($itemData['price'] ?? 0) * 1.2, // 20% VAT
                    'item_sales_tax_percentage' => 20,
                    'is_active' => true,
                ]],
                'IsEncrypted' => false,
                'ServerConfig' => config('services.fiscalization.server_config'),
                'App' => 'web',
                'Language' => 'sq-AL',
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post(config('services.fiscalization.url'), $payload);

            $data = $response->json();
            
            if ($response->successful()) {
                return ['status' => 'success', 'data' => $data];
            } else {
                return ['status' => 'error', 'data' => $data];
            }
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Get existing items from the fiscalization system
     */
    public function getFiscalizationItems(): array
    {
        try {
            $payload = [
                'body' => [[
                    'cmd' => 'get',
                    'page' => 0,
                    'split_page' => 50,
                    'vsort' => [
                        ['PropertyName' => 'row_insert_date_time', 'Direction' => 1]
                    ],
                    'vcols' => ['item_code', 'item_name', 'item_id', 'item_type_id', 'item_unit_id', 'tax_rate_id'],
                    'vsearch' => null
                ]],
                'IsEncrypted' => false,
                'ServerConfig' => config('services.fiscalization.server_config'),
                'App' => 'web',
                'Language' => 'sq-AL',
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post(config('services.fiscalization.url'), $payload);

            $data = $response->json();
            
            if ($response->successful()) {
                return ['status' => 'success', 'data' => $data];
            } else {
                return ['status' => 'error', 'data' => $data];
            }
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Create a customer in the fiscalization system
     */
    public function createFiscalizationCustomer(array $customerData): array
    {
        try {
            $payload = [
                'body' => [[
                    'cmd' => 'insert',
                    'customer_name' => $customerData['name'],
                    'customer_tax_id' => $customerData['tax_id'] ?? 'SKA',
                    'customer_address' => $customerData['address'] ?? '',
                    'customer_phone' => $customerData['phone'] ?? '',
                    'customer_email' => $customerData['email'] ?? '',
                    'is_active' => true,
                ]],
                'IsEncrypted' => false,
                'ServerConfig' => config('services.fiscalization.server_config'),
                'App' => 'web',
                'Language' => 'sq-AL',
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post(config('services.fiscalization.url'), $payload);

            $data = $response->json();
            
            if ($response->successful()) {
                return ['status' => 'success', 'data' => $data];
            } else {
                return ['status' => 'error', 'data' => $data];
            }
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Get existing customers from the fiscalization system
     */
    public function getFiscalizationCustomers(): array
    {
        try {
            $payload = [
                'body' => [[
                    'cmd' => 'get',
                    'page' => 0,
                    'split_page' => 50,
                    'vsort' => [
                        ['PropertyName' => 'row_insert_date_time', 'Direction' => 1]
                    ],
                    'vcols' => ['customer_id', 'customer_name', 'customer_tax_id'],
                    'vsearch' => null
                ]],
                'IsEncrypted' => false,
                'ServerConfig' => config('services.fiscalization.server_config'),
                'App' => 'web',
                'Language' => 'sq-AL',
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post(config('services.fiscalization.url'), $payload);

            $data = $response->json();
            
            if ($response->successful()) {
                return ['status' => 'success', 'data' => $data];
            } else {
                return ['status' => 'error', 'data' => $data];
            }
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Generate Albanian tax authority verification URL
     */
    private function generateVerificationUrl(Invoice $invoice, array $fiscalResponse): string
    {
        // If the API already provides a qrcode_url, use it
        if (isset($fiscalResponse['body'][0]['qrcode_url'])) {
            return $fiscalResponse['body'][0]['qrcode_url'];
        }
        
        // Base URL for Albanian tax authority verification
        $baseUrl = config('services.fiscalization.verification_base_url', 'https://eFiskalizimi-app-test.tatime.gov.al/invoice-check/#/verify');
        
        // Extract fiscal number from response (IIC - Invoice Identification Code)
        $iic = $fiscalResponse['body'][0]['iic'] ?? $this->generateFiscalNumber($invoice);
        
        // Get tax identification number from client or use default
        $tin = $invoice->client->tax_id ?? 'L81310069K';
        
        // Format created date in ISO 8601 format with timezone
        $crtd = $invoice->invoice_date . 'T' . now()->format('H:i:s') . '+02:00';
        
        // Generate order number (invoice ID)
        $ord = $invoice->id;
        
        // Business unit identifier (from config or default)
        $bu = config('services.fiscalization.business_unit', 'li519qp911');
        
        // Cash register identifier (from config or default)
        $cr = config('services.fiscalization.cash_register', 'ny172di313');
        
        // Software identifier (from config or default)
        $sw = config('services.fiscalization.software', 'dx582kn875');
        
        // Price (total amount with tax)
        $prc = number_format($invoice->total_with_tax, 2, '.', '');
        
        // Build query parameters
        $params = [
            'iic' => $iic,
            'tin' => $tin,
            'crtd' => $crtd,
            'ord' => $ord,
            'bu' => $bu,
            'cr' => $cr,
            'sw' => $sw,
            'prc' => $prc
        ];
        
        return $baseUrl . '?' . http_build_query($params);
    }

    /**
     * Generate a fiscal number if not provided by the API
     */
    private function generateFiscalNumber(Invoice $invoice): string
    {
        // Generate a unique fiscal number based on invoice data
        $timestamp = now()->format('YmdHis');
        $invoiceId = str_pad($invoice->id, 6, '0', STR_PAD_LEFT);
        $hash = substr(md5($invoice->invoice_number . $timestamp), 0, 8);
        
        return strtoupper($hash . $invoiceId);
    }
} 