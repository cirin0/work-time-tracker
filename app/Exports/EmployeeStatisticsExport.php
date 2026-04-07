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
        return 'Статистика співробітника';
    }

    public function array(): array
    {
        $a = $this->stats['attendance'];
        $s = $this->stats['summary'];

        return [
            // Employee info
            ['Співробітник', $this->stats['user']->name ?? ''],
            ['Email', $this->stats['user']->email ?? ''],
            [],

            // Summary
            ['Загальна інформація'],
            ['Всього годин', $this->stats['total_hours']],
            ['Всього хвилин (залишок)', $this->stats['total_minutes']],
            ['Робочих днів', $this->stats['working_days']],
            ['Середній робочий час (хв)', $this->stats['average_work_time']],
            [],

            // Attendance
            ['Відвідуваність'],
            ['Показник', 'Кількість', 'Всього (хв)', 'Середнє (хв)'],
            ['Запізнення', $a['late_count'], $a['total_late_minutes'], $a['average_late_minutes']],
            ['Ранній прихід', $a['early_count'], '', ''],
            ['Вчасно', $a['on_time_count'], '', ''],
            ['Ранній вихід', $a['early_leave_count'], $a['total_early_leave_minutes'], $a['average_early_leave_minutes']],
            ['Понаднормові', $a['overtime_count'], $a['total_overtime_minutes'], $a['average_overtime_minutes']],
            [],

            // Period summaries
            ['Підсумки за періоди'],
            ['Період', 'Годин', 'Хвилин', 'Робочих днів', 'Запізнень', 'Ранніх приходів'],
            ['Сьогодні', $s['today']['hours'], $s['today']['minutes'], $s['today']['working_days'], $s['today']['late_count'], $s['today']['early_count']],
            ['Цей тиждень', $s['week']['hours'], $s['week']['minutes'], $s['week']['working_days'], $s['week']['late_count'], $s['week']['early_count']],
            ['Цей місяць', $s['month']['hours'], $s['month']['minutes'], $s['month']['working_days'], $s['month']['late_count'], $s['month']['early_count']],
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
