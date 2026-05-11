<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Packhouse;
use Illuminate\Http\Request;

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
}
