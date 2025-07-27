<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\ImportController;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Authentication
Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (Auth::attempt($credentials)) {
        $user = Auth::user();
        $token = $user->createToken('auth-token')->plainTextToken;
        return response()->json([
            'token' => $token,
            'user' => $user
        ]);
    }

    return response()->json(['message' => 'Invalid credentials'], 401);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Clients
    Route::apiResource('clients', ClientController::class);
    
    // Invoices
    Route::apiResource('invoices', InvoiceController::class);
    Route::post('invoices/{id}/fiscalize', [InvoiceController::class, 'fiscalize']);
    
    // Imports
    Route::apiResource('imports', ImportController::class);
    Route::post('imports/{id}/fiscalize', [ImportController::class, 'fiscalize']);
    
    // Fiscalization Management
    Route::prefix('fiscalization')->group(function () {
        Route::get('customers', [InvoiceController::class, 'getFiscalizationCustomers']);
        Route::post('customers', [InvoiceController::class, 'createFiscalizationCustomer']);
        Route::get('items', [InvoiceController::class, 'getFiscalizationItems']);
        Route::post('items', [InvoiceController::class, 'createFiscalizationItem']);
    });
}); 