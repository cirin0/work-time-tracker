<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_default' => (bool)$this->is_default,
            'daily_schedules' => DailyScheduleResource::collection($this->whenLoaded('dailySchedules')),
        ];
    }
}
