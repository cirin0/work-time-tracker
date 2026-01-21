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
            'user_id' => $this->resource['user_id'],
            'total_hours' => $this->resource['total_hours'],
            'total_minutes' => $this->resource['total_minutes'],
            'entries_count' => $this->resource['entries_count'],
            'average_work_time' => $this->resource['average_work_time'],
            'summary' => [
                'today' => $this->resource['summary']['today'],
                'week' => $this->resource['summary']['week'],
                'month' => $this->resource['summary']['month'],
            ],
        ];
    }
}
