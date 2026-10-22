<?php

namespace App\Repositories\Eloquent;

use App\Models\StockTransaction;
use App\Repositories\Contracts\StockTransactionRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class EloquentStockTransactionRepository extends BaseEloquentRepository implements StockTransactionRepository
{
    public function __construct()
    {
        parent::__construct(StockTransaction::class);
    }

    public function findByReference(string $reference): ?StockTransaction
    {
        return $this->modelClass::query()
            ->where('reference_number', $reference)
            ->first();
    }

    public function referenceExists(string $reference): bool
    {
        return $this->modelClass::query()
            ->where('reference_number', $reference)
            ->exists();
    }

    public function nextDailySequence(string $type, string $date): int
    {
        $day = Carbon::createFromFormat('Ymd', $date)->startOfDay();

        return (int) $this->modelClass::query()
            ->where('type', $type)
            ->whereBetween('created_at', [$day, $day->copy()->endOfDay()])
            ->count() + 1;
    }

    public function paginatedWithRelations(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->modelClass::query()
            ->with(['product', 'user', 'approver']);

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

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

        return $query->orderByDesc('created_at')
            ->paginate($perPage);
    }
}