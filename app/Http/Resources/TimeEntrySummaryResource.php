<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimeEntrySummaryResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->resource['user_id'] ?? null,
            'total_hours' => $this->resource['total_hours'] ?? 0,
            'total_minutes' => $this->resource['total_minutes'] ?? 0,
            'working_days' => $this->resource['working_days'] ?? 0,
            'average_work_time' => $this->resource['average_work_time'] ?? 0,
            'attendance' => $this->resource['attendance'] ?? $this->getEmptyAttendance(),
            'summary' => [
                'today' => $this->formatPeriod($this->resource['summary']['today'] ?? []),
                'week' => $this->formatPeriod($this->resource['summary']['week'] ?? []),
                'month' => $this->formatPeriod($this->resource['summary']['month'] ?? []),
            ],
        ];
    }

    private function getEmptyAttendance(): array
    {
        return [
            'late_count' => 0,
            'early_count' => 0,
            'on_time_count' => 0,
            'total_late_minutes' => 0,
            'average_late_minutes' => 0,
            'early_leave_count' => 0,
            'total_early_leave_minutes' => 0,
            'average_early_leave_minutes' => 0,
            'overtime_count' => 0,
            'total_overtime_minutes' => 0,
            'average_overtime_minutes' => 0,
        ];
    }

    private function formatPeriod(array $data): array
    {
        return [
            'hours' => $data['hours'] ?? 0,
            'minutes' => $data['minutes'] ?? 0,
            'working_days' => $data['working_days'] ?? 0,
            'late_count' => $data['late_count'] ?? 0,
            'early_count' => $data['early_count'] ?? 0,
        ];
    }
}
