<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\PalletType;
use Illuminate\Http\Request;

class PalletTypeController extends BaseApiController
{
    public function index(Request $request)
    {
        $palletTypes = PalletType::query()
            ->orderBy('name')
            ->get(['id', 'name', 'max_cartons', 'max_weight_kg', 'is_active']);

        return $this->success($palletTypes);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'max_cartons' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'max_weight_kg' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $palletType = PalletType::query()->create($validated);

        return $this->success(
            $palletType->only(['id', 'name', 'max_cartons', 'max_weight_kg', 'is_active']),
            'created',
            201
        );
    }

    public function update(Request $request, PalletType $palletType)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'max_cartons' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'max_weight_kg' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $palletType->fill($validated);
        $palletType->save();

        return $this->success(
            $palletType->only(['id', 'name', 'max_cartons', 'max_weight_kg', 'is_active']),
            'updated'
        );
    }

    public function destroy(PalletType $palletType)
    {
        $palletType->delete();

        return $this->success(['id' => $palletType->id], 'deleted');
    }
}
