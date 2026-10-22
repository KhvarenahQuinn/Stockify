<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Specifications that apply to products in this category. Drives the
     * attribute picker on the product form.
     */
    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'attribute_category');
    }

    /**
     * Match a category on its name or its slug.
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
            $q->whereRaw("name LIKE ? ESCAPE '!'", [$escaped])
                ->orWhereRaw("slug LIKE ? ESCAPE '!'", [$escaped]);
        });
    }
}
