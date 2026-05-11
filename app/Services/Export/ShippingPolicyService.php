<?php

namespace App\Services\Export;

use App\Models\ShippingPolicy;

class ShippingPolicyService
{
    public function paginate(int $perPage = 15)
    {
        return ShippingPolicy::query()->paginate($perPage);
    }

    public function findOrFail(int $id)
    {
        return ShippingPolicy::query()->findOrFail($id);
    }

    public function create(array $data)
    {
        return ShippingPolicy::query()->create($data);
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

    public function approve(int $id)
    {
        return $this->update($id, ['status' => 'approved']);
    }
}

