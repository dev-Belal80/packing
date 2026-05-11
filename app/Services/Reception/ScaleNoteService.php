<?php

namespace App\Services\Reception;

use App\Models\ScaleNote;

class ScaleNoteService
{
    public function paginate(int $perPage = 15)
    {
        return ScaleNote::query()->paginate($perPage);
    }

    public function findOrFail(int $id)
    {
        return ScaleNote::query()->findOrFail($id);
    }

    public function create(array $data)
    {
        if (!isset($data['net_weight'])) {
            $gross = (float) ($data['gross_weight'] ?? 0);
            $tare = (float) ($data['tare_weight'] ?? 0);
            $data['net_weight'] = max(0, $gross - $tare);
        }

        return ScaleNote::query()->create($data);
    }

    public function update(int $id, array $data)
    {
        $model = $this->findOrFail($id);
        $model->fill($data);

        if (array_key_exists('gross_weight', $data) || array_key_exists('tare_weight', $data)) {
            $gross = (float) ($model->gross_weight ?? 0);
            $tare = (float) ($model->tare_weight ?? 0);
            $model->net_weight = max(0, $gross - $tare);
        }

        $model->save();
        return $model;
    }

    public function delete(int $id): void
    {
        $this->findOrFail($id)->delete();
    }
}

