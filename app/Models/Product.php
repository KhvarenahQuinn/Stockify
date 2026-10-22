<?php

namespace App\Models;

use App\Services\ProductImageService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'name',
        'description',
        'category_id',
        'supplier_id',
        'buy_price',
        'sell_price',
        'min_stock',
        'current_stock',
        'unit',
        'image',
    ];

    protected $casts = [
        'buy_price' => 'decimal:2',
        'sell_price' => 'decimal:2',
        'min_stock' => 'integer',
        'current_stock' => 'integer',
    ];

    /**
     * Public URL for the stored image, or null when none is set.
     *
     * Resolved here rather than by calling ProductImageService from every
     * Blade file so the table, the edit modal and the detail modal can never
     * disagree about how an image path becomes a URL.
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->image
                ? Storage::disk(ProductImageService::DISK)->url($this->image)
                : null,
        );
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class, 'attribute_product', 'product_id', 'attribute_value_id')
            ->withPivot('id');
    }

    /**
     * Every stock movement recorded against this product.
     */
    public function stockTransactions(): HasMany
    {
        return $this->hasMany(StockTransaction::class);
    }

    /**
     * Items at or below their reorder point, including out-of-stock.
     *
     * This stays deliberately broad because the dashboard alert list treats a
     * zero-stock item as the most urgent low-stock case. The stock report needs
     * three mutually exclusive buckets, so it uses lowStockOnly() instead.
     */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('current_stock', '<=', 'min_stock');
    }

    /**
     * Items at or below their reorder point that still have stock on hand.
     */
    public function scopeLowStockOnly(Builder $query): Builder
    {
        return $query->where('current_stock', '>', 0)
            ->whereColumn('current_stock', '<=', 'min_stock');
    }

    /**
     * Items that have run out entirely.
     */
    public function scopeOutOfStock(Builder $query): Builder
    {
        return $query->where('current_stock', 0);
    }

    /**
     * Items comfortably above their reorder point.
     */
    public function scopeSafeStock(Builder $query): Builder
    {
        return $query->whereColumn('current_stock', '>', 'min_stock');
    }

    /**
     * Case-insensitive search across the fields staff actually search by.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        // Escape with '!' (and escape '!' itself) rather than '\': MySQL treats
        // a backslash inside a string literal as an escape, and SQLite has no
        // default LIKE escape at all. '!' is portable across both.
        $escaped = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';

        return $query->where(function (Builder $q) use ($escaped) {
            $q->whereRaw("name LIKE ? ESCAPE '!'", [$escaped])
                ->orWhereRaw("sku LIKE ? ESCAPE '!'", [$escaped]);
        });
    }
}
