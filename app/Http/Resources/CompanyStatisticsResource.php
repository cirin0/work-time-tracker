<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyStatisticsResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'company_id' => $this->resource['company_id'] ?? null,
            'employee_count' => $this->resource['employee_count'] ?? 0,
            'total_hours' => $this->resource['total_hours'] ?? 0,
            'total_minutes' => $this->resource['total_minutes'] ?? 0,
            'total_entries_count' => $this->resource['total_entries_count'] ?? 0,
            'total_working_days' => $this->resource['total_working_days'] ?? 0,
            'average_working_days_per_employee' => $this->resource['average_working_days_per_employee'] ?? 0,
            'active_entries_count' => $this->resource['active_entries_count'] ?? 0,
            'active_employees' => $this->resource['active_employees'] ?? 0,
            'total_employees_with_entries' => $this->resource['total_employees_with_entries'] ?? 0,
            'attendance' => $this->resource['attendance'] ?? [],
            'summary' => $this->resource['summary'] ?? [
                    'today' => ['hours' => 0, 'minutes' => 0, 'working_days' => 0],
                    'week' => ['hours' => 0, 'minutes' => 0, 'working_days' => 0],
                    'month' => ['hours' => 0, 'minutes' => 0, 'working_days' => 0],
                ],
        ];
    }
}
