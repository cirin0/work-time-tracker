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

    public function messages(): array
    {
        return [
            'name.required' => 'The name is required.',
            'name.max' => 'The name must not exceed 255 characters.',
            'daily_schedules.*.day_of_week.required' => 'Day of week is required for each schedule.',
            'daily_schedules.*.day_of_week.in' => 'Invalid day of week provided.',
            'daily_schedules.*.start_time.required' => 'Start time is required for each schedule.',
            'daily_schedules.*.start_time.date_format' => 'Start time must be in HH:MM format.',
            'daily_schedules.*.end_time.required' => 'End time is required for each schedule.',
            'daily_schedules.*.end_time.date_format' => 'End time must be in HH:MM format.',
            'daily_schedules.*.break_duration.required' => 'Break duration is required for each schedule.',
            'daily_schedules.*.break_duration.integer' => 'Break duration must be an integer.',
            'daily_schedules.*.break_duration.min' => 'Break duration cannot be negative.',
            'daily_schedules.*.is_working_day.required' => 'Working day status is required for each schedule.',
            'daily_schedules.*.is_working_day.boolean' => 'Working day status must be true or false.',
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

                    if ($startTime && $endTime && $startTime === $endTime) {
                        $validator->errors()->add(
                            "daily_schedules.{$index}.end_time",
                            'Час початку та завершення не можуть співпадати для робочих днів.'
                        );
                    }
                }
            }
        });
    }
}
