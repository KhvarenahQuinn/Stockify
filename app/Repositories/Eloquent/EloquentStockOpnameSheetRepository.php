<?php

namespace App\Repositories\Eloquent;

use App\Models\StockOpnameSheet;
use App\Repositories\Contracts\StockOpnameSheetRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class EloquentStockOpnameSheetRepository extends BaseEloquentRepository implements StockOpnameSheetRepository
{
    public function __construct()
    {
        parent::__construct(StockOpnameSheet::class);
    }

    public function findByReference(string $reference): ?StockOpnameSheet
    {
        return $this->modelClass::query()
            ->where('reference', $reference)
            ->first();
    }

    public function referenceExists(string $reference): bool
    {
        return $this->modelClass::query()
            ->where('reference', $reference)
            ->exists();
    }

    public function nextDailySequence(string $date): int
    {
        return (int) $this->modelClass::query()
            ->whereDate('audit_date', Carbon::createFromFormat('Ymd', $date)->toDateString())
            ->count() + 1;
    }

    public function paginatedWithTotals(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->modelClass::query()
            ->with('auditor')
            ->withCount('items as total_items')
            ->withCount(['items as total_matched' => fn ($q) => $q->where('variance', 0)])
            ->withSum('items as total_net_variance', 'variance')
            ->withSum(['items as total_deficit' => fn ($q) => $q->where('variance', '<', 0)], 'variance')
            ->withSum(['items as total_surplus' => fn ($q) => $q->where('variance', '>', 0)], 'variance');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['auditor_id'])) {
            $query->where('auditor_id', $filters['auditor_id']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('audit_date', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('audit_date', '<=', $filters['to']);
        }

        // Variance status. Matched means every line came out even, which is not
        // the same as "no items": an empty sheet has nothing to match.
        if (($filters['variance_status'] ?? null) === 'matched') {
            $query->whereHas('items', fn ($q) => $q->where('variance', 0))
                ->whereDoesntHave('items', fn ($q) => $q->where('variance', '!=', 0));
        }

        if (($filters['variance_status'] ?? null) === 'discrepancy') {
            $query->whereHas('items', fn ($q) => $q->where('variance', '!=', 0));
        }

        return $query->orderByDesc('audit_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}