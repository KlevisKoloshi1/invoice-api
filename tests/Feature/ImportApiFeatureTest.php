<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImportApiFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_admin_can_login_and_upload_excel()
    {
        // Seed an admin user
        $user = User::factory()->create(['role' => 'admin', 'password' => bcrypt('password')]);
        // Login
        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $response->assertStatus(200);
        $token = $response->json('token');
        // Upload Excel
        Storage::fake('local');
        $file = UploadedFile::fake()->create('import.xlsx');
        $upload = $this->postJson('/api/imports', [
            'file' => $file,
        ], [
            'Authorization' => 'Bearer ' . $token,
        ]);
        $upload->assertStatus(201);
        $this->assertDatabaseHas('imports', ['status' => 'completed']);
    }
}
