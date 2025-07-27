<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Client;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        return response()->json(Client::orderByDesc('created_at')->paginate($perPage));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'nullable|email',
            'tax_id' => 'required|string',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
        ]);
        $client = Client::create($validated);
        return response()->json($client, 201);
    }

    public function show($id)
    {
        $client = Client::findOrFail($id);
        return response()->json($client);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string',
            'email' => 'nullable|email',
            'tax_id' => 'sometimes|string',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
        ]);
        $client = Client::findOrFail($id);
        $client->update($validated);
        return response()->json($client);
    }

    public function destroy($id)
    {
        $client = Client::findOrFail($id);
        $client->delete();
        return response()->json(['message' => 'Client deleted']);
    }
}
