<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class LeaveRequestResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        $baseData = [
            'id' => $this->id,
            'type' => $this->type,
            'start_date' => $this->start_date->format('d-m-Y'),
            'end_date' => $this->end_date->format('d-m-Y'),
            'status' => $this->status,
            'created_at' => $this->created_at->format('d-m-Y H:i:s'),
        ];

        if ($this->relationLoaded('user')) {
            $baseData['user'] = [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'avatar' => $this->user->avatar ? Storage::url($this->user->avatar) : null,
            ];
            $baseData['reason'] = $this->reason;
            $baseData['manager_comment'] = $this->manager_comment;
            $baseData['updated_at'] = $this->updated_at->format('d-m-Y H:i:s');
        }

        if ($this->relationLoaded('processor') && $this->processor) {
            $baseData['processor'] = [
                'id' => $this->processor->id,
                'name' => $this->processor->name,
                'email' => $this->processor->email,
                'avatar' => $this->processor->avatar ? Storage::url($this->processor->avatar) : null,
            ];
        }

        return $baseData;
    }
}
