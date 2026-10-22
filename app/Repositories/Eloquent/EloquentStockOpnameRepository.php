<?php

namespace App\Repositories\Eloquent;

use App\Models\StockOpname;
use App\Repositories\Contracts\StockOpnameRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class EloquentStockOpnameRepository extends BaseEloquentRepository implements StockOpnameRepository
{
    public function __construct()
    {
        parent::__construct(StockOpname::class);
    }

    public function findByNumber(string $number): ?StockOpname
    {
        return $this->modelClass::query()
            ->where('opname_number', $number)
            ->first();
    }

    public function numberExists(string $number): bool
    {
        return $this->modelClass::query()
            ->where('opname_number', $number)
            ->exists();
    }

    public function nextDailySequence(string $date): int
    {
        $day = Carbon::createFromFormat('Ymd', $date)->startOfDay();

        return (int) $this->modelClass::query()
            ->whereBetween('created_at', [$day, $day->copy()->endOfDay()])
            ->count() + 1;
    }

    public function paginatedWithRelations(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->modelClass::query()
            ->with(['product', 'user']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        // Only surface genuine discrepancies unless the caller asks for all.
        if (array_key_exists('only_discrepancies', $filters)) {
            $filters['only_discrepancies']
                ? $query->where('difference', '!=', 0)
                : $query->where('difference', 0);
        }

        return $query->orderByDesc('created_at')
            ->paginate($perPage);
    }
}