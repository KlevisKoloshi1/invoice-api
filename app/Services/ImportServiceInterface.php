<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use App\Models\Import;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ImportServiceInterface
{
    public function importFromExcel(UploadedFile $file, int $userId): Import;
    public function fiscalizeImport(int $importId): array;
    public function updateImport(int $id, array $data): Import;
    public function deleteImport(int $id): bool;
    public function listImports(int $perPage = 15): LengthAwarePaginator;
} 