<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveRequestResource extends JsonResource
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
            'type' => $this->type,
            'start_date' => $this->start_date->format('d-m-Y'),
            'end_date' => $this->end_date->format('d-m-Y'),
            'reason' => $this->reason,
            'status' => $this->status,
            'processor' => $this->whenLoaded('processor', fn() => [
                'id' => $this->processor->id,
                'name' => $this->processor->name,
                'email' => $this->processor->email,
            ]),
            'manager_comment' => $this->manager_comment,
            'created_at' => $this->created_at->format('d-m-Y H:i:s'),
            'updated_at' => $this->updated_at->format('d-m-Y H:i:s'),
        ];
    }
}
