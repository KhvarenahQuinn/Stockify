<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'description',
        'ip_address',
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
