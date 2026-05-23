<?php

namespace App\Repositories\Eloquent\Production;

use App\Models\ProductionOrder;
use App\Repositories\Interfaces\Production\ProductionOrderRepositoryInterface;

class ProductionOrderRepository implements ProductionOrderRepositoryInterface
{
    public function paginate(int $perPage = 15, array $filters = [])
    {
        return ProductionOrder::query()
            ->with([
                'packhouse',
                'rawReceipt',
                'product',
                'palletType',
                'productionLine',
                'productionStage',
                'supervisor',
                'supplierContact',
                'clientContact',
                'sortRecordLines',
            ])
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(! empty($filters['packhouse_id']), fn ($query) => $query->where('packhouse_id', $filters['packhouse_id']))
            ->when(! empty($filters['production_line_id']), fn ($query) => $query->where('production_line_id', $filters['production_line_id']))
            ->when(! empty($filters['production_stage_id']), fn ($query) => $query->where('production_stage_id', $filters['production_stage_id']))
            ->when(! empty($filters['supervisor_id']), fn ($query) => $query->where('supervisor_id', $filters['supervisor_id']))
            ->when(! empty($filters['product_id']), fn ($query) => $query->where('product_id', $filters['product_id']))
            ->when(! empty($filters['raw_receipt_id']), fn ($query) => $query->where('raw_receipt_id', $filters['raw_receipt_id']))
            ->when(! empty($filters['date_from']), fn ($query) => $query->whereDate('order_date', '>=', $filters['date_from']))
            ->when(! empty($filters['date_to']), fn ($query) => $query->whereDate('order_date', '<=', $filters['date_to']))
            ->when(! empty($filters['search']), fn ($query) => $query->where('reference_no', 'like', '%' . $filters['search'] . '%'))
            ->latest('id')
            ->paginate($perPage);
    }

    public function findOrFail(int $id)
    {
        return ProductionOrder::query()
            ->with([
                'packhouse',
                'rawReceipt',
                'product',
                'palletType',
                'productionLine',
                'productionStage',
                'supervisor',
                'supplierContact',
                'clientContact',
                'sortRecordLines',
            ])
            ->findOrFail($id);
    }

    public function create(array $data)
    {
        return ProductionOrder::query()->create($data);
    }

    public function update(int $id, array $data)
    {
        $model = $this->findOrFail($id);
        $model->fill($data);
        $model->save();
        return $model;
    }

    public function delete(int $id): void
    {
        $this->findOrFail($id)->delete();
    }
}

