<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Test customer creation in fiscalization system
function testCustomerCreation() {
    $payload = [
        'body' => [[
            'cmd' => 'insert',
            'customer_name' => 'Test Customer',
            'customer_tax_id' => 'SKA',
            'customer_address' => 'Test Address',
            'customer_phone' => '123456789',
            'customer_email' => 'test@example.com',
            'is_active' => true,
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
    
    echo "Response Status: " . $response->status() . "\n";
    echo "Response Data: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    
    return $data;
}

// Test getting existing customers
function testGetCustomers() {
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
    
    echo "Get Customers Response Status: " . $response->status() . "\n";
    echo "Get Customers Response Data: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    
    return $data;
}

echo "Testing Customer Creation...\n";
testCustomerCreation();

echo "\nTesting Get Customers...\n";
testGetCustomers(); 