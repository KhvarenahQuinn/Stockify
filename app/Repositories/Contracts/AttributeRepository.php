<?php

namespace App\Repositories\Contracts;

use App\Models\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AttributeRepository extends BaseRepositoryInterface
{
    public function findByName(string $name): ?Attribute;
    
    public function withValuesPaginated(int $perPage = 15, ?string $search = null): LengthAwarePaginator;

    public function allWithValues(): Collection;
    
    public function valuesForAttribute(int $attributeId): Collection;
}