<?php

namespace App\Repositories\Interfaces\Reception;

interface RawReceiptRepositoryInterface
{
    public function paginate(int $perPage = 15, array $filters = []);

    public function findOrFail(int $id);

    public function create(array $data);

    public function update(int $id, array $data);

    public function delete(int $id): void;
}

