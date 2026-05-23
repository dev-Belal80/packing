<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\ProductionStage;
use Illuminate\Http\Request;

class ProductionStageController extends BaseApiController
{
    public function index(Request $request)
    {
        $stages = ProductionStage::query()
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search');
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('order')
            ->get(['id', 'name', 'order']);

        return $this->success($stages);
    }
}
