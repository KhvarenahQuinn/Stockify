<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface BaseRepositoryInterface
{
    public function all(): Collection;

    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function findOrFail(int $id): Model;

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): Model;

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(int $id, array $attributes): Model;

    public function delete(int $id): bool;
}
