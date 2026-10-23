<?php

namespace App\Repositories\Contracts;

use App\Models\StockTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface StockTransactionRepository extends BaseRepositoryInterface
{
    public function findByReference(string $reference): ?StockTransaction;

    public function referenceExists(string $reference): bool;

    /**
     * Number of transactions already recorded for a type on a given day.
     */
    public function nextDailySequence(string $type, string $date): int;

    /**
     * Number of adjustment rows already recorded on a given day.
     */
    public function nextAdjustmentSequence(string $date): int;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginatedWithRelations(int $perPage = 15, array $filters = []): LengthAwarePaginator;
}
