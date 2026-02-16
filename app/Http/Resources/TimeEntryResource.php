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
            'start_time' => $this->start_time?->format('d-m-Y H:i:s'),
            'stop_time' => $this->stop_time?->format('d-m-Y H:i:s'),
            'duration' => $this->duration ?? 0,
            'entry_type' => $this->entry_type ?? 'gps',
            'location_data' => $this->location_data,
            'start_comment' => $this->start_comment,
            'stop_comment' => $this->stop_comment,
            'created_at' => $this->created_at?->format('d-m-Y H:i:s'),
            'updated_at' => $this->updated_at?->format('d-m-Y H:i:s'),
        ];
    }
}
