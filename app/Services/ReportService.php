<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public const PER_PAGE = 25;

    /**
     * Ceiling on how many rows a single report pulls into memory.
     *
     * Totals are computed at the database level, so this bounds the result set
     * without making the headline figures inaccurate.
     */
    public const REPORT_ROW_LIMIT = 1000;

    public const STOCK_STATUS_ALL = 'all';

    public const STOCK_STATUS_LOW = 'low';

    public const STOCK_STATUS_OUT = 'out';

    public const STOCK_STATUS_SAFE = 'safe';

    /**
     * Accepted alias for the "Safe / OK" bucket.
     */
    public const STOCK_STATUS_OK = 'ok';

    /**
     * Filter dropdown options, so controllers do not touch the ORM directly.
     */
    public function categoryOptions(): Collection
    {
        return Category::query()->orderBy('name')->get(['id', 'name']);
    }

    public function supplierOptions(): Collection
    {
        return Supplier::query()->orderBy('name')->get(['id', 'name']);
    }

    public function userOptions(): Collection
    {
        return User::query()->with('role')->orderBy('name')->get(['id', 'name', 'role_id']);
    }

    /**
     * Distinct top-level action slugs, for the activity filter dropdown.
     *
     * @return array<int, string>
     */
    public function actionPrefixes(): array
    {
        return ActivityLog::query()
            ->select('action')
            ->distinct()
            ->pluck('action')
            ->map(fn (string $action) => explode('.', $action)[0])
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Catalogue report: stock position filtered by category, period and
     * whether an item has reached its reorder point.
     *
     * @param  array<string, mixed>  $filters
     * @return array{rows:SupportCollection,filters:array<string,mixed>,totals:array<string,mixed>}
     */
    public function stockReport(array $filters): array
    {
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;

        $query = Product::query()
            ->with(['category', 'supplier'])
            ->when($filters['category_id'] ?? null, fn (Builder $q, $id) => $q->where('category_id', $id))
            ->when($filters['supplier_id'] ?? null, fn (Builder $q, $id) => $q->where('supplier_id', $id));

        // The catalogue is a point-in-time view, so a period cannot filter which
        // products appear. It instead adds the movement totals for that window,
        // which is what makes the period useful on a stock report.
        if (filled($from) || filled($to)) {
            $query
                ->withSum([
                    'stockTransactions as period_in' => fn (Builder $q) => $q->ofType('IN')->createdBetween($from, $to),
                ], 'quantity')
                ->withSum([
                    'stockTransactions as period_out' => fn (Builder $q) => $q->ofType('OUT')->createdBetween($from, $to),
                ], 'quantity');
        }

        match ($filters['stock_status'] ?? self::STOCK_STATUS_ALL) {
            self::STOCK_STATUS_LOW => $query->lowStockOnly(),
            self::STOCK_STATUS_OUT => $query->outOfStock(),
            self::STOCK_STATUS_SAFE, self::STOCK_STATUS_OK => $query->safeStock(),
            default => $query,
        };

        $query->search($filters['search'] ?? null)->orderBy('name');

        $rows = (clone $query)->limit(self::REPORT_ROW_LIMIT)->get();

        return [
            'rows' => $rows,
            'filters' => $filters,
            'totals' => $this->stockTotals(clone $query),
        ];
    }

    /**
     * Goods receipt / issue report filtered by date range, supplier and status.
     *
     * stock_transactions has no supplier column of its own, so supplier is
     * resolved through the related product rather than a direct column filter.
     *
     * @param  array<string, mixed>  $filters
     * @return array{rows:SupportCollection,filters:array<string,mixed>,totals:array<string,mixed>}
     */
    public function transactionReport(array $filters): array
    {
        $query = StockTransaction::query()
            ->with(['product.category', 'product.supplier', 'user', 'approver'])
            ->createdBetween($filters['from'] ?? null, $filters['to'] ?? null)
            ->when(
                $filters['supplier_id'] ?? null,
                fn (Builder $q, $id) => $q->whereHas(
                    'product',
                    fn (Builder $p) => $p->where('supplier_id', $id)
                )
            )
            ->when($filters['status'] ?? null, fn (Builder $q, $s) => $q->where('status', $s))
            ->when($filters['type'] ?? null, fn (Builder $q, $t) => $q->ofType($t))
            ->when(
                $filters['category_id'] ?? null,
                fn (Builder $q, $id) => $q->whereHas('product', fn (Builder $p) => $p->where('category_id', $id))
            )
            ->when(
                $filters['search'] ?? null,
                fn (Builder $q, $term) => $q->whereHas('product', fn (Builder $p) => $p->search($term))
            )
            ->orderByDesc('created_at');

        $rows = (clone $query)->limit(self::REPORT_ROW_LIMIT)->get();

        return [
            'rows' => $rows,
            'filters' => $filters,
            'totals' => $this->transactionTotals(clone $query),
        ];
    }

    /**
     * Activity log report filtered by date range, user and action prefix.
     *
     * @param  array<string, mixed>  $filters
     * @return array{rows:SupportCollection,filters:array<string,mixed>,totals:array<string,mixed>}
     */
    public function activityLogReport(array $filters): array
    {
        $query = ActivityLog::query()
            ->with('user')
            ->createdBetween($filters['from'] ?? null, $filters['to'] ?? null)
            ->when($filters['user_id'] ?? null, fn (Builder $q, $id) => $q->where('user_id', $id))
            ->action($filters['action'] ?? null)
            ->when($filters['search'] ?? null, function (Builder $q, $term) {
                // '!' escape rather than '\': MySQL treats a backslash inside a
                // string literal as an escape, SQLite has no default one.
                $escaped = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim($term)).'%';
                $q->whereRaw("description LIKE ? ESCAPE '!'", [$escaped]);
            })
            ->orderByDesc('created_at');

        $rows = (clone $query)->limit(self::REPORT_ROW_LIMIT)->get();

        return [
            'rows' => $rows,
            'filters' => $filters,
            'totals' => ['entries' => (clone $query)->count()],
        ];
    }

    /**
     * Headline figures, aggregated by the database.
     *
     * These run against the filtered query rather than over the fetched rows,
     * so they stay correct even when REPORT_ROW_LIMIT caps the result set, and
     * money is summed by the engine instead of in PHP floats.
     *
     * @return array<string, mixed>
     */
    protected function stockTotals(Builder $query): array
    {
        return [
            'products' => (clone $query)->count(),
            'units_on_hand' => (int) (clone $query)->sum('current_stock'),
            'stock_value' => (float) (clone $query)->sum(DB::raw('current_stock * buy_price')),
            'low_stock' => (clone $query)->lowStockOnly()->count(),
            'out_of_stock' => (clone $query)->outOfStock()->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function transactionTotals(Builder $query): array
    {
        return [
            'transactions' => (clone $query)->count(),
            'in_count' => (clone $query)->where('type', 'IN')->count(),
            'in_quantity' => (int) (clone $query)->where('type', 'IN')->sum('quantity'),
            'out_count' => (clone $query)->where('type', 'OUT')->count(),
            'out_quantity' => (int) (clone $query)->where('type', 'OUT')->sum('quantity'),
            'pending' => (clone $query)->where('status', 'PENDING')->count(),
            'rejected' => (clone $query)->where('status', 'REJECTED')->count(),
        ];
    }
}