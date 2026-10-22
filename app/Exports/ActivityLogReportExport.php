<?php

namespace App\Exports;

use App\Models\ActivityLog;
use App\Support\Spreadsheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class ActivityLogReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStrictNullComparison
{
    use Exportable;

    /**
     * @param  Collection<int, ActivityLog>  $rows
     */
    public function __construct(private readonly Collection $rows)
    {
    }

    /**
     * @return Collection<int, ActivityLog>
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
        return [__('Date'), __('User'), __('Action'), __('Description'), __('IP Address')];
    }

    /**
     * @param  ActivityLog  $row
     * @return array<int, mixed>
     */
    public function map($row): array
    {
        return Spreadsheet::row([
            $row->created_at?->format('Y-m-d H:i:s'),
            $row->user?->name,
            $row->displayAction(),
            $row->description,
            $row->ip_address,
        ]);
    }
}