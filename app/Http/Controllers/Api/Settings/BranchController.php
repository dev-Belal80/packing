<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\ProductionOrder;
use Illuminate\Http\Request;

class BranchController extends BaseApiController
{
    public function index(Request $request)
    {
        $branches = ProductionOrder::query()
            ->whereNotNull('branch')
            ->where('branch', '!=', '')
            ->distinct()
            ->orderBy('branch')
            ->pluck('branch')
            ->map(fn (string $branch) => ['value' => $branch])
            ->values();

        return $this->success($branches);
    }
}
