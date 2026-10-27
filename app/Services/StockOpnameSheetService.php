<?php

namespace App\Services;

use App\Exceptions\DomainException;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockOpnameSheet;
use App\Models\StockOpnameSheetItem;
use App\Models\User;
use App\Repositories\Contracts\StockOpnameSheetRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Multi-line physical stock audits.
 *
 * The single-product flow in StockOpnameService is untouched; this service owns
 * the audit-sheet experience where one session counts many products.
 */
class StockOpnameSheetService extends BaseService
{
    public const REFERENCE_PREFIX = 'SOP';

    public const REFERENCE_SEQUENCE_PAD = 3;

    /**
     * Roles permitted to open and submit an audit sheet.
     *
     * @var array<int, string>
     */
    public const COUNTER_ROLES = ['warehouse_staff', 'warehouse_manager', 'admin'];

    /**
     * Roles allowed to attribute a count to a different auditor.
     *
     * @var array<int, string>
     */
    public const SUPERVISOR_ROLES = ['warehouse_manager', 'admin'];

    protected readonly StockOpnameSheetRepository $sheetRepository;

    public function __construct(
        StockOpnameSheetRepository $repository,
        protected readonly ActivityLoggerService $activityLogger,
        protected readonly StockTransactionService $stockTransactions,
    ) {
        parent::__construct($repository);
        $this->sheetRepository = $repository;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listSheets(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->sheetRepository->paginatedWithTotals($perPage, $filters);
    }

    /**
     * A sheet with its lines and their products loaded.
     */
    public function findSheet(int $id): StockOpnameSheet
    {
        /** @var StockOpnameSheet $sheet */
        $sheet = $this->sheetRepository->findOrFail($id);

        return $sheet->load(['auditor', 'verifier', 'items.product']);
    }

    /**
     * Persist a whole audit sheet and its lines in one transaction.
     *
     * system_stock is read from the product row rather than accepted from the
     * request, so nobody can forge the "system" side of a discrepancy, and
     * variance is derived here rather than trusted from the browser.
     *
     * @param  array<string, mixed>  $data
     */
    public function storeSheet(array $data): StockOpnameSheet
    {
        /** @var Collection<int, array{product_id:int, physical_stock:int, reason:?string}> $lines */
        $lines = collect($data['items']);

        $productIds = $lines->pluck('product_id')->map(fn ($id) => (int) $id)->all();

        if ($productIds !== array_unique($productIds)) {
            throw new DomainException(
                __('The same product was counted more than once on this sheet. Combine the counts into a single line.'),
                422
            );
        }

        $user = auth()->user();
        $auditorId = (int) ($data['auditor_id'] ?? $user?->id);

        // Defence in depth: the request rejects a spoofed auditor, but the
        // service also pins non-supervisors to their own account.
        if ($user !== null
            && ! in_array($user->role?->name ?? '', self::SUPERVISOR_ROLES, true)
        ) {
            $auditorId = (int) $user->id;
        }

        $auditDate = $data['audit_date'] ?? Carbon::now()->toDateString();

        /** @var StockOpnameSheet $sheet */
        $sheet = DB::transaction(function () use ($lines, $productIds, $auditorId, $auditDate, $data, $user) {
            // Lock before reading current_stock. The snapshot used for
            // system_stock and the update below must see the same row version,
            // otherwise a concurrent goods movement can be overwritten.
            /** @var Collection<int, Product> $products */
            $products = Product::query()
                ->whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== count($productIds)) {
                throw new DomainException(__('One of the counted products no longer exists.'), 422);
            }

            /** @var StockOpnameSheet $sheet */
            $sheet = $this->sheetRepository->create([
                'reference' => $this->generateReference(Carbon::parse($auditDate)),
                'auditor_id' => $auditorId,
                'audit_date' => $auditDate,
                'status' => StockOpnameSheet::STATUS_SUBMITTED,
                'notes' => $data['notes'] ?? null,
            ]);

            $rows = $lines->map(function (array $line) use ($products, $sheet) {
                /** @var Product $product */
                $product = $products->get((int) $line['product_id']);
                $physical = (int) $line['physical_stock'];
                $system = (int) $product->current_stock;

                return [
                    'stock_opname_sheet_id' => $sheet->id,
                    'product_id' => $product->id,
                    'system_stock' => $system,
                    'physical_stock' => $physical,
                    'variance' => $physical - $system,
                    'reason' => $line['reason'] ?? null,
                ];
            })->all();

            StockOpnameSheetItem::query()->insert($rows);

            // Do not touch current_stock here. The sheet is a proposal until a
            // manager or admin verifies it; submitting only snapshots the count.
            $deficit = collect($rows)->where('variance', '<', 0)->sum('variance');
            $surplus = collect($rows)->where('variance', '>', 0)->sum('variance');

            $this->activityLogger->log(
                $user,
                'stock_opname_sheet.submitted',
                sprintf(
                    'Submitted %d counted product(s) on %s: %d matched, %d short, %d over; awaiting verification by %s',
                    count($rows),
                    $sheet->reference,
                    count(array_filter($rows, fn (array $r) => $r['variance'] === 0)),
                    abs($deficit),
                    $surplus,
                    $user?->name ?? 'system'
                ),
                [],
                $sheet->reference
            );

            return $sheet;
        });

        return $sheet->load(['auditor', 'verifier', 'items.product']);
    }

    /**
     * Verify a submitted sheet and apply its physical counts.
     *
     * The sheet and every affected product are locked together, so a movement
     * cannot change a balance between the comparison and the write. Each line
     * whose live balance differs from the count produces one ADJ row in the
     * movement ledger; matched lines remain on the sheet without a duplicate
     * zero-quantity movement.
     *
     * @throws DomainException when the sheet is already applied or the product is gone
     */
    public function verifySheet(int $id): StockOpnameSheet
    {
        $verifier = auth()->user();

        /** @var StockOpnameSheet $sheet */
        $sheet = DB::transaction(function () use ($id, $verifier) {
            /** @var StockOpnameSheet|null $sheet */
            $sheet = StockOpnameSheet::query()
                ->whereKey($id)
                ->lockForUpdate()
                ->first();

            if ($sheet === null) {
                throw new DomainException(__('Stock opname sheet not found.'), 404);
            }

            if (! in_array($sheet->status, [StockOpnameSheet::STATUS_SUBMITTED, StockOpnameSheet::STATUS_DRAFT], true)) {
                throw new DomainException(
                    __('This sheet has already been applied. Only a submitted count can be verified.'),
                    422
                );
            }

            $sheet->load('items');
            $productIds = $sheet->items->pluck('product_id')->unique()->values()->all();

            // Lock every row the verification can write before reading them.
            Product::query()
                ->whereIn('id', $productIds)
                ->lockForUpdate()
                ->get();

            $adjusted = 0;

            foreach ($sheet->items as $item) {
                $reason = filled($item->reason)
                    ? (string) $item->reason
                    : __('Stock opname :reference', ['reference' => $sheet->reference]);

                $transaction = $this->stockTransactions->recordAdjustmentForProduct(
                    (int) $item->product_id,
                    (int) $item->physical_stock,
                    $reason,
                    $verifier?->id,
                    __('Verified stock opname :reference', ['reference' => $sheet->reference])
                );

                if ($transaction !== null) {
                    $adjusted++;
                }
            }

            $sheet->update([
                'status' => StockOpnameSheet::STATUS_ADJUSTED,
                'verified_by' => $verifier?->id,
                'verified_at' => now(),
            ]);

            $this->activityLogger->log(
                $verifier,
                'stock_opname_sheet.verified',
                sprintf(
                    'Verified %s: %d line(s) applied, %d stock adjustment(s) posted by %s',
                    $sheet->reference,
                    $sheet->items->count(),
                    $adjusted,
                    $verifier?->name ?? 'system'
                ),
                [],
                $sheet->reference
            );

            return $sheet;
        });

        return $sheet->refresh()->load(['auditor', 'verifier', 'items.product']);
    }

    /**
     * Build a reference like SOP-20261017-001, skipping any already taken.
     */
    public function generateReference(Carbon $date): string
    {
        $stamp = $date->format('Ymd');
        $prefix = self::REFERENCE_PREFIX.'-'.$stamp.'-';
        $sequence = $this->sheetRepository->nextDailySequence($stamp);

        do {
            $candidate = $prefix.str_pad((string) $sequence, self::REFERENCE_SEQUENCE_PAD, '0', STR_PAD_LEFT);
            $sequence++;
        } while ($this->sheetRepository->referenceExists($candidate));

        return $candidate;
    }

    /**
     * Catalogue offered to the auditor, with live stock for context.
     *
     * @return Collection<int, Product>
     */
    public function productsForCounting(): Collection
    {
        return Product::query()
            ->with('category:id,name')
            ->orderBy('name')
            ->get(['id', 'sku', 'name', 'current_stock', 'unit', 'category_id']);
    }

    /**
     * People who can be named as the auditor, for the history filter.
     *
     * @return Collection<int, User>
     */
    public function auditors(): Collection
    {
        $roleIds = Role::query()->whereIn('name', self::COUNTER_ROLES)->pluck('id');

        return User::query()
            ->whereIn('role_id', $roleIds)
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
