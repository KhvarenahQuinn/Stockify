<?php

namespace App\Repositories\Contracts;

use App\Models\StockOpname;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface StockOpnameRepository extends BaseRepositoryInterface
{
    public function findByNumber(string $number): ?StockOpname;

    public function numberExists(string $number): bool;

    /**
     * Number of opnames already recorded on a given day.
     */
    public function nextDailySequence(string $date): int;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginatedWithRelations(int $perPage = 15, array $filters = []): LengthAwarePaginator;
}