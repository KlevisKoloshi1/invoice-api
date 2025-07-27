<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Test different customer_id values for fiscalization
function testFiscalizationWithCustomerId($customerId) {
    $payload = [
        'body' => [[
            'cmd' => 'insert',
            'sales_date' => '2024-07-27',
            'customer_name' => 'Test Customer',
            'exchange_rate' => 1,
            'city_id' => 1,
            'warehouse_id' => 1,
            'automatic_payment_method_id' => -1,
            'currency_id' => 1,
            'customer_id' => $customerId,
            'sales_document_serial' => '',
            'paid_amount' => null,
            'customer_tax_id' => 'SKA',
            'cash_register_id' => null,
            'fiscal_delay_reason_type' => null,
            'fiscal_invoice_type_id' => 7,
            'fiscal_profile_id' => 1,
            'details' => [[
                'item_name' => 'Test Item',
                'item_quantity' => 1.0,
                'item_unit_id' => 1,
                'item_type_id' => 1,
                'item_price_without_tax' => 100.0,
                'item_price_with_tax' => 120.0,
                'item_sales_tax_percentage' => 20,
                'item_total_without_tax' => 100.0,
                'item_total_tax' => 20.0,
                'item_total_with_tax' => 120.0,
                'tax_rate_id' => 1,
                'item_code' => 'art1',
                'item_id' => 3,
            ]],
        ]],
        'IsEncrypted' => false,
        'ServerConfig' => '{
            "Url_API": "https://elif12.2rmlab.com/live/api",
            "DB_Config": "elif_config",
            "Company_DB_Name": "Elif_001_1202260_07-2024",
            "HardwareId": "cfe8a423409129b0c36b418c71385eec",
            "UserInfo": {
                "user_id": 8001950,
                "username": "fiscaluser",
                "password": null,
                "token": "6c3b1ef34f5e69711e3d52bc8a78ef4811039acdb383a84b8a6d17757e904734a318006cb0069ada6f132400be18701493e487ced6b7f6dfdb1da61a0e21f929"
            }
        }',
        'App' => 'web',
        'Language' => 'sq-AL',
    ];

    $response = Http::withHeaders([
        'Content-Type' => 'application/json',
    ])->post('https://elif12.2rmlab.com/live/api/sales.php', $payload);

    $data = $response->json();
    
    echo "Testing with customer_id: " . ($customerId === null ? 'null' : $customerId) . "\n";
    echo "Response Status: " . $response->status() . "\n";
    echo "Response Data: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    echo "---\n";
    
    return $data;
}

// Test getting existing customers
function getExistingCustomers() {
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
        'ServerConfig' => '{
            "Url_API": "https://elif12.2rmlab.com/live/api",
            "DB_Config": "elif_config",
            "Company_DB_Name": "Elif_001_1202260_07-2024",
            "HardwareId": "cfe8a423409129b0c36b418c71385eec",
            "UserInfo": {
                "user_id": 8001950,
                "username": "fiscaluser",
                "password": null,
                "token": "6c3b1ef34f5e69711e3d52bc8a78ef4811039acdb383a84b8a6d17757e904734a318006cb0069ada6f132400be18701493e487ced6b7f6dfdb1da61a0e21f929"
            }
        }',
        'App' => 'web',
        'Language' => 'sq-AL',
    ];

    $response = Http::withHeaders([
        'Content-Type' => 'application/json',
    ])->post('https://elif12.2rmlab.com/live/api/sales.php', $payload);

    $data = $response->json();
    
    echo "Getting existing customers...\n";
    echo "Response Status: " . $response->status() . "\n";
    echo "Response Data: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    echo "---\n";
    
    return $data;
}

echo "=== Testing Fiscalization with Different Customer IDs ===\n\n";

// First, get existing customers
$customers = getExistingCustomers();

// Test with null customer_id
testFiscalizationWithCustomerId(null);

// Test with customer_id = 1
testFiscalizationWithCustomerId(1);

// Test with customer_id = 0
testFiscalizationWithCustomerId(0);

// If we have existing customers, test with the first one
if (isset($customers['body']) && !empty($customers['body'])) {
    $firstCustomerId = $customers['body'][0]['customer_id'] ?? null;
    if ($firstCustomerId !== null) {
        testFiscalizationWithCustomerId($firstCustomerId);
    }
} 