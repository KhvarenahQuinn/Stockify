<?php

namespace App\Repositories\Eloquent;

use App\Models\Attribute;
use App\Repositories\Contracts\AttributeRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentAttributeRepository extends BaseEloquentRepository implements AttributeRepository
{
    public function __construct()
    {
        parent::__construct(Attribute::class);
    }

    public function findByName(string $name): ?Attribute
    {
        return $this->modelClass::query()
            ->where('name', $name)
            ->first();
    }

    public function withValuesPaginated(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        return $this->modelClass::query()
            ->search($search)
            ->with('values')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function allWithValues(): Collection
    {
        return $this->modelClass::query()
            ->with('values')
            ->orderBy('name')
            ->get();
    }

    public function valuesForAttribute(int $attributeId): Collection
    {
        return $this->modelClass::query()
            ->where('id', $attributeId)
            ->with('values')
            ->first()
            ->values ?? collect();
    }
}