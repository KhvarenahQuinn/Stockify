<?php

namespace App\Repositories\Contracts;

use App\Models\AttributeValue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AttributeValueRepository extends BaseRepositoryInterface
{
    public function allByAttribute(int $attributeId): Collection;
    
    public function existsForAttribute(int $attributeId, string $value): bool;
    
    public function deleteByAttribute(int $attributeId): bool;
}