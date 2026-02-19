<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $canViewCompany = $user && ($user->isAdmin() || $user->isManager());

        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_default' => (bool)$this->is_default,
            'company' => $this->when($canViewCompany, CompanyResource::make($this->whenLoaded('company'))),
            'daily_schedules' => DailyScheduleResource::collection($this->whenLoaded('dailySchedules')),
        ];
    }
}
