<?php

namespace App\Services\Export;

use App\Models\Pallet;

class PalletService
{
    public function paginate(int $perPage = 15)
    {
        return Pallet::query()->paginate($perPage);
    }

    public function findOrFail(int $id)
    {
        return Pallet::query()->findOrFail($id);
    }

    public function create(array $data)
    {
        return Pallet::query()->create($data);
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

    public function cooling(int $id)
    {
        return $this->update($id, ['status' => 'cooling']);
    }

    public function confirmReceipt(int $id)
    {
        return $this->update($id, ['status' => 'receipt_confirmed']);
    }
}

