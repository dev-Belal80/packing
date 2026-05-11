<?php

namespace App\Http\Controllers\Api\Export;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\Export\ShippingPolicyResource;
use App\Services\Export\ShippingPolicyService;
use Illuminate\Http\Request;

class ShippingPolicyController extends BaseApiController
{
    public function __construct(private readonly ShippingPolicyService $service)
    {
    }

    public function index(Request $request)
    {
        $paginator = $this->service->paginate($request->integer('per_page', 15));
        return $this->paginated($paginator);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'reference' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $model = $this->service->create($data);
        return $this->success(new ShippingPolicyResource($model), 'created', 201);
    }

    public function show(int $id)
    {
        $model = $this->service->findOrFail($id);
        return $this->success(new ShippingPolicyResource($model));
    }

    public function update(Request $request, int $id)
    {
        $data = $request->validate([
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $model = $this->service->update($id, $data);
        return $this->success(new ShippingPolicyResource($model), 'updated');
    }

    public function destroy(int $id)
    {
        $this->service->delete($id);
        return $this->success(['ok' => true], 'deleted');
    }

    public function approve(int $id)
    {
        $model = $this->service->approve($id);
        return $this->success(new ShippingPolicyResource($model), 'approved');
    }
}

