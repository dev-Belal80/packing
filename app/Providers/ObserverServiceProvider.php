<?php

namespace App\Providers;

use App\Models\ProductionOrder;
use App\Models\RawReceipt;
use App\Observers\ProductionOrderObserver;
use App\Observers\RawReceiptObserver;
use Illuminate\Support\ServiceProvider;

class ObserverServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (class_exists(RawReceipt::class)) {
            RawReceipt::observe(RawReceiptObserver::class);
        }

        if (class_exists(ProductionOrder::class)) {
            ProductionOrder::observe(ProductionOrderObserver::class);
        }
    }
}
