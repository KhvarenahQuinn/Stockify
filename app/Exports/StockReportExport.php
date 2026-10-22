<?php

namespace App\Exports;

use App\Models\Product;
use App\Support\Spreadsheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class StockReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStrictNullComparison
{
    use Exportable;

    /**
     * @param  Collection<int, Product>  $rows
     */
    public function __construct(
        private readonly Collection $rows,
        private readonly bool $withPeriod = false,
    ) {
    }

    /**
     * @return Collection<int, Product>
     */
    public function collection(): Collection
    {
        return $this->rows;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        $headings = [__('SKU'), __('Product'), __('Category'), __('Supplier'), __('On Hand'), __('Min Stock'), __('Status'), __('Buy Price'), __('Stock Value')];

        if ($this->withPeriod) {
            array_push($headings, __('Period In'), __('Period Out'));
        }

        return $headings;
    }

    /**
     * @param  Product  $row
     * @return array<int, mixed>
     */
    public function map($row): array
    {
        $mapped = Spreadsheet::row([
            $row->sku,
            $row->name,
            $row->category?->name,
            $row->supplier?->name,
            (int) $row->current_stock,
            (int) $row->min_stock,
            $this->statusLabel($row),
            (float) $row->buy_price,
            round($row->current_stock * (float) $row->buy_price, 2),
        ]);

        if ($this->withPeriod) {
            $mapped[] = (int) ($row->period_in ?? 0);
            $mapped[] = (int) ($row->period_out ?? 0);
        }

        return $mapped;
    }

    protected function statusLabel(Product $product): string
    {
        if ($product->current_stock <= 0) {
            return __('Out of stock');
        }

        return $product->current_stock <= $product->min_stock ? __('Low stock') : __('Safe');
    }
}