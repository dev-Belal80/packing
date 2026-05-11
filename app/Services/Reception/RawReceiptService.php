<?php

namespace App\Services\Reception;

use App\Repositories\Interfaces\Reception\RawReceiptRepositoryInterface;

class RawReceiptService
{
    public function __construct(private readonly RawReceiptRepositoryInterface $repo)
    {
    }

    public function paginate(int $perPage = 15)
    {
        return $this->repo->paginate($perPage);
    }

    public function findOrFail(int $id)
    {
        return $this->repo->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->repo->create($data);
    }

    public function update(int $id, array $data)
    {
        return $this->repo->update($id, $data);
    }

    public function delete(int $id): void
    {
        $this->repo->delete($id);
    }

    public function approve(int $id)
    {
        return $this->repo->update($id, ['status' => 'approved']);
    }
}

