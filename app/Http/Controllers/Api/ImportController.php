<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ImportServiceInterface;
use Illuminate\Support\Facades\Auth;

class ImportController extends Controller
{
    protected $importService;

    public function __construct(ImportServiceInterface $importService)
    {
        $this->importService = $importService;
    }

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        return response()->json($this->importService->listImports($perPage));
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);
        $import = $this->importService->importFromExcel($request->file('file'), Auth::id());
        return response()->json($import, 201);
    }

    public function show($id)
    {
        return response()->json($this->importService->listImports()->find($id));
    }

    public function update(Request $request, $id)
    {
        $import = $this->importService->updateImport($id, $request->all());
        return response()->json($import);
    }

    public function destroy($id)
    {
        $this->importService->deleteImport($id);
        return response()->json(['message' => 'Import deleted']);
    }

    public function fiscalize($id)
    {
        $result = $this->importService->fiscalizeImport($id);
        return response()->json($result);
    }
}
