<?php

namespace App\Repositories\Contracts;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CategoryRepository extends BaseRepositoryInterface
{
    public function findBySlug(string $slug): ?Category;
    
    public function slugExists(string $slug, ?int $ignoreId = null): bool;
    
    public function withProductsCountPaginated(int $perPage = 15, ?string $search = null): LengthAwarePaginator;
}
