<?php

namespace App\Http\Controllers;

use App\Exceptions\DomainException;
use App\Http\Requests\InspectStockTransactionRequest;
use App\Http\Requests\ReviewStockTransactionRequest;
use App\Http\Requests\StoreStockAdjustmentRequest;
use App\Http\Requests\StoreStockTransactionRequest;
use App\Models\StockTransaction;
use App\Services\StockTransactionService;
use App\Services\TransactionDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StockTransactionController extends Controller
{
    public const INDEX_PER_PAGE = 15;

    public const QUEUE_PER_PAGE = 15;

    /**
     * Lenses offered by the two queues. Kept here so the view and the service
     * agree on the vocabulary without either inventing it.
     */
    public const LENSES = ['all', 'inspection', 'approval', 'discrepancy'];

    /**
     * Display the transaction history with optional filters.
     */
    public function index(Request $request, StockTransactionService $service): View
    {
        $filters = $request->only(['type', 'status', 'product_id', 'from', 'to']);

        $transactions = $service->listTransactions(self::INDEX_PER_PAGE, array_filter($filters));

        return view('transactions.index', [
            'transactions' => $transactions,
            'filters' => $filters,
            'types' => [StockTransactionService::TYPE_IN, StockTransactionService::TYPE_OUT],
            'statuses' => [
                StockTransactionService::STATUS_PENDING,
                StockTransactionService::STATUS_APPROVED,
                StockTransactionService::STATUS_REJECTED,
                StockTransactionService::STATUS_COMPLETED,
            ],
        ]);
    }

    /**
     * Show the form for submitting a movement request.
     */
    public function create(StockTransactionService $service): View
    {
        return view('transactions.create', [
            'products' => $service->productsForMovement(),
            'suppliers' => $service->suppliersForMovement(),
            'storeAction' => route('stock-transactions.store'),
        ]);
    }

    /**
     * Store a new PENDING movement request.
     */
    public function store(StoreStockTransactionRequest $request, StockTransactionService $service): RedirectResponse
    {
        try {
            $transaction = $service->createTransaction($request->validated());
        } catch (DomainException $e) {
            return back()
                ->withInput()
                // $errors is a readonly property on DomainException, not a
                // method — it carries the field-level messages that let the
                // form put the complaint next to the box that caused it.
                ->withErrors($e->errors)
                ->with('error', $e->getMessage());
        }

        return redirect()->route('stock-transactions.show', $transaction)
            ->with('success', __(':reference submitted. It now sits in the inspection queue.', ['reference' => $transaction->reference_number]));
    }

    /**
     * Form for a manual stock correction.
     */
    public function createAdjustment(StockTransactionService $service): View
    {
        $this->authorize('adjust', StockTransaction::class);

        return view('transactions.adjustment', [
            'products' => $service->productsForMovement(),
            'selectedProductId' => request()->integer('product') ?: null,
            'storeAction' => route('stock-transactions.adjustments.store'),
        ]);
    }

    /**
     * Apply a manual correction immediately and record it in the ledger.
     */
    public function storeAdjustment(StoreStockAdjustmentRequest $request, StockTransactionService $service): RedirectResponse
    {
        try {
            $transaction = $service->adjustStock($request->validated());
        } catch (DomainException $e) {
            return back()
                ->withInput()
                ->withErrors($e->errors)
                ->with('error', $e->getMessage());
        }

        return redirect()->route('stock-transactions.show', $transaction)
            ->with('success', __(':reference adjustment applied. Stock moved from :before to :after.', [
                'reference' => $transaction->reference_number,
                'before' => $transaction->stock_before,
                'after' => $transaction->stock_after,
            ]));
    }

    /**
     * The floor queue: requests waiting for a physical count.
     */
    public function staffQueue(Request $request, StockTransactionService $service): View
    {
        $filters = $request->only(['type', 'from', 'to']);

        return view('transactions.staff-queue', [
            'queue' => $service->listPending(self::QUEUE_PER_PAGE, $this->queueFilters($request)),
            'filters' => $filters,
            'lens' => $this->resolveLens($request),
            'counts' => $service->pendingCounts(),
            'canInspect' => $request->user()?->can('inspect', StockTransaction::class) ?? false,
            // Route::has() alone is not enough for a cross-role link: the
            // approvals route exists for everyone, but only managers pass the
            // role middleware, so staff would get a button that 403s.
            'canReview' => $request->user()?->can('reviewQueue', StockTransaction::class) ?? false,
        ]);
    }

    /**
     * Record a physical count against a submitted request.
     */
    public function inspect(int $id, InspectStockTransactionRequest $request, StockTransactionService $service): RedirectResponse
    {
        $this->authorizeAction($request, 'inspect');

        try {
            $transaction = $service->inspect($id, $request->validated());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with(
            'success',
            $transaction->hasDiscrepancy()
                ? __('Counted :counted against a request of :requested. A manager has to decide what happens next.', [
                    'counted' => $transaction->inspected_quantity,
                    'requested' => $transaction->quantity,
                ])
                : __('Count matches. :reference is with a manager for approval.', ['reference' => $transaction->reference_number])
        );
    }

    /**
     * The manager queue: every request still waiting on a decision.
     */
    public function approvals(Request $request, StockTransactionService $service): View
    {
        $this->authorizeAction($request, 'reviewQueue');

        return view('transactions.approvals', [
            'queue' => $service->listPending(self::QUEUE_PER_PAGE, $this->queueFilters($request)),
            'filters' => $request->only(['type', 'from', 'to']),
            'lens' => $this->resolveLens($request),
            'counts' => $service->pendingCounts(),
        ]);
    }

    /**
     * Display a single transaction.
     */
    public function show(int $id, StockTransactionService $service): View
    {
        return view('transactions.show', [
            'transaction' => $service->findTransaction($id),
        ]);
    }

    /**
     * Stream an attached delivery note through the authenticated route.
     */
    public function document(int $id, StockTransactionService $service): StreamedResponse
    {
        $transaction = $service->findTransaction($id);
        $path = $transaction->document_path;

        abort_if($path === null || $path === '', 404);

        $disk = Storage::disk(TransactionDocumentService::DISK);
        abort_unless($disk->exists($path), 404);

        return $disk->download($path, $transaction->document_name, [
            'Content-Type' => $disk->mimeType($path) ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Approve a pending movement and post the stock change.
     */
    public function approve(int $id, ReviewStockTransactionRequest $request, StockTransactionService $service): RedirectResponse
    {
        try {
            $transaction = $service->approve($id, $request->validated('notes'));
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('stock-transactions.index')
            ->with('success', __(':reference approved. Stock :before → :after.', ['reference' => $transaction->reference_number, 'before' => $transaction->stock_before, 'after' => $transaction->stock_after]));
    }

    /**
     * Reject a pending movement.
     */
    public function reject(int $id, ReviewStockTransactionRequest $request, StockTransactionService $service): RedirectResponse
    {
        try {
            $transaction = $service->reject($id, $request->validated('notes'));
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        $submitter = $transaction->user?->name ?? __('the submitter');

        return redirect()->route('stock-transactions.index')
            ->with('success', __(':reference rejected and sent back to :submitter.', ['reference' => $transaction->reference_number, 'submitter' => $submitter]));
    }

    /**
     * Finalise an approved movement.
     */
    public function complete(int $id, Request $request, StockTransactionService $service): RedirectResponse
    {
        $this->authorizeAction($request, 'complete');

        try {
            $transaction = $service->complete($id);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __(':reference closed as completed.', ['reference' => $transaction->reference_number]));
    }

    /**
     * Queue filters shared by both queues, with the lens validated against the
     * known set so a hand-edited query string cannot reach the service.
     *
     * @return array<string, mixed>
     */
    protected function queueFilters(Request $request): array
    {
        $filters = $request->only(['type', 'from', 'to']);
        $filters['lens'] = $this->resolveLens($request);

        return array_filter($filters);
    }

    protected function resolveLens(Request $request): string
    {
        $lens = (string) $request->query('lens', 'all');

        return in_array($lens, self::LENSES, true) ? $lens : 'all';
    }

    /**
     * Enforce the TransactionPolicy for the custom action abilities.
     */
    protected function authorizeAction(Request $request, string $ability): void
    {
        abort_unless(
            $request->user()?->can($ability, StockTransaction::class) ?? false,
            403,
            __('You are not allowed to perform this action.')
        );
    }
}
