<?php

namespace App\Repositories\Eloquent\Reception;

use App\Models\RawReceipt;
use App\Repositories\Interfaces\Reception\RawReceiptRepositoryInterface;

class RawReceiptRepository implements RawReceiptRepositoryInterface
{
    private function query()
    {
        return RawReceipt::query()->with([
            'contact',
            'rawMaterialType',
            'gateInquiry',
            'scaleNote',
            'packhouse',
            'approvedBy',
        ]);
    }

    public function paginate(int $perPage = 15)
    {
        return $this->query()->latest('id')->paginate($perPage);
    }

    public function findOrFail(int $id)
    {
        return $this->query()->findOrFail($id);
    }

    public function create(array $data)
    {
        $model = RawReceipt::query()->create($data);

        return $this->query()->findOrFail($model->id);
    }

    public function update(int $id, array $data)
    {
        $model = $this->findOrFail($id);
        $model->fill($data);
        $model->save();

        return $this->query()->findOrFail($model->id);
    }

    public function delete(int $id): void
    {
        RawReceipt::query()->whereKey($id)->delete();
    }
}

