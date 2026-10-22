<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One physical audit session: a single reference, one auditor, many lines.
 *
 * The pre-existing StockOpname model records a single counted product. This is
 * the multi-line sheet and deliberately keeps its own table rather than
 * reshaping the one-product flow.
 */
class StockOpnameSheet extends Model
{
    public const STATUS_DRAFT = 'DRAFT';

    public const STATUS_SUBMITTED = 'SUBMITTED';

    public const STATUS_ADJUSTED = 'ADJUSTED';

    protected $fillable = [
        'reference',
        'auditor_id',
        'audit_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'audit_date' => 'date',
    ];

    public function auditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auditor_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockOpnameSheetItem::class);
    }

    /**
     * Aggregate totals.
     *
     * Nothing here is persisted. When the listing pre-computes the sums as SQL
     * sub-selects they arrive in $attributes and are returned as-is; otherwise
     * they are folded from the loaded items. Both paths produce the same
     * numbers, so a sheet can never contradict its own lines.
     *
     * @template T of int
     *
     * @param  callable(): int  $fromItems
     * @return T
     */
    protected function derived(string $key, callable $fromItems): int
    {
        if (array_key_exists($key, $this->attributes)) {
            return (int) $this->attributes[$key];
        }

        return (int) $fromItems();
    }

    /**
     * Lines counted, whether or not they came out even.
     */
    public function totalItems(): int
    {
        return $this->derived('total_items', fn () => $this->items->count());
    }

    /**
     * Lines whose physical count matched the system figure exactly.
     */
    public function totalMatched(): int
    {
        return $this->derived(
            'total_matched',
            fn () => $this->items->filter(fn (StockOpnameSheetItem $item) => $item->variance === 0)->count()
        );
    }

    /**
     * Units found missing. Reported as a positive figure: a shortfall of four
     * is a deficit of four, not a variance of minus four.
     *
     * abs() is applied to both paths because the SQL aggregate arrives as a
     * negative sum, and a ledger that shows deficits as negative numbers is the
     * same mistake the badge column is trying to avoid.
     */
    public function totalDeficit(): int
    {
        return abs($this->derived(
            'total_deficit',
            fn () => $this->items->where('variance', '<', 0)->sum('variance')
        ));
    }

    /**
     * Units found in excess, as a positive figure.
     */
    public function totalSurplus(): int
    {
        return $this->derived(
            'total_surplus',
            fn () => $this->items->where('variance', '>', 0)->sum('variance')
        );
    }

    /**
     * Signed sum across every line. Negative means the warehouse is short.
     */
    public function totalNetVariance(): int
    {
        return $this->derived(
            'total_net_variance',
            fn () => (int) $this->items->sum('variance')
        );
    }

    /**
     * True when any counted line disagreed with the system figure.
     */
    public function hasDiscrepancy(): bool
    {
        return $this->totalMatched() < $this->totalItems();
    }
}