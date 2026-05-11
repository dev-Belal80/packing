<?php

namespace App\Repositories\Eloquent\Production;

use App\Models\ProductionOrder;
use App\Repositories\Interfaces\Production\ProductionOrderRepositoryInterface;

class ProductionOrderRepository implements ProductionOrderRepositoryInterface
{
    public function paginate(int $perPage = 15)
    {
        return ProductionOrder::query()->latest('id')->paginate($perPage);
    }

    public function findOrFail(int $id)
    {
        return ProductionOrder::query()->findOrFail($id);
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

