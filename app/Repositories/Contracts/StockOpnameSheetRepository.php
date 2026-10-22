<?php

namespace App\Repositories\Contracts;

use App\Models\StockOpnameSheet;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface StockOpnameSheetRepository extends BaseRepositoryInterface
{
    public function findByReference(string $reference): ?StockOpnameSheet;

    public function referenceExists(string $reference): bool;

    /**
     * Sheets already opened on a given day, used to build the next reference.
     */
    public function nextDailySequence(string $date): int;

    /**
     * History listing with the per-sheet totals already folded in as SQL
     * aggregates, so the table never issues one query per row.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginatedWithTotals(int $perPage = 15, array $filters = []): LengthAwarePaginator;
}