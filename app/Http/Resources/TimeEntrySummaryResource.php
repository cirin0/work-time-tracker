<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TimeEntrySummaryResource',
    description: 'Resource representation of a time entry summary',
    properties: [
        new OA\Property(property: 'user_id', type: 'integer', example: 1),
        new OA\Property(property: 'total_hours', type: 'integer', example: 40),
        new OA\Property(property: 'total_minutes', type: 'integer', example: 2400),
        new OA\Property(property: 'entries_count', type: 'integer', example: 10),
        new OA\Property(property: 'average_work_time', type: 'string', format: 'duration', example: '04:00'),
        new OA\Property(property: 'summary', properties: [
            new OA\Property(property: 'today', type: 'string', format: 'duration', example: '02:00'),
            new OA\Property(property: 'week', type: 'string', format: 'duration', example: '20:00'),
            new OA\Property(property: 'month', type: 'string', format: 'duration', example: '80:00'),
        ], type: 'object'),
    ],
    type: 'object'
)]
class TimeEntrySummaryResource extends JsonResource
{
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
            ]
        ];
    }
}
