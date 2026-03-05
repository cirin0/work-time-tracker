<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TimeEntryExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(
        protected Collection $entries
    )
    {
    }

    public function collection(): Collection
    {
        return $this->entries;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Employee',
            'Email',
            'Date',
            'Start Time',
            'Stop Time',
            'Duration (min)',
            'Entry Type',
            'Lateness (min)',
            'Early Leave (min)',
            'Overtime (min)',
            'Start Comment',
            'Stop Comment',
        ];
    }

    public function map($entry): array
    {
        $durationMinutes = $entry->duration ? (int)round($entry->duration / 60) : 0;

        return [
            $entry->id,
            $entry->user?->name ?? '',
            $entry->user?->email ?? '',
            $entry->date,
            $entry->start_time?->format('H:i:s'),
            $entry->stop_time?->format('H:i:s'),
            $durationMinutes,
            $entry->entry_type?->value ?? 'manual',
            $entry->lateness_minutes ?? 0,
            $entry->early_leave_minutes ?? 0,
            $entry->overtime_minutes ?? 0,
            $entry->start_comment ?? '',
            $entry->stop_comment ?? '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
