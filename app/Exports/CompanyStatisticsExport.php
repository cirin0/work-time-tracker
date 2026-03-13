<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CompanyStatisticsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(
        protected Collection $statistics
    )
    {
    }

    public function collection(): Collection
    {
        return $this->statistics;
    }

    public function title(): string
    {
        return 'Company Statistics';
    }

    public function headings(): array
    {
        return [
            'Employee',
            'Email',
            'Working Days',
            'Total Hours',
            'Total Minutes (rem.)',
            'Avg Work Time (min)',
            'Late Count',
            'Total Late (min)',
            'Early Leave Count',
            'Total Early Leave (min)',
            'Overtime Count',
            'Total Overtime (min)',
        ];
    }

    public function map($row): array
    {
        $attendance = $row['attendance'];

        return [
            $row['user']->name,
            $row['user']->email,
            $row['working_days'],
            $row['total_hours'],
            $row['total_minutes'],
            $row['average_work_time'],
            $attendance['late_count'],
            $attendance['total_late_minutes'],
            $attendance['early_leave_count'],
            $attendance['total_early_leave_minutes'],
            $attendance['overtime_count'],
            $attendance['total_overtime_minutes'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
