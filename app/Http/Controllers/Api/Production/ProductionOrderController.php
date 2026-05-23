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
        $filters = $request->only([
            'status',
            'packhouse_id',
            'production_line_id',
            'production_stage_id',
            'supervisor_id',
            'product_id',
            'raw_receipt_id',
            'date_from',
            'date_to',
            'search',
        ]);

        $paginator = $this->service->paginate($request->integer('per_page', 15), $filters);
        $paginator->setCollection(
            $paginator->getCollection()->map(fn ($model) => new ProductionOrderResource($model))
        );

        return $this->paginated($paginator);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'packhouse_id' => ['required', 'integer', 'exists:packhouses,id'],
            'raw_receipt_id' => ['required', 'integer', 'exists:raw_receipts,id'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'pallet_type_id' => ['nullable', 'integer', 'exists:pallet_types,id'],
            'production_line_id' => ['required', 'integer', 'exists:production_lines,id'],
            'production_stage_id' => ['required', 'integer', 'exists:production_stages,id'],
            'supplier_contact_id' => ['nullable', 'integer', 'exists:contacts,id'],
            'client_contact_id' => ['nullable', 'integer', 'exists:contacts,id'],
            'supervisor_id' => ['required', 'integer', 'exists:users,id'],
            'other_supervisor_ids' => ['nullable', 'array'],
            'other_supervisor_ids.*' => ['integer', 'exists:users,id'],
            'accounting_period' => ['nullable', 'string', 'max:50'],
            'branch' => ['nullable', 'string', 'max:100'],
            'order_date' => ['nullable', 'date'],
            'special_code' => ['nullable', 'string', 'max:100'],
            'target_qty_kg' => ['required', 'numeric', 'min:0'],
            'actual_input_kg' => ['nullable', 'numeric', 'min:0'],
            'order_type' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $model = $this->service->create($data);
        $model = $this->service->findOrFail($model->id);

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
            'packhouse_id' => ['nullable', 'integer', 'exists:packhouses,id'],
            'raw_receipt_id' => ['nullable', 'integer', 'exists:raw_receipts,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'pallet_type_id' => ['nullable', 'integer', 'exists:pallet_types,id'],
            'production_line_id' => ['nullable', 'integer', 'exists:production_lines,id'],
            'production_stage_id' => ['nullable', 'integer', 'exists:production_stages,id'],
            'supplier_contact_id' => ['nullable', 'integer', 'exists:contacts,id'],
            'client_contact_id' => ['nullable', 'integer', 'exists:contacts,id'],
            'supervisor_id' => ['nullable', 'integer', 'exists:users,id'],
            'other_supervisor_ids' => ['nullable', 'array'],
            'other_supervisor_ids.*' => ['integer', 'exists:users,id'],
            'accounting_period' => ['nullable', 'string', 'max:50'],
            'branch' => ['nullable', 'string', 'max:100'],
            'order_date' => ['nullable', 'date'],
            'special_code' => ['nullable', 'string', 'max:100'],
            'target_qty_kg' => ['nullable', 'numeric', 'min:0'],
            'actual_input_kg' => ['nullable', 'numeric', 'min:0'],
            'order_type' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $model = $this->service->update($id, $data);
        $model = $this->service->findOrFail($model->id);

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

