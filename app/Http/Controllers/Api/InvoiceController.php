<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\InvoiceServiceInterface;

class InvoiceController extends Controller
{
    protected $invoiceService;

    public function __construct(InvoiceServiceInterface $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        return response()->json($this->invoiceService->listInvoices($perPage));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'invoice_number' => 'required|string',
            'invoice_date' => 'required|date',
            'total_without_tax' => 'required|numeric',
            'total_tax' => 'required|numeric',
            'total_with_tax' => 'required|numeric',
            'items' => 'required|array',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric',
            'items.*.unit' => 'required|string',
            'items.*.price' => 'required|numeric',
            'items.*.tax' => 'required|numeric',
            'items.*.total' => 'required|numeric',
        ]);
        $invoice = $this->invoiceService->createInvoice($validated);
        return response()->json($invoice, 201);
    }

    public function show($id)
    {
        $invoice = $this->invoiceService->listInvoices()->find($id);
        return response()->json($invoice);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'client_id' => 'sometimes|exists:clients,id',
            'invoice_number' => 'sometimes|string',
            'invoice_date' => 'sometimes|date',
            'total_without_tax' => 'sometimes|numeric',
            'total_tax' => 'sometimes|numeric',
            'total_with_tax' => 'sometimes|numeric',
            'items' => 'sometimes|array',
            'items.*.description' => 'required_with:items|string',
            'items.*.quantity' => 'required_with:items|numeric',
            'items.*.unit' => 'required_with:items|string',
            'items.*.price' => 'required_with:items|numeric',
            'items.*.tax' => 'required_with:items|numeric',
            'items.*.total' => 'required_with:items|numeric',
        ]);
        $invoice = $this->invoiceService->updateInvoice($id, $validated);
        return response()->json($invoice);
    }

    public function destroy($id)
    {
        $this->invoiceService->deleteInvoice($id);
        return response()->json(['message' => 'Invoice deleted']);
    }

    public function fiscalize($id)
    {
        $result = $this->invoiceService->fiscalizeInvoice($id);
        return response()->json($result);
    }

    public function createFiscalizationItem(Request $request)
    {
        $validated = $request->validate([
            'item_code' => 'required|string',
            'item_name' => 'required|string',
            'price' => 'required|numeric',
        ]);
        
        $result = $this->invoiceService->createFiscalizationItem($validated);
        return response()->json($result);
    }

    public function getFiscalizationItems()
    {
        $result = $this->invoiceService->getFiscalizationItems();
        return response()->json($result);
    }

    public function createFiscalizationCustomer(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'tax_id' => 'nullable|string',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
        ]);
        
        $result = $this->invoiceService->createFiscalizationCustomer($validated);
        return response()->json($result);
    }

    public function getFiscalizationCustomers()
    {
        $result = $this->invoiceService->getFiscalizationCustomers();
        return response()->json($result);
    }
}
