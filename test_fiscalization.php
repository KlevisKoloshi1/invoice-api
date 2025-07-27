<?php

/**
 * Standalone script to test fiscalization API
 * Run this script to test the fiscalization endpoint directly
 */

require_once 'vendor/autoload.php';

// Load environment variables
$envFile = '.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

// Configuration
$fiscalizationUrl = $_ENV['FISCALIZATION_API_URL'] ?? 'https://elif12.2rmlab.com/live/api/sales.php';
$serverConfig = $_ENV['FISCALIZATION_SERVER_CONFIG'] ?? '{
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
}';

// Test data
$testPayload = [
    'body' => [[
        'cmd' => 'insert',
        'sales_date' => date('Y-m-d'),
        'customer_name' => 'Test Customer',
        'exchange_rate' => 1,
        'city_id' => 1,
        'warehouse_id' => 1,
        'automatic_payment_method_id' => 0,
        'currency_id' => 1,
        'customer_id' => 1,
        'sales_document_serial' => '',
        'paid_amount' => 120.00,
        'customer_tax_id' => 'SKA',
        'cash_register_id' => 1,
        'fiscal_delay_reason_type' => null,
        'fiscal_invoice_type_id' => 4,
        'fiscal_profile_id' => 1,
        'details' => [
            [
                'item_name' => 'Test Product 1',
                'item_quantity' => 2.0,
                'item_unit_id' => 1,
                'item_price_without_tax' => 50.0,
                'item_total_without_tax' => 100.0,
                'item_total_tax' => 20.0,
                'item_total_with_tax' => 120.0,
                'tax_rate_id' => 2,
                'vat_level' => 20,
            ]
        ],
    ]],
    'IsEncrypted' => false,
    'ServerConfig' => $serverConfig,
    'App' => 'web',
    'Language' => 'sq-AL',
];

echo "🧪 Testing Fiscalization API\n";
echo "============================\n";
echo "URL: {$fiscalizationUrl}\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Make the API request
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $fiscalizationUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($testPayload),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json',
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false, // For testing only
]);

echo "📤 Sending request...\n";
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "📥 Response received\n";
echo "HTTP Code: {$httpCode}\n\n";

if ($error) {
    echo "❌ cURL Error: {$error}\n";
    exit(1);
}

if ($response === false) {
    echo "❌ No response received\n";
    exit(1);
}

// Parse and display response
$responseData = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "❌ Invalid JSON response:\n";
    echo $response . "\n";
    exit(1);
}

echo "📋 Response Data:\n";
echo json_encode($responseData, JSON_PRETTY_PRINT) . "\n\n";

// Check for success
if ($httpCode === 200) {
    if (isset($responseData['body'][0]['qrcode_url'])) {
        echo "✅ Fiscalization successful!\n";
        echo "QR Code URL: " . $responseData['body'][0]['qrcode_url'] . "\n";
        if (isset($responseData['body'][0]['fiscal_number'])) {
            echo "Fiscal Number: " . $responseData['body'][0]['fiscal_number'] . "\n";
        }
    } else {
        echo "⚠️  Response received but no QR code URL found\n";
        if (isset($responseData['error'])) {
            echo "Error: " . $responseData['error'] . "\n";
        }
    }
} else {
    echo "❌ API request failed with HTTP code: {$httpCode}\n";
    if (isset($responseData['error'])) {
        echo "Error: " . $responseData['error'] . "\n";
    }
}

echo "\n🔍 Request Payload (for debugging):\n";
echo json_encode($testPayload, JSON_PRETTY_PRINT) . "\n"; 