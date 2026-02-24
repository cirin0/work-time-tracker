<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimeEntryResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => $this->whenLoaded('user', fn() => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'start_time' => $this->start_time,
            'stop_time' => $this->stop_time,
            'duration' => $this->duration ?? 0,
            'entry_type' => $this->entry_type ?? 'gps',
            'location_data' => $this->location_data,
            'start_comment' => $this->start_comment,
            'stop_comment' => $this->stop_comment,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
