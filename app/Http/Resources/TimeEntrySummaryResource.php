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
                'today' => [
                    'hours' => $this->resource['summary']['today']['hours'],
                    'minutes' => $this->resource['summary']['today']['minutes'],
                    'entries' => $this->resource['summary']['today']['entries'],
                ],
                'week' => [
                    'hours' => $this->resource['summary']['week']['hours'],
                    'minutes' => $this->resource['summary']['week']['minutes'],
                    'entries' => $this->resource['summary']['week']['entries'],
                ],
                'month' => [
                    'hours' => $this->resource['summary']['month']['hours'],
                    'minutes' => $this->resource['summary']['month']['minutes'],
                    'entries' => $this->resource['summary']['month']['entries'],
                ],
            ],
        ];
    }
}
