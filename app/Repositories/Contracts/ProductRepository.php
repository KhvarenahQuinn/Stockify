<?php

namespace App\Repositories\Contracts;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProductRepository extends BaseRepositoryInterface
{
    public function findBySku(string $sku): ?Product;

    public function skuExists(string $sku): bool;

    public function nextSkuSequence(int $categoryId): int;

    public function withRelationsPaginated(
        int $perPage = 15,
        ?string $search = null,
        ?int $categoryId = null,
        ?string $stockStatus = null
    ): LengthAwarePaginator;
}