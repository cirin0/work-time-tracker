<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeeStatisticsExport implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(
        protected array $stats
    )
    {
    }

    public function title(): string
    {
        return 'Employee Statistics';
    }

    public function array(): array
    {
        $a = $this->stats['attendance'];
        $s = $this->stats['summary'];

        return [
            // Employee info
            ['Employee', $this->stats['user']->name ?? ''],
            ['Email', $this->stats['user']->email ?? ''],
            [],

            // Summary
            ['Summary'],
            ['Total Hours', $this->stats['total_hours']],
            ['Total Minutes (remainder)', $this->stats['total_minutes']],
            ['Working Days', $this->stats['working_days']],
            ['Avg Work Time (min)', $this->stats['average_work_time']],
            [],

            // Attendance
            ['Attendance'],
            ['Metric', 'Count', 'Total (min)', 'Avg (min)'],
            ['Late', $a['late_count'], $a['total_late_minutes'], $a['average_late_minutes']],
            ['Early Arrival', $a['early_count'], '', ''],
            ['On Time', $a['on_time_count'], '', ''],
            ['Early Leave', $a['early_leave_count'], $a['total_early_leave_minutes'], $a['average_early_leave_minutes']],
            ['Overtime', $a['overtime_count'], $a['total_overtime_minutes'], $a['average_overtime_minutes']],
            [],

            // Period summaries
            ['Period Summaries'],
            ['Period', 'Hours', 'Minutes', 'Working Days', 'Late Count', 'Early Count'],
            ['Today', $s['today']['hours'], $s['today']['minutes'], $s['today']['working_days'], $s['today']['late_count'], $s['today']['early_count']],
            ['This Week', $s['week']['hours'], $s['week']['minutes'], $s['week']['working_days'], $s['week']['late_count'], $s['week']['early_count']],
            ['This Month', $s['month']['hours'], $s['month']['minutes'], $s['month']['working_days'], $s['month']['late_count'], $s['month']['early_count']],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],  // Employee row
            2 => ['font' => ['bold' => true]],  // Email row
            5 => ['font' => ['bold' => true]],  // Summary heading
            11 => ['font' => ['bold' => true]],  // Attendance heading
            12 => ['font' => ['bold' => true]],  // Attendance sub-header
            19 => ['font' => ['bold' => true]],  // Period summaries heading
            20 => ['font' => ['bold' => true]],  // Period sub-header
        ];
    }
}
