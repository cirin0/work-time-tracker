<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkScheduleRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'is_default' => 'boolean',
            'daily_schedules' => 'sometimes|array',
            'daily_schedules.*.day_of_week' => 'required|string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'daily_schedules.*.start_time' => 'required|date_format:H:i',
            'daily_schedules.*.end_time' => 'required|date_format:H:i',
            'daily_schedules.*.break_duration' => 'required|integer|min:0',
            'daily_schedules.*.is_working_day' => 'required|boolean',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $dailySchedules = $this->input('daily_schedules', []);

            foreach ($dailySchedules as $index => $schedule) {
                if (isset($schedule['is_working_day']) && $schedule['is_working_day'] === true) {
                    $startTime = $schedule['start_time'] ?? null;
                    $endTime = $schedule['end_time'] ?? null;

                    if ($startTime && $endTime && $startTime >= $endTime) {
                        $validator->errors()->add(
                            "daily_schedules.{$index}.end_time",
                            'Час закінчення повинен бути пізніше часу початку для робочих днів.'
                        );
                    }
                }
            }
        });
    }
}
