<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class LowStockService
{
    public const DEFAULT_LIMIT = 10;

    /**
     * The reusable low-stock query: current_stock <= min_stock.
     */
    public function query(): Builder
    {
        return Product::query()->lowStock();
    }

    /**
     * Items requiring restock, most urgent first.
     *
     * @return Collection<int, Product>
     */
    public function lowStockItems(int $limit = self::DEFAULT_LIMIT): Collection
    {
        return $this->query()
            ->with(['category', 'supplier'])
            // Biggest shortfall first, so a zero-stock item leads the list.
            ->orderByRaw('(min_stock - current_stock) DESC')
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * Paginated low-stock list for a restock report screen.
     */
    public function paginateLowStock(int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->with(['category', 'supplier'])
            ->orderByRaw('(min_stock - current_stock) DESC')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function lowStockCount(): int
    {
        return $this->query()->count();
    }

    public function outOfStockCount(): int
    {
        return Product::query()->outOfStock()->count();
    }

    /**
     * Counts and the top offenders, ready for a notification panel.
     *
     * @return array{low_stock:int,out_of_stock:int,items:Collection}
     */
    public function summary(int $limit = self::DEFAULT_LIMIT): array
    {
        return [
            'low_stock' => $this->lowStockCount(),
            'out_of_stock' => $this->outOfStockCount(),
            'items' => $this->lowStockItems($limit),
        ];
    }
}