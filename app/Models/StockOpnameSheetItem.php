<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One counted line on a stock opname sheet.
 *
 * system_stock is a snapshot written when the line was counted, so stock moving
 * afterwards cannot rewrite the audit trail. variance is always derived from
 * system_stock and physical_stock - never accepted from the request.
 */
class StockOpnameSheetItem extends Model
{
    protected $fillable = [
        'stock_opname_sheet_id',
        'product_id',
        'system_stock',
        'physical_stock',
        'variance',
        'reason',
    ];

    protected $casts = [
        'system_stock' => 'integer',
        'physical_stock' => 'integer',
        'variance' => 'integer',
    ];

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(StockOpnameSheet::class, 'stock_opname_sheet_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isMatched(): bool
    {
        return $this->variance === 0;
    }

    public function isSurplus(): bool
    {
        return $this->variance > 0;
    }

    public function isDeficit(): bool
    {
        return $this->variance < 0;
    }
}