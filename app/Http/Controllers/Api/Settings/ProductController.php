<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends BaseApiController
{
	public function index(Request $request)
	{
		$products = Product::query()
			->when($request->filled('search'), function ($query) use ($request): void {
				$search = $request->string('search');
				$query->where('name', 'like', "%{$search}%")
					->orWhere('code', 'like', "%{$search}%");
			})
			->orderBy('name')
			->get(['id', 'code', 'name', 'unit', 'is_active']);

		return $this->success($products);
	}

	public function store(Request $request)
	{
		$tenantId = app()->has('current_tenant_id') ? app('current_tenant_id') : null;

		$validated = $request->validate([
			'name' => ['required', 'string', 'max:255'],
			'code' => [
				'nullable',
				'string',
				'max:255',
				Rule::unique('products', 'code')->when($tenantId, fn ($rule) => $rule->where('tenant_id', $tenantId)),
			],
			'unit' => ['sometimes', 'string', 'max:255'],
			'carton_weight_kg' => ['sometimes', 'nullable', 'numeric', 'min:0'],
			'grades' => ['sometimes', 'nullable', 'array'],
			'grades.*' => ['string', 'max:50'],
			'min_cooling_hours' => ['sometimes', 'numeric', 'min:0'],
			'waste_threshold_pct' => ['sometimes', 'numeric', 'min:0', 'max:100'],
			'is_active' => ['sometimes', 'boolean'],
		]);

		$product = Product::query()->create($validated);

		return $this->success(
			$product->only([
				'id', 'name', 'code', 'unit', 'carton_weight_kg', 'grades',
				'min_cooling_hours', 'waste_threshold_pct', 'is_active',
			]),
			'created',
			201
		);
	}

	public function update(Request $request, Product $product)
	{
		$tenantId = app()->has('current_tenant_id') ? app('current_tenant_id') : null;

		$validated = $request->validate([
			'name' => ['sometimes', 'required', 'string', 'max:255'],
			'code' => [
				'sometimes',
				'nullable',
				'string',
				'max:255',
				Rule::unique('products', 'code')
					->ignore($product->id)
					->when($tenantId, fn ($rule) => $rule->where('tenant_id', $tenantId)),
			],
			'unit' => ['sometimes', 'string', 'max:255'],
			'carton_weight_kg' => ['sometimes', 'nullable', 'numeric', 'min:0'],
			'grades' => ['sometimes', 'nullable', 'array'],
			'grades.*' => ['string', 'max:50'],
			'min_cooling_hours' => ['sometimes', 'numeric', 'min:0'],
			'waste_threshold_pct' => ['sometimes', 'numeric', 'min:0', 'max:100'],
			'is_active' => ['sometimes', 'boolean'],
		]);

		$product->fill($validated);
		$product->save();

		return $this->success(
			$product->only([
				'id', 'name', 'code', 'unit', 'carton_weight_kg', 'grades',
				'min_cooling_hours', 'waste_threshold_pct', 'is_active',
			]),
			'updated'
		);
	}

	public function destroy(Product $product)
	{
		$product->delete();

		return $this->success(['id' => $product->id], 'deleted');
	}
}
