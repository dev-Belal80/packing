<?php

namespace App\Repositories\Interfaces\Production;

interface ProductionOrderRepositoryInterface
{
    public function paginate(int $perPage = 15, array $filters = []);

    public function findOrFail(int $id);

    public function create(array $data);

    public function update(int $id, array $data);

    public function dispatch(int $id);

    public function cancel(int $id);

    public function delete(int $id): void;
}

