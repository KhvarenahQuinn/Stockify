<?php

namespace App\Exports;

use App\Models\StockTransaction;
use App\Support\Spreadsheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class TransactionReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStrictNullComparison
{
    use Exportable;

    /**
     * @param  Collection<int, StockTransaction>  $rows
     */
    public function __construct(private readonly Collection $rows)
    {
    }

    /**
     * @return Collection<int, StockTransaction>
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
        return [
            __('Reference'),
            __('Type'),
            __('Product'),
            __('SKU'),
            __('Category'),
            __('Supplier'),
            __('Quantity'),
            __('Stock Before'),
            __('Stock After'),
            __('Status'),
            __('Requested By'),
            __('Reviewed By'),
            __('Date'),
            __('Notes'),
        ];
    }

    /**
     * @param  StockTransaction  $row
     * @return array<int, mixed>
     */
    public function map($row): array
    {
        return Spreadsheet::row([
            $row->reference_number,
            $row->type,
            $row->product?->name,
            $row->product?->sku,
            $row->product?->category?->name,
            $row->product?->supplier?->name,
            (int) $row->quantity,
            (int) $row->stock_before,
            (int) $row->stock_after,
            __($row->status),
            $row->user?->name,
            $row->approver?->name,
            $row->created_at?->format('Y-m-d H:i'),
            $row->notes,
        ]);
    }
}