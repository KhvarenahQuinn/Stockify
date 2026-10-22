<?php

namespace App\Repositories\Eloquent;

use App\Models\AttributeValue;
use App\Repositories\Contracts\AttributeValueRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentAttributeValueRepository extends BaseEloquentRepository implements AttributeValueRepository
{
    public function __construct()
    {
        parent::__construct(AttributeValue::class);
    }

    public function allByAttribute(int $attributeId): Collection
    {
        return $this->modelClass::query()
            ->where('attribute_id', $attributeId)
            ->orderBy('value')
            ->get();
    }

    public function existsForAttribute(int $attributeId, string $value): bool
    {
        return $this->modelClass::query()
            ->where('attribute_id', $attributeId)
            ->where('value', $value)
            ->exists();
    }

    public function deleteByAttribute(int $attributeId): bool
    {
        return (bool) $this->modelClass::query()
            ->where('attribute_id', $attributeId)
            ->delete();
    }
}