<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Test Item Creation and Fiscalization ===\n\n";

// Step 1: Create an item in the fiscalization system
echo "Step 1: Creating item in fiscalization system...\n";

$itemPayload = [
    'body' => [[
        'cmd' => 'insert',
        'item_code' => 'TEST001',
        'item_name' => 'Test Service',
        'item_type_id' => 1,
        'item_unit_id' => 21,
        'tax_rate_id' => 2,
        'item_price_without_tax' => 100,
        'item_price_with_tax' => 120,
        'item_sales_tax_percentage' => 20,
        'is_active' => true,
    ]],
    'IsEncrypted' => false,
    'ServerConfig' => config('services.fiscalization.server_config'),
    'App' => 'web',
    'Language' => 'sq-AL',
];

echo "Item creation payload:\n";
echo json_encode($itemPayload, JSON_PRETTY_PRINT) . "\n\n";

$response = Http::withHeaders([
    'Content-Type' => 'application/json',
])->post(config('services.fiscalization.url'), $itemPayload);

$data = $response->json();

echo "Item creation response:\n";
echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

if ($response->successful()) {
    echo "✓ Item created successfully!\n\n";
    
    // Step 2: Now try to fiscalize an invoice using this item
    echo "Step 2: Testing fiscalization with the created item...\n";
    
    // Use the InvoiceService to fiscalize
    $invoiceService = app('App\Services\InvoiceService');
    $result = $invoiceService->fiscalizeInvoice(2);
    
    echo "Fiscalization result:\n";
    echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
    
} else {
    echo "✗ Item creation failed!\n";
    echo "Error: " . ($data['status']['message'] ?? 'Unknown error') . "\n\n";
} 