<?php

namespace App\Http\Controllers;

use App\Exports\ActivityLogReportExport;
use App\Exports\StockReportExport;
use App\Exports\TransactionReportExport;
use App\Models\ActivityLog;
use App\Models\StockTransaction;
use App\Services\ReportService;
use App\Services\SettingService;
use App\Services\StockTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ReportController extends Controller
{
    /**
     * Report filter keys accepted from the query string.
     *
     * @var array<int, string>
     */
    protected const STOCK_FILTERS = ['category_id', 'supplier_id', 'stock_status', 'search', 'from', 'to', 'start_date', 'end_date'];

    /**
     * @var array<int, string>
     */
    protected const TRANSACTION_FILTERS = ['from', 'to', 'start_date', 'end_date', 'supplier_id', 'status', 'type', 'category_id', 'search'];

    /**
     * @var array<int, string>
     */
    protected const ACTIVITY_FILTERS = ['from', 'to', 'user_id', 'action', 'search'];

    /**
     * A landing page listing the reports.
     *
     * This used to redirect to the stock report, which meant the only way to
     * reach the activity log was to know its URL.
     */
    public function index(): View
    {
        return view('reports.index', [
            'reports' => [
                [
                    'route' => 'reports.stock',
                    'title' => __('Stock balances'),
                    'description' => __('What is on hand for every product, its value, and which lines are low.'),
                    'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                ],
                [
                    'route' => 'reports.transactions',
                    'title' => __('Goods movements'),
                    'description' => __('Every receipt and issue, with its quantity, direction and approval state.'),
                    'icon' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
                ],
                [
                    'route' => 'reports.activity',
                    'title' => __('User activity'),
                    'description' => __('Who changed what, and when, across the whole application.'),
                    'icon' => 'M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z',
                ],
            ],
        ]);
    }

    /**
     * Catalogue stock report.
     */
    public function stock(Request $request, ReportService $reports, SettingService $settings): View
    {
        $report = $reports->stockReport($this->filters($request, self::STOCK_FILTERS));

        return view('reports.stock', [
            'report' => $report,
            'appName' => $settings->appName(),
            'categories' => $reports->categoryOptions(),
            'suppliers' => $reports->supplierOptions(),
            'stockStatuses' => [
                ReportService::STOCK_STATUS_ALL => 'All statuses',
                ReportService::STOCK_STATUS_LOW => 'Low stock',
                ReportService::STOCK_STATUS_OUT => 'Out of stock',
                ReportService::STOCK_STATUS_SAFE => 'Safe',
            ],
        ]);
    }

    /**
     * Goods receipt / issue report.
     */
    public function transactions(Request $request, ReportService $reports, SettingService $settings): View
    {
        $report = $reports->transactionReport($this->filters($request, self::TRANSACTION_FILTERS));

        return view('reports.transactions', [
            'report' => $report,
            'appName' => $settings->appName(),
            'suppliers' => $reports->supplierOptions(),
            'categories' => $reports->categoryOptions(),
            'statuses' => [
                StockTransactionService::STATUS_PENDING,
                StockTransactionService::STATUS_APPROVED,
                StockTransactionService::STATUS_REJECTED,
                StockTransactionService::STATUS_COMPLETED,
            ],
            'types' => [StockTransactionService::TYPE_IN, StockTransactionService::TYPE_OUT],
        ]);
    }

    /**
     * User activity log report.
     */
    public function activity(Request $request, ReportService $reports, SettingService $settings): View
    {
        $report = $reports->activityLogReport($this->activityFilters($request));

        return view('reports.activity', [
            'report' => $report,
            'appName' => $settings->appName(),
            'users' => $reports->userOptions(),
            'actionPrefixes' => $reports->actionPrefixes(),
        ]);
    }

    public function exportStock(Request $request, ReportService $reports): SymfonyResponse
    {
        $filters = $this->filters($request, self::STOCK_FILTERS);
        $report = $reports->stockReport($filters);

        $withPeriod = filled($filters['from'] ?? null) || filled($filters['to'] ?? null);

        return $this->respond(
            $request,
            new StockReportExport($report['rows'], $withPeriod),
            'stock-report'
        );
    }

    public function exportTransactions(Request $request, ReportService $reports): SymfonyResponse
    {
        $report = $reports->transactionReport($this->filters($request, self::TRANSACTION_FILTERS));

        return $this->respond(
            $request,
            new TransactionReportExport($report['rows']),
            'transaction-report'
        );
    }

    public function exportActivity(Request $request, ReportService $reports): SymfonyResponse
    {
        $report = $reports->activityLogReport($this->activityFilters($request));

        return $this->respond(
            $request,
            new ActivityLogReportExport($report['rows']),
            'activity-report'
        );
    }

    /**
     * Send the export as a PDF render or a spreadsheet download.
     */
    protected function respond(Request $request, object $export, string $slug): SymfonyResponse
    {
        $format = strtolower($request->query('format', 'xlsx'));
        $stamp = Carbon::now()->format('Ymd-His');

        if ($format === 'pdf') {
            $view = match (true) {
                $export instanceof StockReportExport => 'reports.pdf.stock',
                $export instanceof TransactionReportExport => 'reports.pdf.transactions',
                default => 'reports.pdf.activity',
            };

            $pdf = app(\Barryvdh\DomPDF\PDF::class)
                ->loadView($view, [
                    'export' => $export,
                    'rows' => $export->collection(),
                    'appName' => app(SettingService::class)->appName(),
                    'generatedAt' => Carbon::now(),
                ])
                ->setPaper('a4', 'landscape');

            return $pdf->download("{$slug}-{$stamp}.pdf");
        }

        return Excel::download($export, "{$slug}-{$stamp}.xlsx");
    }

    /**
     * Keep only the whitelisted filter keys, dropping blanks.
     *
     * @param  array<int, string>  $keys
     * @return array<string, mixed>
     */
    protected function filters(Request $request, array $keys): array
    {
        $filters = array_filter(
            $request->only($keys),
            fn ($value) => $value !== null && $value !== ''
        );

        // The date range controls post start_date and end_date; the report
        // service has always spoken in from and to. The mapping happens here so
        // the service, the stored filters and both export routes keep working
        // unchanged - and so an old bookmarked from= link still works.
        if (! isset($filters['from']) && $request->filled('start_date')) {
            $filters['from'] = $request->query('start_date');
        }

        if (! isset($filters['to']) && $request->filled('end_date')) {
            $filters['to'] = $request->query('end_date');
        }

        return $filters;
    }

    /**
     * Staff only see their own audit entries. Managers and admins can filter
     * the full trail; a request-supplied user_id must not widen a staff view.
     *
     * @return array<string, mixed>
     */
    protected function activityFilters(Request $request): array
    {
        $filters = $this->filters($request, self::ACTIVITY_FILTERS);

        if (! $request->user()?->can('viewAllActivity', ActivityLog::class)) {
            $filters['user_id'] = $request->user()?->id;
        }

        return $filters;
    }
}
