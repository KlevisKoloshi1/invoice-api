<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ImportServiceInterface;
use App\Services\ImportService;
use App\Services\InvoiceServiceInterface;
use App\Services\InvoiceService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ImportServiceInterface::class, ImportService::class);
        $this->app->bind(InvoiceServiceInterface::class, InvoiceService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
