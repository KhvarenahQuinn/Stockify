<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class);
    }

    /**
     * Categories whose product form should offer this attribute.
     */
    public function categories(): BelongsToMany
    {
        // No withTimestamps(), matching the attribute_product pivot: these tables
        // carry only the join and its unique constraint.
        return $this->belongsToMany(Category::class, 'attribute_category');
    }

    /**
     * Match an attribute on its name, or on the name of one of its values so a
     * manager can find "Size" by typing "XL".
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
                ->orWhereHas('values', fn (Builder $v) => $v->whereRaw("value LIKE ? ESCAPE '!'", [$escaped]));
        });
    }
}
