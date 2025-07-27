<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use App\Services\InvoiceServiceInterface;

class FiscalizePendingInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fiscalize-pending-invoices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $service = app(InvoiceServiceInterface::class);
        $pending = Invoice::where('fiscal_status', 'pending')->get();
        $this->info('Fiscalizing ' . $pending->count() . ' pending invoices...');
        foreach ($pending as $invoice) {
            $result = $service->fiscalizeInvoice($invoice->id);
            $this->info('Invoice #' . $invoice->id . ': ' . $result['status']);
        }
        $this->info('Done.');
    }
}
