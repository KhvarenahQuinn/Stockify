<?php

namespace App\Services;

use App\Exceptions\DomainException;
use App\Models\Product;
use App\Models\StockOpname;
use App\Repositories\Contracts\StockOpnameRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StockOpnameService extends BaseService
{
    public const STATUS_DRAFT = 'DRAFT';

    public const STATUS_ADJUSTED = 'ADJUSTED';

    public const NUMBER_PREFIX = 'SOP';

    public const NUMBER_SEQUENCE_PAD = 3;

    protected readonly StockOpnameRepository $stockOpnameRepository;

    public function __construct(
        StockOpnameRepository $repository,
        protected readonly ActivityLoggerService $activityLogger,
    ) {
        parent::__construct($repository);
        $this->stockOpnameRepository = $repository;
    }

    /**
     * Paginated list of counts with optional filters (status, product, dates).
     *
     * @param  array<string, mixed>  $filters
     */
    public function listOpnames(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->stockOpnameRepository->paginatedWithRelations($perPage, $filters);
    }

    public function findOpname(int $id): StockOpname
    {
        /** @var StockOpname $opname */
        $opname = $this->stockOpnameRepository->findOrFail($id);

        return $opname->load(['product', 'user']);
    }

    /**
     * Record a physical count.
     *
     * system_stock is read from the product rather than accepted from the
     * request, so a client cannot forge the "system" side of a discrepancy.
     * The difference is derived here, never trusted from the caller.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createOpname(array $attributes): StockOpname
    {
        /** @var Product $product */
        $product = Product::query()->findOrFail((int) $attributes['product_id']);

        $physicalStock = (int) $attributes['physical_stock'];
        $systemStock = (int) $product->current_stock;

        /** @var StockOpname $opname */
        $opname = DB::transaction(function () use ($product, $physicalStock, $systemStock, $attributes) {
            /** @var StockOpname $opname */
            $opname = $this->repository->create([
                'opname_number' => $this->generateOpnameNumber(),
                'product_id' => $product->id,
                'user_id' => auth()->id(),
                'system_stock' => $systemStock,
                'physical_stock' => $physicalStock,
                'difference' => $physicalStock - $systemStock,
                'status' => self::STATUS_DRAFT,
                'notes' => $attributes['notes'] ?? null,
            ]);

            $this->activityLogger->log(
                auth()->user(),
                'stock_opname.created',
                "Counted {$product->name}: system {$systemStock}, physical {$physicalStock}, "
                ."difference {$opname->difference} ({$opname->opname_number})",
                [],
                $opname->opname_number
            );

            return $opname;
        });

        return $opname->load(['product', 'user']);
    }

    /**
     * Verify a draft count and post the stock correction atomically.
     *
     * The product row is re-read under a lock and system_stock is refreshed to
     * the value observed at posting time, so stock movements that happened
     * between the count and the verification cannot produce a phantom gain or
     * loss. The count-time figures are preserved in the activity log.
     */
    public function adjust(int $id, ?string $notes = null): StockOpname
    {
        /** @var StockOpname $opname */
        $opname = DB::transaction(function () use ($id, $notes) {
            $opname = $this->lockOpname($id);

            if ($opname->status !== self::STATUS_DRAFT) {
                throw new DomainException(
                    __('Only DRAFT counts can be adjusted. This one is already :status.', ['status' => $opname->status]),
                    422
                );
            }

            /** @var Product $product */
            $product = Product::query()
                ->whereKey($opname->product_id)
                ->lockForUpdate()
                ->first();

            if ($product === null) {
                throw new DomainException(__('The related product no longer exists.'), 422);
            }

            $systemAtPosting = (int) $product->current_stock;
            $physicalStock = (int) $opname->physical_stock;

            $product->update(['current_stock' => $physicalStock]);

            $opname->update([
                'status' => self::STATUS_ADJUSTED,
                'system_stock' => $systemAtPosting,
                'difference' => $physicalStock - $systemAtPosting,
                'notes' => $notes ?? $opname->notes,
            ]);

            $this->activityLogger->log(
                auth()->user(),
                'stock_opname.adjusted',
                "Adjusted {$product->name} to {$physicalStock} "
                ."(counted at {$opname->physical_stock}, system was {$systemAtPosting})",
                [
                    'old' => ['current_stock' => $systemAtPosting],
                    'new' => ['current_stock' => $physicalStock],
                ],
                $opname->opname_number
            );

            return $opname;
        });

        return $opname->refresh()->load(['product', 'user']);
    }

    /**
     * Build an opname number like SOP-20261008-001, skipping existing numbers.
     */
    public function generateOpnameNumber(): string
    {
        $date = Carbon::now()->format('Ymd');
        $prefix = self::NUMBER_PREFIX.'-'.$date.'-';
        $sequence = $this->stockOpnameRepository->nextDailySequence($date);

        do {
            $candidate = $prefix.str_pad(
                (string) $sequence,
                self::NUMBER_SEQUENCE_PAD,
                '0',
                STR_PAD_LEFT
            );

            $sequence++;
        } while ($this->stockOpnameRepository->numberExists($candidate));

        return $candidate;
    }

    /**
     * Products offered in the counting form, with live stock for context.
     */
    public function productsForCounting(): Collection
    {
        return Product::query()
            ->orderBy('name')
            ->get(['id', 'sku', 'name', 'current_stock']);
    }

    public function findByNumber(string $number): ?StockOpname
    {
        return $this->stockOpnameRepository->findByNumber($number);
    }

    /**
     * Fetch a count with a row lock held for the surrounding transaction.
     */
    protected function lockOpname(int $id): StockOpname
    {
        /** @var StockOpname|null $opname */
        $opname = StockOpname::query()
            ->whereKey($id)
            ->lockForUpdate()
            ->first();

        if ($opname === null) {
            throw new DomainException(__('Stock opname not found.'), 404);
        }

        return $opname;
    }
}
