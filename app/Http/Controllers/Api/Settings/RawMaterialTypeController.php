<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\RawMaterialType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RawMaterialTypeController extends BaseApiController
{
    public function index(Request $request)
    {
        $items = RawMaterialType::query()
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search');
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->get(['id', 'name', 'unit', 'is_active']);

        return $this->success($items);
    }

    public function store(Request $request)
    {
        $tenantId = app()->has('current_tenant_id') ? app('current_tenant_id') : null;

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('raw_material_types', 'name')->when($tenantId, fn ($rule) => $rule->where('tenant_id', $tenantId)),
            ],
            'unit' => ['sometimes', 'nullable', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $item = RawMaterialType::query()->create($validated);

        return $this->success($item->only(['id', 'name', 'unit', 'is_active']), 'created', 201);
    }

    public function update(Request $request, RawMaterialType $rawMaterialType)
    {
        $tenantId = app()->has('current_tenant_id') ? app('current_tenant_id') : null;

        $validated = $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('raw_material_types', 'name')
                    ->ignore($rawMaterialType->id)
                    ->when($tenantId, fn ($rule) => $rule->where('tenant_id', $tenantId)),
            ],
            'unit' => ['sometimes', 'nullable', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $rawMaterialType->fill($validated);
        $rawMaterialType->save();

        return $this->success($rawMaterialType->only(['id', 'name', 'unit', 'is_active']), 'updated');
    }

    public function destroy(RawMaterialType $rawMaterialType)
    {
        $rawMaterialType->delete();

        return $this->success(['id' => $rawMaterialType->id], 'deleted');
    }
}