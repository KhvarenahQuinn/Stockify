<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ActivityLog extends Model
{
    use HasFactory;

    /**
     * Readable labels for the columns the services diff into `properties`.
     * Anything missing falls back to a headline reading of the column name, so
     * a newly logged field still makes sense without touching this list.
     *
     * @var array<string, string>
     */
    protected const CHANGE_LABELS = [
        'name' => 'Name',
        'sku' => 'SKU',
        'code' => 'Code',
        'email' => 'Email',
        'phone' => 'Phone',
        'address' => 'Address',
        'description' => 'Description',
        'category_id' => 'Category',
        'supplier_id' => 'Supplier',
        'role_id' => 'Role',
        'buy_price' => 'Buy price',
        'sell_price' => 'Sell price',
        'min_stock' => 'Minimum stock',
        'current_stock' => 'Stock on hand',
        'stock_before' => 'Stock before',
        'stock_after' => 'Stock after',
        'status' => 'Status',
        'unit' => 'Unit',
        'quantity' => 'Quantity',
        'values' => 'Values',
        'value' => 'Value',
        'notes' => 'Notes',
        'adjustment_reason' => 'Reason',
        'inspected_quantity' => 'Counted quantity',
        'inspection_note' => 'Inspection note',
    ];

    protected $fillable = [
        'user_id',
        'action',
        'description',
        'ip_address',
        'properties',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Human-readable action label for the audit feed and report table.
     */
    public function displayAction(): string
    {
        return __($this->action);
    }

    /**
     * Old/new field pairs for the detail modal.
     *
     * The services store a `['old' => [...], 'new' => [...]]` payload; a log
     * written before that shape existed (or one that stores something else)
     * simply yields an empty list, and the modal falls back to the raw payload.
     *
     * @return array<int, array{label: string, old: string, new: string}>
     */
    public function changeDetails(): array
    {
        $old = $this->properties['old'] ?? null;
        $new = $this->properties['new'] ?? null;

        if (! is_array($old) || ! is_array($new)) {
            return [];
        }

        $changes = [];

        foreach (array_keys($old + $new) as $field) {
            $changes[] = [
                'label' => __(self::CHANGE_LABELS[$field] ?? Str::headline($field)),
                'old' => $this->formatChangeValue($old[$field] ?? null),
                'new' => $this->formatChangeValue($new[$field] ?? null),
            ];
        }

        return $changes;
    }

    /**
     * Whether the row carries any structured payload at all.
     */
    public function hasProperties(): bool
    {
        return ! empty($this->properties);
    }

    /**
     * Human label of the record the action touched (e.g. "Siti Rahayu").
     */
    public function subject(): ?string
    {
        $subject = $this->properties['subject'] ?? null;

        return is_string($subject) && $subject !== '' ? $subject : null;
    }

    /**
     * Translation key for a plain-language note stored with the entry, such as
     * "Password was changed." Returned as a key so the reader's locale decides
     * the wording rather than the actor's.
     */
    public function noteKey(): ?string
    {
        $note = $this->properties['note'] ?? null;

        return is_string($note) && $note !== '' ? $note : null;
    }

    /**
     * Render a stored value for display. Arrays are flattened rather than
     * dumped as JSON, because the modal already has a raw-payload fallback.
     */
    protected function formatChangeValue(mixed $value): string
    {
        if (is_array($value)) {
            $value = array_filter(
                array_map(
                    fn ($item) => is_scalar($item) ? (string) $item : json_encode($item, JSON_UNESCAPED_UNICODE),
                    $value
                ),
                fn ($item) => $item !== ''
            );

            return $value === [] ? '—' : implode(', ', $value);
        }

        if (is_bool($value)) {
            return $value ? __('Yes') : __('No');
        }

        if ($value === null || $value === '') {
            return '—';
        }

        return (string) $value;
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
     * Filter by the dotted action slug, or a leading segment of it
     * (e.g. 'user' matches 'user.created').
     *
     * Wildcards are escaped so a caller-supplied '%' or '_' is matched
     * literally instead of widening the prefix, matching the convention
     * already used by Product::scopeSearch and the activity report filter.
     */
    public function scopeAction(Builder $query, ?string $action): Builder
    {
        if (blank($action)) {
            return $query;
        }

        // Escape with '!' rather than '\': MySQL treats a backslash inside a
        // string literal as an escape, so "ESCAPE '\'" is a syntax error there,
        // and SQLite has no default escape at all. '!' is portable across both.
        $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $action);

        return $query->whereRaw("action LIKE ? ESCAPE '!'", [$escaped.'%']);
    }
}
