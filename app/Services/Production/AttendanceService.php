<?php

namespace App\Services\Production;

use App\Models\EmployeeAttendance;

class AttendanceService
{
    public function paginate(int $perPage = 15)
    {
        return EmployeeAttendance::query()->paginate($perPage);
    }

    public function findOrFail(int $id)
    {
        return EmployeeAttendance::query()->findOrFail($id);
    }

    public function create(array $data)
    {
        return EmployeeAttendance::query()->create($data);
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

