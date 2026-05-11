<?php

namespace App\Services\Production;

use App\Models\SortRecord;

class SortRecordService
{
    public function paginate(int $perPage = 15)
    {
        return SortRecord::query()->paginate($perPage);
    }

    public function findOrFail(int $id)
    {
        return SortRecord::query()->findOrFail($id);
    }

    public function create(array $data)
    {
        return SortRecord::query()->create($data);
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

