<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\InvoiceServiceInterface;

class TestFiscalizationFix extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-fiscalization-fix';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the fiscalization fix with different customer ID approaches';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $service = app(InvoiceServiceInterface::class);
        
        $this->info('Testing fiscalization fix...');
        
        // Test getting existing customers
        $this->info('Getting existing customers...');
        $customersResult = $service->getFiscalizationCustomers();
        $this->info('Customers result: ' . json_encode($customersResult, JSON_PRETTY_PRINT));
        
        // Test creating a customer
        $this->info('Creating a test customer...');
        $customerResult = $service->createFiscalizationCustomer([
            'name' => 'Test Customer ' . time(),
            'tax_id' => 'SKA',
            'address' => 'Test Address',
            'phone' => '123456789',
            'email' => 'test@example.com',
        ]);
        $this->info('Customer creation result: ' . json_encode($customerResult, JSON_PRETTY_PRINT));
        
        // Test getting items
        $this->info('Getting existing items...');
        $itemsResult = $service->getFiscalizationItems();
        $this->info('Items result: ' . json_encode($itemsResult, JSON_PRETTY_PRINT));
        
        $this->info('Test completed.');
    }
} 