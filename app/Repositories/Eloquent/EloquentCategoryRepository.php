<?php

namespace App\Repositories\Eloquent;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentCategoryRepository extends BaseEloquentRepository implements CategoryRepository
{
    public function __construct()
    {
        parent::__construct(Category::class);
    }

    public function findBySlug(string $slug): ?Category
    {
        return $this->modelClass::query()
            ->where('slug', $slug)
            ->first();
    }

    public function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $query = $this->modelClass::query()->where('slug', $slug);
        
        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }
        
        return $query->exists();
    }

    public function withProductsCountPaginated(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        return $this->modelClass::query()
            ->search($search)
            ->withCount('products')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
