<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\ProductionLine;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductionLineController extends BaseApiController
{
	public function index(Request $request)
	{
		$lines = ProductionLine::query()
			->when($request->filled('packhouse_id'), fn ($query) => $query->where('packhouse_id', $request->integer('packhouse_id')))
			->when($request->filled('search'), function ($query) use ($request): void {
				$search = $request->string('search');
				$query->where('name', 'like', "%{$search}%");
			})
			->orderBy('name')
			->get(['id', 'packhouse_id', 'name', 'status', 'is_active']);

		return $this->success($lines);
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
			'status' => ['sometimes', 'string', 'max:255'],
			'is_active' => ['sometimes', 'boolean'],
		]);

		$productionLine = ProductionLine::query()->create($validated);

		return $this->success(
			$productionLine->only(['id', 'packhouse_id', 'name', 'status', 'is_active']),
			'created',
			201
		);
	}

	public function update(Request $request, ProductionLine $productionLine)
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
			'status' => ['sometimes', 'required', 'string', 'max:255'],
			'is_active' => ['sometimes', 'boolean'],
		]);

		$productionLine->fill($validated);
		$productionLine->save();

		return $this->success(
			$productionLine->only(['id', 'packhouse_id', 'name', 'status', 'is_active']),
			'updated'
		);
	}

	public function destroy(ProductionLine $productionLine)
	{
		$productionLine->delete();

		return $this->success([
			'id' => $productionLine->id,
		], 'deleted');
	}
}
