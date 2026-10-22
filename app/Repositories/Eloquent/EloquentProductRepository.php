<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentProductRepository extends BaseEloquentRepository implements ProductRepository
{
    public function __construct()
    {
        parent::__construct(Product::class);
    }

    public function findBySku(string $sku): ?Product
    {
        return $this->modelClass::query()
            ->where('sku', $sku)
            ->first();
    }

    public function skuExists(string $sku): bool
    {
        return $this->modelClass::query()
            ->where('sku', $sku)
            ->exists();
    }

    public function nextSkuSequence(int $categoryId): int
    {
        return (int) $this->modelClass::query()
            ->where('category_id', $categoryId)
            ->count() + 1;
    }

    /**
     * The catalogue listing, with the filters the index screen offers.
     *
     * The stock buckets mirror the stock-badge component's rule exactly, so the
     * status filter and the status badge can never disagree about what "low"
     * means: out is zero, low is one-or-more but at or under the reorder point.
     */
    public function withRelationsPaginated(
        int $perPage = 15,
        ?string $search = null,
        ?int $categoryId = null,
        ?string $stockStatus = null
    ): LengthAwarePaginator {
        return $this->modelClass::query()
            ->search($search)
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->when($stockStatus === 'out', fn ($q) => $q->where('current_stock', '<=', 0))
            ->when($stockStatus === 'low', fn ($q) => $q->where('current_stock', '>', 0)
                ->whereColumn('current_stock', '<=', 'min_stock'))
            ->when($stockStatus === 'safe', fn ($q) => $q->whereColumn('current_stock', '>', 'min_stock'))
            ->with(['category', 'supplier', 'attributeValues.attribute'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}