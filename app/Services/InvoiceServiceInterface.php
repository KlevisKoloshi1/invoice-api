<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface InvoiceServiceInterface
{
    public function createInvoice(array $data): Invoice;
    public function updateInvoice(int $id, array $data): Invoice;
    public function deleteInvoice(int $id): bool;
    public function listInvoices(int $perPage = 15): LengthAwarePaginator;
    public function fiscalizeInvoice(int $id): array;
    public function createFiscalizationItem(array $itemData): array;
    public function getFiscalizationItems(): array;
    public function createFiscalizationCustomer(array $customerData): array;
    public function getFiscalizationCustomers(): array;
} 