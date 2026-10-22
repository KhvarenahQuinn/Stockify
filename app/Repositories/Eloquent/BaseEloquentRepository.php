<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class BaseEloquentRepository implements BaseRepositoryInterface
{
    public function __construct(
        protected readonly string $modelClass
    ) {
    }

    public function all(): Collection
    {
        return $this->modelClass::query()->get();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->modelClass::query()->paginate($perPage);
    }

    public function findOrFail(int $id): Model
    {
        return $this->modelClass::query()->findOrFail($id);
    }

    public function create(array $attributes): Model
    {
        return $this->modelClass::query()->create($attributes);
    }

    public function update(int $id, array $attributes): Model
    {
        $model = $this->findOrFail($id);
        $model->update($attributes);

        return $model->refresh();
    }

    public function delete(int $id): bool
    {
        return (bool) $this->findOrFail($id)->delete();
    }
}
