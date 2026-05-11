<?php

namespace App\Http\Controllers\Api\Stock;

use App\Http\Controllers\Api\BaseApiController;
use App\Services\Stock\StockService;
use Illuminate\Http\Request;

class StockController extends BaseApiController
{
    public function __construct(private readonly StockService $service)
    {
    }

    public function index()
    {
        return $this->success($this->service->inventory());
    }

    public function pricing(Request $request)
    {
        $data = $request->validate([
            'raw_receipt_ids' => ['required', 'array'],
            'raw_receipt_ids.*' => ['integer'],
            'price_per_kg' => ['required', 'numeric', 'min:0'],
        ]);

        return $this->success($this->service->pricing($data), 'priced');
    }

    public function dispatchShipment(Request $request)
    {
        $data = $request->validate([
            'shipping_policy_id' => ['required', 'integer'],
        ]);

        return $this->success($this->service->dispatchShipment($data), 'dispatched');
    }
}

