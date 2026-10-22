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

class ProductExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStrictNullComparison
{
    use Exportable;

    /**
     * @param  Collection<int, Product>  $products
     */
    public function __construct(private readonly Collection $products)
    {
    }

    /**
     * @return Collection<int, Product>
     */
    public function collection(): Collection
    {
        return $this->products;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            __('SKU'),
            __('Name'),
            __('Description'),
            __('Category'),
            __('Supplier Code'),
            __('Supplier'),
            __('Buy Price'),
            __('Sell Price'),
            __('Min Stock'),
            __('Current Stock'),
            __('Unit'),
            __('Attributes'),
        ];
    }

    /**
     * @param  Product  $row
     * @return array<int, mixed>
     */
    public function map($row): array
    {
        return Spreadsheet::row([
            $row->sku,
            $row->name,
            $row->description,
            $row->category?->name,
            $row->supplier?->code,
            $row->supplier?->name,
            $row->buy_price,
            $row->sell_price,
            $row->min_stock,
            $row->current_stock,
            $row->unit,
            $this->formatAttributes($row),
        ]);
    }

    /**
     * Render the product's attribute values as "Color: Red | Size: L".
     */
    protected function formatAttributes(Product $product): string
    {
        return $product->attributeValues
            ->map(fn ($value) => $value->attribute?->name.': '.$value->value)
            ->filter()
            ->implode(' | ');
    }
}