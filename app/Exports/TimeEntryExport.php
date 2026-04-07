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
            'Співробітник',
            'Email',
            'Дата',
            'Час початку',
            'Час завершення',
            'Тривалість (хв)',
            'Тип запису',
            'Запізнення (хв)',
            'Ранній вихід (хв)',
            'Понаднормові (хв)',
            'Коментар початку',
            'Коментар завершення',
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
            $this->translateEntryType($entry->entry_type?->value ?? 'manual'),
            $entry->lateness_minutes ?? 0,
            $entry->early_leave_minutes ?? 0,
            $entry->overtime_minutes ?? 0,
            $entry->start_comment ?? '',
            $entry->stop_comment ?? '',
        ];
    }

    protected function translateEntryType(string $type): string
    {
        return match ($type) {
            'gps' => 'GPS',
            'qr' => 'QR-код',
            'gps_qr' => 'GPS + QR-код',
            'remote' => 'Віддалено',
            'manual' => 'Вручну',
            default => $type,
        };
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
