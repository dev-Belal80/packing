<?php

namespace App\Observers;

use App\Models\ProductionOrder;

class ProductionOrderObserver
{
    public function created(ProductionOrder $productionOrder): void
    {
    }
}

