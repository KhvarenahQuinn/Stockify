<?php

namespace App\Services;

use App\Exceptions\DomainException;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\Supplier;
use App\Repositories\Contracts\StockTransactionRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StockTransactionService extends BaseService
{
    public const TYPE_IN = 'IN';

    public const TYPE_OUT = 'OUT';

    // Aliased to the model rather than redeclared: the persisted enum is one
    // thing, and a second set of literals is a second thing to keep in step.
    public const STATUS_PENDING = StockTransaction::STATUS_PENDING;

    public const STATUS_APPROVED = StockTransaction::STATUS_APPROVED;

    public const STATUS_REJECTED = StockTransaction::STATUS_REJECTED;

    public const STATUS_COMPLETED = StockTransaction::STATUS_COMPLETED;

    public const REFERENCE_SEQUENCE_PAD = 3;

    protected readonly StockTransactionRepository $stockTransactionRepository;

    public function __construct(
        StockTransactionRepository $repository,
        protected readonly ActivityLoggerService $activityLogger,
        protected readonly TransactionDocumentService $documents,
    ) {
        parent::__construct($repository);
        $this->stockTransactionRepository = $repository;
    }

    /**
     * Paginated ledger with optional filters (type, status, product, date range).
     *
     * @param  array<string, mixed>  $filters
     */
    public function listTransactions(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->stockTransactionRepository->paginatedWithRelations($perPage, $filters);
    }

    /**
     * Requests still open to a decision, for the staff inspection queue and the
     * manager approval queue.
     *
     * Both screens read the same rows; only the emphasis differs, so the
     * filtering lives here rather than being re-derived per controller.
     *
     * @param  array<string, mixed>  $filters  'lens' => inspection|approval|discrepancy|all
     */
    public function listPending(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $lens = $filters['lens'] ?? 'all';

        $query = StockTransaction::query()->with([
            'product',
            'user',
            'inspector',
            'supplier',
        ]);

        match ($lens) {
            'inspection' => $query->awaitingInspection(),
            'approval' => $query->awaitingApproval(),
            'discrepancy' => $query->withDiscrepancy(),
            default => $query->where('status', self::STATUS_PENDING),
        };

        if (! empty($filters['type'])) {
            $query->where('type', strtoupper($filters['type']));
        }

        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        // Oldest first: an item nobody has looked at is the one that hurts.
        return $query->orderBy('created_at')->paginate($perPage);
    }

    /**
     * Counts backing the queue headers, so a screen can say "4 waiting" without
     * the view guessing at it.
     *
     * @return array<string, int>
     */
    public function pendingCounts(): array
    {
        return [
            'all' => StockTransaction::query()->where('status', self::STATUS_PENDING)->count(),
            'inspection' => StockTransaction::query()->awaitingInspection()->count(),
            'approval' => StockTransaction::query()->awaitingApproval()->count(),
            'discrepancy' => StockTransaction::query()->withDiscrepancy()->count(),
        ];
    }

    public function findTransaction(int $id): StockTransaction
    {
        /** @var StockTransaction $transaction */
        $transaction = $this->stockTransactionRepository->findOrFail($id);

        return $transaction->load(['product', 'user', 'approver', 'inspector', 'supplier']);
    }

    /**
     * Record a new movement request. Status is always PENDING here: staff submit,
     * and only a manager or admin moves it forward.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createTransaction(array $attributes): StockTransaction
    {
        $type = strtoupper((string) $attributes['type']);

        if (! in_array($type, [self::TYPE_IN, self::TYPE_OUT], true)) {
            throw new DomainException(__('Transaction type must be either IN or OUT.'));
        }

        // Existence is validated by the form request; this guards direct service use.
        /** @var Product $product */
        $product = Product::query()->findOrFail((int) $attributes['product_id']);

        // The form guards this in the browser, but a direct POST or an API client
        // skips that. Catching it here keeps the queue free of requests that are
        // certain to fail at approval time.
        $quantity = (int) $attributes['quantity'];

        if ($type === self::TYPE_OUT && $quantity > (int) $product->current_stock) {
            throw new DomainException(
                __('Only :stock :unit(s) of :product are available.', [
                    'stock' => $product->current_stock,
                    'unit' => $product->unit,
                    'product' => $product->name,
                ]),
                422,
                ['quantity' => [__('Only :stock unit(s) available.', ['stock' => $product->current_stock])]]
            );
        }

        $documentPath = null;

        if (! empty($attributes['document'])) {
            $documentPath = $this->documents->store($attributes['document']);
        }

        unset($attributes['document'], $attributes['_token'], $attributes['_method']);

        $attributes['type'] = $type;
        $attributes['reference_number'] = $this->generateReference($type);
        $attributes['status'] = self::STATUS_PENDING;
        $attributes['user_id'] = auth()->id();
        $attributes['stock_before'] = 0;
        $attributes['stock_after'] = 0;
        $attributes['document_path'] = $documentPath;

        /** @var StockTransaction $transaction */
        $transaction = $this->repository->create($attributes);

        $this->activityLogger->log(
            auth()->user(),
            'stock_transaction.created',
            "Submitted {$transaction->reference_number} for {$transaction->quantity} unit(s)"
        );

        return $transaction->load(['product', 'user', 'supplier']);
    }

    /**
     * Record the physical count made against a submitted request.
     *
     * The status column is untouched: inspection refines what PENDING means, so
     * the row keeps its place in the manager's queue instead of disappearing
     * from it when the floor count lands.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws DomainException when the request is no longer open
     */
    public function inspect(int $id, array $attributes): StockTransaction
    {
        /** @var StockTransaction $transaction */
        $transaction = DB::transaction(function () use ($id, $attributes) {
            $transaction = $this->lockTransaction($id);

            $this->assertStatus($transaction, self::STATUS_PENDING);

            $inspectedQuantity = (int) $attributes['inspected_quantity'];

            $transaction->update([
                'inspected_by' => auth()->id(),
                'inspected_at' => now(),
                'inspected_quantity' => $inspectedQuantity,
                'inspection_note' => $attributes['inspection_note'] ?? null,
            ]);

            $variance = $inspectedQuantity - (int) $transaction->quantity;

            $this->activityLogger->log(
                auth()->user(),
                $variance === 0 ? 'stock_transaction.inspected' : 'stock_transaction.discrepancy',
                $variance === 0
                    ? "Inspected {$transaction->reference_number}: {$inspectedQuantity} unit(s) matched the request"
                    : "Inspected {$transaction->reference_number}: counted {$inspectedQuantity}, requested {$transaction->quantity}"
            );

            return $transaction;
        });

        return $transaction->refresh()->load(['product', 'user', 'inspector', 'supplier']);
    }

    /**
     * Approve a pending movement and post the stock change atomically.
     *
     * Locks both the transaction and the product row so concurrent approvals
     * cannot read a stale stock level and over-issue goods.
     */
    public function approve(int $id, ?string $notes = null): StockTransaction
    {
        /** @var StockTransaction $transaction */
        $transaction = DB::transaction(function () use ($id, $notes) {
            $transaction = $this->lockTransaction($id);

            $this->assertStatus($transaction, self::STATUS_PENDING);

            // Separation of duties: the person who submitted a movement cannot
            // also be the one who approves it, even if they hold a manager role.
            if ((int) $transaction->user_id === (int) auth()->id()) {
                throw new DomainException(
                    __('You cannot approve a movement you submitted. Ask another manager or an administrator to review it.'),
                    422
                );
            }

            // A counted mismatch is a question, not a yes. Posting it would move
            // the ledger to the requested quantity while the shelf says
            // otherwise, so the manager has to reject and send it back for a
            // corrected count instead.
            if ($transaction->hasDiscrepancy()) {
                throw new DomainException(
                    __(':reference was counted at :counted against a request of :requested. Reject it and send it back for a corrected count.', [
                        'reference' => $transaction->reference_number,
                        'counted' => $transaction->inspected_quantity,
                        'requested' => $transaction->quantity,
                    ]),
                    422
                );
            }

            /** @var Product $product */
            $product = Product::query()
                ->whereKey($transaction->product_id)
                ->lockForUpdate()
                ->first();

            if ($product === null) {
                throw new DomainException(__('The related product no longer exists.'), 422);
            }

            $stockBefore = (int) $product->current_stock;
            $quantity = (int) $transaction->quantity;

            $stockAfter = $this->calculateStockAfter(
                $stockBefore,
                $quantity,
                $transaction->type,
                $product->name
            );

            $product->update(['current_stock' => $stockAfter]);

            $transaction->update([
                'status' => self::STATUS_APPROVED,
                'approved_by' => auth()->id(),
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'notes' => $notes ?? $transaction->notes,
            ]);

            $this->activityLogger->log(
                auth()->user(),
                'stock_transaction.approved',
                "Approved {$transaction->reference_number}: stock {$stockBefore} -> {$stockAfter}"
            );

            return $transaction;
        });

        return $transaction->refresh()->load(['product', 'user', 'approver']);
    }

    /**
     * Reject a pending movement. Stock is left untouched.
     */
    public function reject(int $id, ?string $notes = null): StockTransaction
    {
        /** @var StockTransaction $transaction */
        $transaction = DB::transaction(function () use ($id, $notes) {
            $transaction = $this->lockTransaction($id);

            $this->assertStatus($transaction, self::STATUS_PENDING);

            $transaction->update([
                'status' => self::STATUS_REJECTED,
                'approved_by' => auth()->id(),
                'notes' => $notes ?? $transaction->notes,
            ]);

            $this->activityLogger->log(
                auth()->user(),
                'stock_transaction.rejected',
                "Rejected {$transaction->reference_number}"
            );

            return $transaction;
        });

        return $transaction->refresh()->load(['product', 'user', 'approver']);
    }

    /**
     * Finalise an approved movement.
     */
    public function complete(int $id): StockTransaction
    {
        /** @var StockTransaction $transaction */
        $transaction = DB::transaction(function () use ($id) {
            $transaction = $this->lockTransaction($id);

            $this->assertStatus($transaction, self::STATUS_APPROVED);

            $transaction->update(['status' => self::STATUS_COMPLETED]);

            $this->activityLogger->log(
                auth()->user(),
                'stock_transaction.completed',
                "Completed {$transaction->reference_number}"
            );

            return $transaction;
        });

        return $transaction->refresh()->load(['product', 'user', 'approver']);
    }

    /**
     * Build a reference like TRX-IN-20261008-001, skipping any that already exists.
     */
    public function generateReference(string $type): string
    {
        $date = Carbon::now()->format('Ymd');
        $prefix = 'TRX-'.strtoupper($type).'-'.$date.'-';
        $sequence = $this->stockTransactionRepository->nextDailySequence(strtoupper($type), $date);

        do {
            $candidate = $prefix.str_pad(
                (string) $sequence,
                self::REFERENCE_SEQUENCE_PAD,
                '0',
                STR_PAD_LEFT
            );

            $sequence++;
        } while ($this->stockTransactionRepository->referenceExists($candidate));

        return $candidate;
    }

    /**
     * Products offered in the movement form, with live stock for context.
     *
     * The combobox needs unit and min_stock alongside the count: "12" on its own
     * is not enough for someone deciding whether an OUT of 40 is a mistake, and
     * min_stock is what tells them the shelf is already near its reorder point.
     */
    public function productsForMovement(): Collection
    {
        return Product::query()
            ->orderBy('name')
            ->get(['id', 'sku', 'name', 'unit', 'min_stock', 'current_stock']);
    }

    /**
     * Suppliers offered as the source of a goods receipt.
     */
    public function suppliersForMovement(): Collection
    {
        return Supplier::query()
            ->orderBy('name')
            ->get(['id', 'code', 'name']);
    }

    public function findByReference(string $reference): ?StockTransaction
    {
        return $this->stockTransactionRepository->findByReference($reference);
    }

    /**
     * Work out the resulting stock level, refusing to go negative.
     *
     * IN always adds. Only OUT can underflow, and it must never be allowed to
     * push a product below zero.
     *
     * @throws DomainException when the movement would overdraw the product
     */
    protected function calculateStockAfter(
        int $stockBefore,
        int $quantity,
        string $type,
        string $productName
    ): int {
        $stockAfter = $type === self::TYPE_IN
            ? $stockBefore + $quantity
            : $stockBefore - $quantity;

        if ($stockAfter < 0) {
            throw new DomainException(
                __('Insufficient stock for :product. Requested :requested, available :available.', [
                    'product' => $productName,
                    'requested' => $quantity,
                    'available' => $stockBefore,
                ]),
                422,
                [
                    'quantity' => [
                        __('Insufficient stock. Only :available unit(s) available.', ['available' => $stockBefore]),
                    ],
                ]
            );
        }

        return $stockAfter;
    }

    /**
     * Fetch a transaction with a row lock held for the surrounding transaction.
     */
    protected function lockTransaction(int $id): StockTransaction
    {
        /** @var StockTransaction|null $transaction */
        $transaction = StockTransaction::query()
            ->whereKey($id)
            ->lockForUpdate()
            ->first();

        if ($transaction === null) {
            throw new DomainException(__('Stock transaction not found.'), 404);
        }

        return $transaction;
    }

    /**
     * Guard the workflow so only PENDING rows can be approved or rejected.
     */
    protected function assertStatus(StockTransaction $transaction, string $expected): void
    {
        if ($transaction->status !== $expected) {
            throw new DomainException(
                __('Only :expected transactions can be processed. This one is :status.', [
                    'expected' => $expected,
                    'status' => $transaction->status,
                ]),
                422
            );
        }
    }
}