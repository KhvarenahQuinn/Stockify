<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\StockOpname;
use App\Models\StockTransaction;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public const ROLE_ADMIN = 'admin';

    public const ROLE_MANAGER = 'warehouse_manager';

    public const ROLE_STAFF = 'warehouse_staff';

    public const DEFAULT_CHART_MONTHS = 12;

    public const MAX_CHART_MONTHS = 24;

    public function __construct(
        protected readonly LowStockService $lowStockService,
    ) {
    }

    /**
     * Metrics tailored to the signed-in user's role.
     *
     * @return array<string, mixed>
     */
    public function forUser(?User $user): array
    {
        return match ($user?->role?->name) {
            self::ROLE_ADMIN => $this->adminMetrics(),
            self::ROLE_MANAGER => $this->managerMetrics(),
            self::ROLE_STAFF => $this->staffMetrics(),
            default => $this->guestMetrics(),
        };
    }

    /**
     * Admin: catalogue size, this month's movement totals, user count and the
     * system-wide activity feed.
     *
     * @return array<string, mixed>
     */
    public function adminMetrics(int $activityLimit = 10): array
    {
        $monthStart = Carbon::now()->startOfMonth();

        return [
            'role' => self::ROLE_ADMIN,
            'total_products' => Product::query()->count(),
            // Provisioned accounts and accounts that can actually sign in are
            // different questions, and an admin reading "Users" wants the
            // second one. Counting every row made a deprovisioned account look
            // like live access.
            'user_count' => User::query()->count(),
            'active_user_count' => User::query()->where('status', 'active')->count(),
            'monthly_in_total' => (int) StockTransaction::query()
                ->ofType('IN')
                ->where('created_at', '>=', $monthStart)
                ->sum('quantity'),
            'monthly_out_total' => (int) StockTransaction::query()
                ->ofType('OUT')
                ->where('created_at', '>=', $monthStart)
                ->sum('quantity'),
            'transaction_in_count' => StockTransaction::query()->ofType('IN')->count(),
            'transaction_out_count' => StockTransaction::query()->ofType('OUT')->count(),
            'total_stock_value' => $this->totalStockValue(),
            'pending_approvals' => StockTransaction::query()->pending()->count(),
            'activity' => ActivityLog::query()
                ->with('user')
                ->orderByDesc('created_at')
                ->limit($activityLimit)
                ->get(),
        ];
    }

    /**
     * Stock on hand valued at purchase price.
     *
     * Computed in the database rather than by summing a loaded collection: a
     * few thousand products would otherwise be pulled into memory to add up a
     * number the SQL engine already has.
     */
    public function totalStockValue(): float
    {
        return (float) Product::query()
            ->selectRaw('COALESCE(SUM(current_stock * buy_price), 0) as total')
            ->value('total');
    }

    /**
     * Warehouse Manager: replenishment pressure and today's throughput plus
     * stock counts still awaiting verification.
     *
     * @return array<string, mixed>
     */
    public function managerMetrics(int $taskLimit = 5): array
    {
        $lowStock = $this->lowStockService->summary();

        return [
            'role' => self::ROLE_MANAGER,
            'low_stock_count' => $lowStock['low_stock'],
            'out_of_stock_count' => $lowStock['out_of_stock'],
            'low_stock_items' => $lowStock['items'],
            'today_in_count' => StockTransaction::query()->ofType('IN')->today()->count(),
            'today_out_count' => StockTransaction::query()->ofType('OUT')->today()->count(),
            'pending_opnames' => $this->pendingOpnames($taskLimit),
            'pending_opname_count' => StockOpname::query()
                ->where('status', StockOpnameService::STATUS_DRAFT)
                ->count(),
            'pending_approvals' => StockTransaction::query()->pending()->count(),
            // The preview table needs the movements themselves, not just a
            // count: a manager deciding whether to clear the queue has to see
            // what is in it before clicking through.
            'pending_approval_items' => $this->pendingMovements(null, $taskLimit),
        ];
    }

    /**
     * Warehouse Staff: the two task queues they can actually act on.
     *
     * @return array<string, mixed>
     */
    public function staffMetrics(int $taskLimit = 5): array
    {
        return [
            'role' => self::ROLE_STAFF,
            'pending_incoming' => $this->pendingMovements('IN', $taskLimit),
            'pending_incoming_count' => $this->pendingMovements('IN', 0)->count(),
            'pending_outgoing' => $this->pendingMovements('OUT', $taskLimit),
            'pending_outgoing_count' => $this->pendingMovements('OUT', 0)->count(),
        ];
    }

    /**
     * Fallback for a role with no widget set of its own.
     *
     * @return array<string, mixed>
     */
    public function guestMetrics(): array
    {
        return ['role' => null];
    }

    /**
     * Monthly stock movement totals for the last N months, shaped for both
     * ApexCharts and Chart.js.
     *
     * @return array<string, mixed>
     */
    public function stockMovementChart(int $months = self::DEFAULT_CHART_MONTHS): array
    {
        $months = max(1, min($months, self::MAX_CHART_MONTHS));
        $periods = $this->periodKeys($months);
        $start = Carbon::parse($periods[0].'-01')->startOfMonth();

        $rows = DB::table('stock_transactions')
            ->selectRaw($this->periodExpression().' as period, type, SUM(quantity) as total')
            ->where('created_at', '>=', $start)
            ->groupBy('period', 'type')
            ->get();

        return $this->buildChartPayload($rows, $periods);
    }

    /**
     * Assemble a chart payload from grouped rows.
     *
     * Kept free of database access so the series/label alignment is testable
     * on its own: months with no movement must still occupy a slot, otherwise
     * the series and the categories drift out of alignment.
     *
     * @param  iterable<int, object>  $rows
     * @param  array<int, string>  $periods  'Y-m' keys, oldest first
     * @return array<string, mixed>
     */
    public function buildChartPayload(iterable $rows, array $periods): array
    {
        $in = array_fill(0, count($periods), 0);
        $out = array_fill(0, count($periods), 0);
        $index = array_flip($periods);

        foreach ($rows as $row) {
            $period = (string) ($row->period ?? '');

            if (! isset($index[$period])) {
                continue; // Outside the requested window.
            }

            $position = $index[$period];
            $total = (float) ($row->total ?? 0);

            if (strtoupper((string) $row->type) === 'IN') {
                $in[$position] = $total;
            } else {
                $out[$position] = $total;
            }
        }

        $labels = array_map(
            fn (string $period) => Carbon::parse($period.'-01')->translatedFormat('M Y'),
            $periods
        );

        return [
            'apexcharts' => [
                'type' => 'bar',
                'height' => 320,
                'series' => [
                    ['name' => __('Stock In'), 'data' => $in],
                    ['name' => __('Stock Out'), 'data' => $out],
                ],
                'categories' => $labels,
                /**
                 * Flowbite's palette, not Tailwind's defaults, and the only
                 * shades of these two hues that clear 3:1 against BOTH the
                 * white card and the gray-800 card:
                 *
                 *   green-500  #0E9F6E   3.39:1 light / 4.34:1 dark
                 *   orange-500 #FF5A1F   3.12:1 light / 4.71:1 dark
                 *
                 * green-600 (5.36/2.74) and orange-600 (4.94/2.97) read well on
                 * white and then disappear into the dark card, which is exactly
                 * the failure this chart exists to make obvious.
                 */
                'colors' => ['#0E9F6E', '#FF5A1F'],
            ],
            'chartjs' => [
                'labels' => $labels,
                'datasets' => [
                    ['label' => __('Stock In'), 'data' => $in],
                    ['label' => __('Stock Out'), 'data' => $out],
                ],
            ],
            'totals' => [
                'in' => array_sum($in),
                'out' => array_sum($out),
            ],
        ];
    }

    /**
     * 'Y-m' keys for the trailing N months, oldest first.
     *
     * @return array<int, string>
     */
    protected function periodKeys(int $months): array
    {
        $keys = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $keys[] = Carbon::now()->subMonths($i)->format('Y-m');
        }

        return $keys;
    }

    /**
     * Pending movements of one direction, newest first.
     */
    protected function pendingMovements(?string $type, int $limit): \Illuminate\Database\Eloquent\Collection
    {
        $query = StockTransaction::query()
            ->pending()
            ->when($type, fn ($q) => $q->ofType($type))
            ->with(['product'])
            ->orderByDesc('created_at');

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Draft stock counts awaiting manager verification.
     */
    protected function pendingOpnames(int $limit): \Illuminate\Database\Eloquent\Collection
    {
        $query = StockOpname::query()
            ->where('status', StockOpnameService::STATUS_DRAFT)
            ->with(['product', 'user'])
            ->orderByDesc('created_at');

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Driver-aware month grouping, so the chart query works on MySQL,
     * PostgreSQL and SQLite alike.
     */
    protected function periodExpression(): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', created_at)",
            'pgsql' => "to_char(created_at, 'YYYY-MM')",
            default => "DATE_FORMAT(created_at, '%Y-%m')",
        };
    }
}