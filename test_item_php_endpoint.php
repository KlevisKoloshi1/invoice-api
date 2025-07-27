<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Test item.php Endpoint ===\n\n";

// Try to get items from item.php endpoint
echo "Step 1: Getting items from item.php...\n";

$itemPayload = [
    'body' => [[
        'cmd' => 'get',
        'page' => 0,
        'split_page' => 10,
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

echo "Item query payload:\n";
echo json_encode($itemPayload, JSON_PRETTY_PRINT) . "\n\n";

// Try both endpoints
$endpoints = [
    config('services.fiscalization.url'),
    'https://elif12.2rmlab.com/live/api/item.php'
];

foreach ($endpoints as $endpoint) {
    echo "Trying endpoint: $endpoint\n";
    
    $response = Http::withHeaders([
        'Content-Type' => 'application/json',
    ])->post($endpoint, $itemPayload);

    $data = $response->json();

    echo "Response:\n";
    echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";
    
    if ($response->successful() && isset($data['body']) && !empty($data['body'])) {
        echo "✓ Success! Found " . count($data['body']) . " items.\n";
        break;
    } else {
        echo "✗ Failed or no items found.\n\n";
    }
} 