<?php

namespace App\Repositories\Eloquent\Reception;

use App\Models\GateInquiry;
use App\Repositories\Interfaces\Reception\GateInquiryRepositoryInterface;

class GateInquiryRepository implements GateInquiryRepositoryInterface
{
    public function paginate(int $perPage = 15)
    {
        return GateInquiry::query()->latest('id')->paginate($perPage);
    }

    public function findOrFail(int $id)
    {
        return GateInquiry::query()->findOrFail($id);
    }

    public function create(array $data)
    {
        return GateInquiry::query()->create($data);
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

