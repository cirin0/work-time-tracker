<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class LatenessCalculator
{
    public function __construct(protected CacheService $cacheService)
    {
    }

    public function calculate(User $user, CarbonInterface $actualStartTime): array
    {
        $workSchedule = null;

        if ($user->work_schedule_id) {
            $workSchedule = $this->cacheService->getWorkSchedule($user->work_schedule_id);
        }

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

    public function calculateEarlyLeave(User $user, CarbonInterface $actualEndTime): array
    {
        $resolved = $this->resolveScheduledEndForEndTime($user, $actualEndTime);

        if (!$resolved['scheduled_end'] || !$resolved['scheduled_end_time']) {
            return ['early_leave_minutes' => null, 'scheduled_end_time' => null];
        }

        $earlyLeaveMinutes = $actualEndTime->diffInMinutes($resolved['scheduled_end'], false);

        if ($earlyLeaveMinutes <= 0) {
            return ['early_leave_minutes' => 0, 'scheduled_end_time' => $resolved['scheduled_end_time']];
        }

        return [
            'early_leave_minutes' => (int)$earlyLeaveMinutes,
            'scheduled_end_time' => $resolved['scheduled_end_time'],
        ];
    }

    private function resolveScheduledEndForEndTime(User $user, CarbonInterface $actualEndTime): array
    {
        if (!$user->work_schedule_id) {
            return ['scheduled_end' => null, 'scheduled_end_time' => null, 'daily_schedule' => null];
        }

        $workSchedule = $this->cacheService->getWorkSchedule($user->work_schedule_id);
        if (!$workSchedule) {
            return ['scheduled_end' => null, 'scheduled_end_time' => null, 'daily_schedule' => null];
        }

        $currentDayOfWeek = strtolower($actualEndTime->format('l'));
        $previousDayOfWeek = strtolower($actualEndTime->copy()->subDay()->format('l'));

        $candidateSchedules = [
            $workSchedule->getDailySchedule($currentDayOfWeek),
            $workSchedule->getDailySchedule($previousDayOfWeek),
        ];

        $bestScheduledEnd = null;
        $bestScheduledEndTime = null;
        $bestDailySchedule = null;
        $bestDiff = null;

        foreach ($candidateSchedules as $dailySchedule) {
            if (!$dailySchedule || !$dailySchedule->is_working_day || !$dailySchedule->end_time) {
                continue;
            }

            $scheduledEndTime = Carbon::parse($dailySchedule->end_time);
            $scheduledEnd = $actualEndTime->copy()->setTimeFromTimeString($scheduledEndTime->format('H:i:s'));

            if (!empty($dailySchedule->start_time)) {
                $scheduledStartTime = Carbon::parse($dailySchedule->start_time);
                if ($scheduledEndTime->lessThanOrEqualTo($scheduledStartTime)) {
                    $scheduledEnd->addDay();
                }
            }

            $diff = abs($scheduledEnd->diffInMinutes($actualEndTime, false));

            if ($bestDiff === null || $diff < $bestDiff) {
                $bestDiff = $diff;
                $bestScheduledEnd = $scheduledEnd;
                $bestScheduledEndTime = $scheduledEndTime;
                $bestDailySchedule = $dailySchedule;
            }
        }

        if ($bestScheduledEnd && $bestDailySchedule) {
            $breakMinutes = $bestDailySchedule->break_duration ?? 0;
            $bestScheduledEnd = $bestScheduledEnd->copy()->addMinutes($breakMinutes);
        }

        return [
            'scheduled_end' => $bestScheduledEnd,
            'scheduled_end_time' => $bestScheduledEndTime,
        ];
    }

    public function calculateOvertime(User $user, CarbonInterface $actualEndTime): array
    {
        $resolved = $this->resolveScheduledEndForEndTime($user, $actualEndTime);

        if (!$resolved['scheduled_end'] || !$resolved['scheduled_end_time']) {
            return ['overtime_minutes' => null, 'scheduled_end_time' => null];
        }

        $overtimeMinutes = $resolved['scheduled_end']->diffInMinutes($actualEndTime, false);

        if ($overtimeMinutes <= 0) {
            return ['overtime_minutes' => 0, 'scheduled_end_time' => $resolved['scheduled_end_time']];
        }

        return [
            'overtime_minutes' => (int)$overtimeMinutes,
            'scheduled_end_time' => $resolved['scheduled_end_time'],
        ];
    }
}
