<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'email',
        'phone',
        'address',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Match a supplier on its code, name or email.
     *
     * Wildcards are escaped following the convention already set by
     * Product::scopeSearch, so a literal '%' or '_' in the search box does not
     * silently widen the query.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $escaped = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';

        return $query->where(function (Builder $q) use ($escaped) {
            $q->whereRaw("code LIKE ? ESCAPE '!'", [$escaped])
                ->orWhereRaw("name LIKE ? ESCAPE '!'", [$escaped])
                ->orWhereRaw("email LIKE ? ESCAPE '!'", [$escaped]);
        });
    }
}
