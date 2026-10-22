<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

/**
 * A blank import sheet whose headings match ProductImport::rules() exactly, plus
 * one worked example row. Downloading it and importing it unchanged has to work,
 * otherwise the template is worse than useless.
 */
class ProductTemplateExport implements FromArray, WithHeadings, ShouldAutoSize, WithStrictNullComparison
{
    use Exportable;

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'sku',
            'name',
            'description',
            'category',
            'supplier',
            'buy_price',
            'sell_price',
            'min_stock',
            'current_stock',
            'unit',
        ];
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        return [
            [
                '',
                'Corrugated Carton Box Large',
                'Single-wall carton for order packing.',
                'Packaging Materials',
                'SUP-001',
                '7200',
                '9500',
                '60',
                '118',
                'piece',
            ],
        ];
    }
}