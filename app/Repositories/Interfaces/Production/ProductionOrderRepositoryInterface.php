<?php

namespace App\Repositories\Interfaces\Production;

interface ProductionOrderRepositoryInterface
{
    public function paginate(int $perPage = 15);

    public function findOrFail(int $id);

    public function create(array $data);

    public function update(int $id, array $data);

    public function delete(int $id): void;
}

