<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProfileResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar ? Storage::url($this->avatar) : null,
            'role' => $this->role,
            'work_mode' => $this->work_mode,
            'has_pin_code' => !empty($this->pin_code),
            'company' => $this->whenLoaded('company', fn() => [
                'id' => $this->company->id,
                'name' => $this->company->name,
            ]),
            'manager' => $this->whenLoaded('manager', fn() => [
                'id' => $this->manager->id,
                'name' => $this->manager->name,
                'email' => $this->manager->email,
                'avatar' => $this->manager->avatar ? Storage::url($this->manager->avatar) : null,
            ]),
            'work_schedule' => $this->whenLoaded('workSchedule', fn() => [
                'id' => $this->workSchedule->id,
                'name' => $this->workSchedule->name,
                'daily_schedules' => $this->workSchedule->dailySchedules->map(fn($schedule) => [
                    'id' => $schedule->id,
                    'day_of_week' => $schedule->day_of_week,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'break_duration' => $schedule->break_duration,
                    'is_working_day' => $schedule->is_working_day,
                ]),
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
