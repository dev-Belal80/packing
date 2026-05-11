<?php

namespace App\Providers;

use App\Repositories\Eloquent\Production\ProductionOrderRepository;
use App\Repositories\Eloquent\Reception\GateInquiryRepository;
use App\Repositories\Eloquent\Reception\RawReceiptRepository;
use App\Repositories\Interfaces\Production\ProductionOrderRepositoryInterface;
use App\Repositories\Interfaces\Reception\GateInquiryRepositoryInterface;
use App\Repositories\Interfaces\Reception\RawReceiptRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(GateInquiryRepositoryInterface::class, GateInquiryRepository::class);
        $this->app->bind(RawReceiptRepositoryInterface::class, RawReceiptRepository::class);
        $this->app->bind(ProductionOrderRepositoryInterface::class, ProductionOrderRepository::class);
    }
}

