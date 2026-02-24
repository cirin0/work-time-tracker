<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class LatenessCalculator
{
    public function calculate(User $user, Carbon $actualStartTime): array
    {
        $workSchedule = $user->workSchedule;

        if (!$workSchedule) {
            return ['lateness_minutes' => null, 'scheduled_start_time' => null];
        }

        $dayOfWeek = strtolower($actualStartTime->format('l'));
        $dailySchedule = $workSchedule->getDailySchedule($dayOfWeek);

        if (!$dailySchedule || !$dailySchedule->is_working_day || !$dailySchedule->start_time) {
            return ['lateness_minutes' => null, 'scheduled_start_time' => null];
        }

        $scheduledStartTime = Carbon::parse($dailySchedule->start_time);
        $scheduledStart = $actualStartTime->copy()->setTimeFromTimeString($scheduledStartTime);

        $latenessMinutes = $scheduledStart->diffInMinutes($actualStartTime, false);

        return [
            'lateness_minutes' => (int)$latenessMinutes,
            'scheduled_start_time' => $scheduledStartTime,
        ];
    }

    public function calculateEarlyLeave(User $user, Carbon $actualEndTime): array
    {
        $workSchedule = $user->workSchedule;

        if (!$workSchedule) {
            return ['early_leave_minutes' => null, 'scheduled_end_time' => null];
        }

        $dayOfWeek = strtolower($actualEndTime->format('l'));
        $dailySchedule = $workSchedule->getDailySchedule($dayOfWeek);

        if (!$dailySchedule || !$dailySchedule->is_working_day || !$dailySchedule->end_time) {
            return ['early_leave_minutes' => null, 'scheduled_end_time' => null];
        }

        $scheduledEndTime = Carbon::parse($dailySchedule->end_time);
        $scheduledEnd = $actualEndTime->copy()->setTimeFromTimeString($scheduledEndTime);

        $earlyLeaveMinutes = $actualEndTime->diffInMinutes($scheduledEnd, false);

        return [
            'early_leave_minutes' => (int)$earlyLeaveMinutes,
            'scheduled_end_time' => $scheduledEndTime,
        ];
    }
}
