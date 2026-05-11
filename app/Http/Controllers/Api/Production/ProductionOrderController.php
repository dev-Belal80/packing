<?php

namespace App\Http\Controllers\Api\Production;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\Production\ProductionOrderResource;
use App\Services\Production\ProductionOrderService;
use Illuminate\Http\Request;

class ProductionOrderController extends BaseApiController
{
    public function __construct(private readonly ProductionOrderService $service)
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
            'product_id' => ['nullable', 'integer'],
            'production_line_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $model = $this->service->create($data);
        return $this->success(new ProductionOrderResource($model), 'created', 201);
    }

    public function show(int $id)
    {
        $model = $this->service->findOrFail($id);
        return $this->success(new ProductionOrderResource($model));
    }

    public function update(Request $request, int $id)
    {
        $data = $request->validate([
            'status' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $model = $this->service->update($id, $data);
        return $this->success(new ProductionOrderResource($model), 'updated');
    }

    public function destroy(int $id)
    {
        $this->service->delete($id);
        return $this->success(['ok' => true], 'deleted');
    }

    public function dispatch(int $id)
    {
        $model = $this->service->dispatch($id);
        return $this->success(new ProductionOrderResource($model), 'dispatched');
    }

    public function pause(int $id)
    {
        $model = $this->service->pause($id);
        return $this->success(new ProductionOrderResource($model), 'paused');
    }

    public function cancel(int $id)
    {
        $model = $this->service->cancel($id);
        return $this->success(new ProductionOrderResource($model), 'cancelled');
    }
}

