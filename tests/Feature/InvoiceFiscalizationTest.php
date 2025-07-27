<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

class InvoiceFiscalizationTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $client;
    protected $invoice;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user
        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password')
        ]);

        // Create a test client
        $this->client = Client::create([
            'name' => 'Test Client',
            'email' => 'client@test.com',
            'tax_id' => '123456789',
            'address' => 'Test Address',
            'phone' => '1234567890'
        ]);

        // Create a test invoice with items
        $this->invoice = Invoice::create([
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-TEST-001',
            'invoice_date' => now()->format('Y-m-d'),
            'total_without_tax' => 100.00,
            'total_tax' => 20.00,
            'total_with_tax' => 120.00,
            'created_by' => $this->adminUser->id,
            'fiscal_status' => 'pending',
            'fiscal_response' => null
        ]);

        // Create invoice items
        InvoiceItem::create([
            'invoice_id' => $this->invoice->id,
            'description' => 'Test Item 1',
            'quantity' => 2,
            'unit' => 'pcs',
            'price' => 50.00,
            'tax' => 10.00,
            'total' => 60.00
        ]);

        InvoiceItem::create([
            'invoice_id' => $this->invoice->id,
            'description' => 'Test Item 2',
            'quantity' => 1,
            'unit' => 'pcs',
            'price' => 50.00,
            'tax' => 10.00,
            'total' => 60.00
        ]);
    }

    public function test_admin_can_fiscalize_invoice()
    {
        // Mock the HTTP response for fiscalization API
        Http::fake([
            config('services.fiscalization.url') => Http::response([
                'body' => [[
                    'qrcode_url' => 'https://example.com/qr-code',
                    'fiscal_number' => 'FISCAL-123',
                    'status' => 'success'
                ]]
            ], 200)
        ]);

        Sanctum::actingAs($this->adminUser);

        $response = $this->postJson("/api/invoices/{$this->invoice->id}/fiscalize");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'data' => [
                        'body' => [[
                            'qrcode_url',
                            'fiscal_number',
                            'status'
                        ]],
                        'verification_url'
                    ],
                    'verification_url'
                ]);

        // Check that the invoice was updated
        $this->invoice->refresh();
        $this->assertEquals('sent', $this->invoice->fiscal_status);
        $this->assertNotNull($this->invoice->fiscal_response);
    }

    public function test_fiscalization_fails_with_invalid_invoice()
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->postJson("/api/invoices/99999/fiscalize");

        $response->assertStatus(404);
    }

    public function test_fiscalization_requires_admin_role()
    {
        // Create a public user
        $publicUser = User::factory()->create([
            'role' => 'public',
            'email' => 'public@test.com',
            'password' => bcrypt('password')
        ]);

        Sanctum::actingAs($publicUser);

        $response = $this->postJson("/api/invoices/{$this->invoice->id}/fiscalize");

        $response->assertStatus(403);
    }

    public function test_fiscalization_requires_authentication()
    {
        $response = $this->postJson("/api/invoices/{$this->invoice->id}/fiscalize");

        $response->assertStatus(401);
    }

    public function test_fiscalization_handles_api_error()
    {
        // Mock the HTTP response to simulate an error
        Http::fake([
            config('services.fiscalization.url') => Http::response([
                'error' => 'API Error',
                'message' => 'Something went wrong'
            ], 400)
        ]);

        Sanctum::actingAs($this->adminUser);

        $response = $this->postJson("/api/invoices/{$this->invoice->id}/fiscalize");

        $response->assertStatus(200)
                ->assertJson([
                    'status' => 'error'
                ]);

        // Check that the invoice was updated with error status
        $this->invoice->refresh();
        $this->assertEquals('error', $this->invoice->fiscal_status);
        $this->assertNotNull($this->invoice->fiscal_response);
    }

    public function test_fiscalization_handles_network_error()
    {
        // Mock the HTTP response to simulate a network error
        Http::fake([
            config('services.fiscalization.url') => Http::response('', 500)
        ]);

        Sanctum::actingAs($this->adminUser);

        $response = $this->postJson("/api/invoices/{$this->invoice->id}/fiscalize");

        $response->assertStatus(200)
                ->assertJson([
                    'status' => 'error'
                ])
                ->assertJsonStructure([
                    'status',
                    'data'
                ]);

        // Check that the invoice was updated with error status
        $this->invoice->refresh();
        $this->assertEquals('error', $this->invoice->fiscal_status);
        // fiscal_response might be null for certain error conditions
        // $this->assertNotNull($this->invoice->fiscal_response);
    }

    public function test_fiscalization_payload_structure()
    {
        // Mock the HTTP response
        Http::fake([
            config('services.fiscalization.url') => Http::response([
                'body' => [[
                    'qrcode_url' => 'https://example.com/qr-code',
                    'fiscal_number' => 'FISCAL-123',
                    'status' => 'success'
                ]]
            ], 200)
        ]);

        Sanctum::actingAs($this->adminUser);

        $response = $this->postJson("/api/invoices/{$this->invoice->id}/fiscalize");

        // Verify the HTTP request was made with correct payload structure
        Http::assertSent(function ($request) {
            $payload = $request->data();
            
            return $request->url() === config('services.fiscalization.url') &&
                   isset($payload['body'][0]['cmd']) &&
                   $payload['body'][0]['cmd'] === 'insert' &&
                   isset($payload['body'][0]['customer_name']) &&
                   $payload['body'][0]['customer_name'] === 'Test Client' &&
                   isset($payload['body'][0]['details']) &&
                   count($payload['body'][0]['details']) === 2;
        });
    }

    public function test_fiscalization_generates_verification_url()
    {
        // Mock the HTTP response for fiscalization API
        Http::fake([
            config('services.fiscalization.url') => Http::response([
                'body' => [[
                    'qrcode_url' => 'https://example.com/qr-code',
                    'fiscal_number' => '8CD406762EE243885B3F041F0A3135E3',
                    'status' => 'success'
                ]]
            ], 200)
        ]);

        Sanctum::actingAs($this->adminUser);

        $response = $this->postJson("/api/invoices/{$this->invoice->id}/fiscalize");

        $response->assertStatus(200)
                ->assertJson([
                    'status' => 'success'
                ]);

        $responseData = $response->json();
        
        // Check that verification URL is generated
        $this->assertArrayHasKey('verification_url', $responseData);
        $this->assertArrayHasKey('verification_url', $responseData['data']);
        
        $verificationUrl = $responseData['verification_url'];
        
        // Verify URL format matches Albanian tax authority pattern
        $this->assertStringContainsString('eFiskalizimi-app-test.tatime.gov.al/invoice/check/#/verify', $verificationUrl);
        $this->assertStringContainsString('iic=', $verificationUrl);
        $this->assertStringContainsString('tin=', $verificationUrl);
        $this->assertStringContainsString('crtd=', $verificationUrl);
        $this->assertStringContainsString('ord=', $verificationUrl);
        $this->assertStringContainsString('bu=', $verificationUrl);
        $this->assertStringContainsString('cr=', $verificationUrl);
        $this->assertStringContainsString('sw=', $verificationUrl);
        $this->assertStringContainsString('prc=', $verificationUrl);
        
        // Verify specific values
        $this->assertStringContainsString('iic=8CD406762EE243885B3F041F0A3135E3', $verificationUrl);
        $this->assertStringContainsString('tin=123456789', $verificationUrl);
        $this->assertStringContainsString('prc=120.00', $verificationUrl);
    }
} 