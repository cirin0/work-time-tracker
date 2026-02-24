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
            'company_id' => $this->resource['company_id'],
            'total_hours' => $this->resource['total_hours'],
            'total_minutes' => $this->resource['total_minutes'],
            'entries_count' => $this->resource['entries_count'],
            'active_entries_count' => $this->resource['active_entries_count'],
            'active_employees' => $this->resource['active_employees'],
            'total_employees_with_entries' => $this->resource['total_employees_with_entries'],
            'attendance' => $this->resource['attendance'],
            'summary' => $this->resource['summary'],
        ];
    }
}
