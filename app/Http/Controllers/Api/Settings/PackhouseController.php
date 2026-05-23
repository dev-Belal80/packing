<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Packhouse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PackhouseController extends BaseApiController
{
	public function index(Request $request)
	{
		$packhouses = Packhouse::query()
			->when($request->filled('search'), function ($query) use ($request): void {
				$search = $request->string('search');
				$query->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%");
			})
			->orderBy('name')
			->get(['id', 'code', 'name', 'location']);

		return $this->success($packhouses);
	}

	public function store(Request $request)
	{
		$tenantId = app()->has('current_tenant_id') ? app('current_tenant_id') : null;

		$validated = $request->validate([
			'code' => [
				'nullable',
				'string',
				'max:255',
				Rule::unique('packhouses', 'code')->when($tenantId, fn ($rule) => $rule->where('tenant_id', $tenantId)),
			],
			'name' => ['required', 'string', 'max:255'],
			'location' => ['nullable', 'string', 'max:255'],
			'is_active' => ['sometimes', 'boolean'],
		]);

		$packhouse = Packhouse::query()->create($validated);

		return $this->success(
			$packhouse->only(['id', 'code', 'name', 'location', 'is_active']),
			'created',
			201
		);
	}

	public function update(Request $request, Packhouse $packhouse)
	{
		$tenantId = app()->has('current_tenant_id') ? app('current_tenant_id') : null;

		$validated = $request->validate([
			'code' => [
				'sometimes',
				'nullable',
				'string',
				'max:255',
				Rule::unique('packhouses', 'code')
					->ignore($packhouse->id)
					->when($tenantId, fn ($rule) => $rule->where('tenant_id', $tenantId)),
			],
			'name' => ['sometimes', 'required', 'string', 'max:255'],
			'location' => ['sometimes', 'nullable', 'string', 'max:255'],
			'is_active' => ['sometimes', 'boolean'],
		]);

		$packhouse->fill($validated);
		$packhouse->save();

		return $this->success($packhouse->only(['id', 'code', 'name', 'location', 'is_active']), 'updated');
	}

	public function destroy(Packhouse $packhouse)
	{
		$packhouse->delete();

		return $this->success([
			'id' => $packhouse->id,
		], 'deleted');
	}
}
