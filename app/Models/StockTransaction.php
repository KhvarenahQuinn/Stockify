<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransaction extends Model
{
    use HasFactory;

    /**
     * The persisted status enum. Declared here rather than only on the service
     * because the model derives display state from it and the views read the
     * badges straight off the row — three call sites that should not each be
     * typing the same string literal.
     */
    public const STATUS_PENDING = 'PENDING';

    public const STATUS_APPROVED = 'APPROVED';

    public const STATUS_REJECTED = 'REJECTED';

    public const STATUS_COMPLETED = 'COMPLETED';

    /** Derived, human-facing status keys. Never persisted. */
    public const DISPLAY_PENDING_INSPECTION = 'PENDING_INSPECTION';

    public const DISPLAY_AWAITING_APPROVAL = 'AWAITING_APPROVAL';

    public const DISPLAY_DISCREPANCY = 'DISCREPANCY';

    protected $fillable = [
        'reference_number',
        'product_id',
        'supplier_id',
        'user_id',
        'approved_by',
        'inspected_by',
        'type',
        'is_adjustment',
        'adjustment_reason',
        'quantity',
        'stock_before',
        'stock_after',
        'status',
        'notes',
        'destination',
        'reference_note',
        'document_path',
        'inspected_at',
        'inspected_quantity',
        'inspection_note',
    ];

    protected $casts = [
        'is_adjustment' => 'boolean',
        'quantity' => 'integer',
        'stock_before' => 'integer',
        'stock_after' => 'integer',
        'inspected_quantity' => 'integer',
        'inspected_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * The staff member who physically checked the goods.
     */
    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    /**
     * Authenticated URL for the attached delivery note, or null when none was
     * filed.
     */
    protected function documentUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->document_path
                ? route('stock-transactions.document', ['transaction' => $this->getKey()])
                : null,
        );
    }

    /**
     * Filename shown next to the attachment link.
     */
    protected function documentName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->document_path ? basename($this->document_path) : null,
        );
    }

    /**
     * Whether staff have already counted this movement.
     */
    public function hasInspection(): bool
    {
        return $this->inspected_at !== null;
    }

    /**
     * A manual correction rather than a supplier receipt or customer issue.
     */
    public function isAdjustment(): bool
    {
        return (bool) $this->is_adjustment;
    }

    /**
     * Signed gap between the requested quantity and the physical count, or null
     * when the movement has not been inspected yet.
     *
     * Positive means the shelf held more than the paperwork claimed.
     */
    public function inspectionVariance(): ?int
    {
        if (! $this->hasInspection() || $this->inspected_quantity === null) {
            return null;
        }

        return (int) $this->inspected_quantity - (int) $this->quantity;
    }

    public function hasDiscrepancy(): bool
    {
        return (int) $this->inspectionVariance() !== 0;
    }

    /**
     * The status a human sees, which is finer-grained than the stored enum.
     *
     * PENDING alone does not say how far a request has travelled: an untouched
     * one is waiting for a floor inspection, and a counted one is waiting for a
     * manager. Collapsing both into one word in the UI is what made the queue
     * impossible to triage, so the distinction is derived here rather than
     * guessed in every view.
     */
    public function displayStatus(): string
    {
        if ($this->status === self::STATUS_PENDING) {
            if (! $this->hasInspection()) {
                return self::DISPLAY_PENDING_INSPECTION;
            }

            return $this->hasDiscrepancy()
                ? self::DISPLAY_DISCREPANCY
                : self::DISPLAY_AWAITING_APPROVAL;
        }

        return $this->status;
    }

    /**
     * Restrict to a movement direction (IN or OUT).
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', strtoupper($type));
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_APPROVED, self::STATUS_COMPLETED]);
    }

    /**
     * Movements recorded today. Used for the manager's daily counters.
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', now()->toDateString());
    }

    /**
     * Restrict to an inclusive date range; either bound may be omitted.
     */
    public function scopeCreatedBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when($from, fn (Builder $q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn (Builder $q) => $q->whereDate('created_at', '<=', $to));
    }

    /**
     * Submitted but nobody has counted the goods yet.
     */
    public function scopeAwaitingInspection(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING)->whereNull('inspected_at');
    }

    /**
     * Counted, matched the paperwork, and is sitting with a manager.
     */
    public function scopeAwaitingApproval(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_PENDING)
            ->whereNotNull('inspected_at')
            ->whereColumn('inspected_quantity', 'quantity');
    }

    /**
     * Counted and the count disagrees with the request. These block approval:
     * posting the requested quantity would make the ledger disagree with the
     * shelf.
     */
    public function scopeWithDiscrepancy(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_PENDING)
            ->whereNotNull('inspected_at')
            ->whereColumn('inspected_quantity', '!=', 'quantity');
    }
}
