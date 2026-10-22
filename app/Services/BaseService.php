<?php

namespace App\Services;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class BaseService
{
    public function __construct(
        protected readonly BaseRepositoryInterface $repository
    ) {
    }

    public function all(): Collection
    {
        return $this->repository->all();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    public function findOrFail(int $id): Model
    {
        return $this->repository->findOrFail($id);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): Model
    {
        return $this->repository->create($attributes);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(int $id, array $attributes): Model
    {
        return $this->repository->update($id, $attributes);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
