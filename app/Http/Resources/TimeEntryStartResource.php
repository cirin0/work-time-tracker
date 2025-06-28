<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TimeEntryStartResource',
    description: 'Resource representation of a started time entry',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', example: 1),
        new OA\Property(property: 'start_time', type: 'string', format: 'date-time', example: '2023-10-01T12:00:00Z'),
        new OA\Property(property: 'comment', type: 'string', maxLength: 255, example: 'Started working on project X'),
    ],
    type: 'object'
)]
class TimeEntryStartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'start_time' => $this->start_time->format('Y-m-d H:i:s'),
            'comment' => $this->comment,
        ];
    }
}
