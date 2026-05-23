<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Fridge;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FridgeController extends BaseApiController
{
	public function index(Request $request)
	{
		$fridges = Fridge::query()
			->when($request->filled('search'), function ($query) use ($request): void {
				$search = $request->string('search');
				$query->where('name', 'like', "%{$search}%");
			})
			->orderBy('name')
			->get(['id', 'packhouse_id', 'name', 'capacity_tons', 'min_temp', 'max_temp', 'is_active']);

		return $this->success($fridges);
	}

	public function store(Request $request)
	{
		$tenantId = app()->has('current_tenant_id') ? app('current_tenant_id') : null;

		$validated = $request->validate([
			'packhouse_id' => [
				'required',
				'integer',
				Rule::exists('packhouses', 'id')->when($tenantId, fn ($rule) => $rule->where('tenant_id', $tenantId)),
			],
			'name' => ['required', 'string', 'max:255'],
			'capacity_tons' => ['sometimes', 'nullable', 'numeric', 'min:0'],
			'min_temp' => ['sometimes', 'nullable', 'numeric'],
			'max_temp' => ['sometimes', 'nullable', 'numeric'],
			'is_active' => ['sometimes', 'boolean'],
		]);

		$fridge = Fridge::query()->create($validated);

		return $this->success(
			$fridge->only(['id', 'packhouse_id', 'name', 'capacity_tons', 'min_temp', 'max_temp', 'is_active']),
			'created',
			201
		);
	}

	public function update(Request $request, Fridge $fridge)
	{
		$tenantId = app()->has('current_tenant_id') ? app('current_tenant_id') : null;

		$validated = $request->validate([
			'packhouse_id' => [
				'sometimes',
				'required',
				'integer',
				Rule::exists('packhouses', 'id')->when($tenantId, fn ($rule) => $rule->where('tenant_id', $tenantId)),
			],
			'name' => ['sometimes', 'required', 'string', 'max:255'],
			'capacity_tons' => ['sometimes', 'nullable', 'numeric', 'min:0'],
			'min_temp' => ['sometimes', 'nullable', 'numeric'],
			'max_temp' => ['sometimes', 'nullable', 'numeric'],
			'is_active' => ['sometimes', 'boolean'],
		]);

		$fridge->fill($validated);
		$fridge->save();

		return $this->success(
			$fridge->only(['id', 'packhouse_id', 'name', 'capacity_tons', 'min_temp', 'max_temp', 'is_active']),
			'updated'
		);
	}

	public function destroy(Fridge $fridge)
	{
		$fridge->delete();

		return $this->success([
			'id' => $fridge->id,
		], 'deleted');
	}
}
