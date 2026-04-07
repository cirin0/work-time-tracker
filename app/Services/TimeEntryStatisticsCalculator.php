<?php

namespace App\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;

class TimeEntryStatisticsCalculator
{
    public function __construct(
        protected CacheService $cacheService
    )
    {
    }

    public function calculateStatistics(Collection $completedEntries, int $userId): array
    {
        $totalMinutes = $this->calculateTotalMinutes($completedEntries);
        $workingDays = $this->countUniqueDays($completedEntries);

        return [
            'user_id' => $userId,
            'total_hours' => (int)floor($totalMinutes / 60),
            'total_minutes' => (int)($totalMinutes % 60),
            'working_days' => $workingDays,
            'average_work_time' => $workingDays > 0 ? (int)round($totalMinutes / $workingDays) : 0,
            'attendance' => $this->calculateAttendanceStatistics($completedEntries),
            'summary' => $this->calculatePeriodSummaries($completedEntries),
        ];
    }

    private function calculateTotalMinutes(Collection $entries): int
    {
        $grouped = $entries->groupBy(function ($entry) {
            $userId = $entry->user_id;
            $date = is_string($entry->date) ? $entry->date : $entry->date->format('Y-m-d');
            return "{$userId}_{$date}";
        });

        $totalMinutes = 0;

        foreach ($grouped as $dayEntries) {
            $dayWorkedMinutes = $dayEntries->sum(function ($entry) {
                return round($entry->duration / 60);
            });

            $breakMinutes = $this->getBreakDurationForEntry($dayEntries->first());

            if ($dayWorkedMinutes > $breakMinutes) {
                $totalMinutes += $dayWorkedMinutes - $breakMinutes;
            } else {
                $totalMinutes += $dayWorkedMinutes;
            }
        }

        return $totalMinutes;
    }

    private function getBreakDurationForEntry($entry): int
    {
        if (!$entry->user || !$entry->user->work_schedule_id) {
            return 0;
        }

        try {
            $workSchedule = $this->cacheService->getWorkSchedule($entry->user->work_schedule_id);

            if (!$workSchedule) {
                return 0;
            }

            $dayOfWeek = strtolower(Carbon::parse($entry->date)->format('l'));
            $dailySchedule = $workSchedule->getDailySchedule($dayOfWeek);

            if (!$dailySchedule || !$dailySchedule->is_working_day) {
                return 0;
            }

            return $dailySchedule->break_duration ?? 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    private function countUniqueDays(Collection $entries): int
    {
        return $entries->pluck('date')
            ->map(fn($date) => is_string($date) ? $date : $date->format('Y-m-d'))
            ->unique()
            ->count();
    }

    private function calculateAttendanceStatistics(Collection $completedEntries): array
    {
        $entriesWithSchedule = $completedEntries->whereNotNull('lateness_minutes');
        $lateCount = $entriesWithSchedule->where('lateness_minutes', '>', 0)->count();
        $earlyCount = $entriesWithSchedule->where('lateness_minutes', '<', 0)->count();
        $onTimeCount = $entriesWithSchedule->where('lateness_minutes', '=', 0)->count();
        $totalLateMinutes = $entriesWithSchedule->where('lateness_minutes', '>', 0)->sum('lateness_minutes');
        $averageLateMinutes = $lateCount > 0 ? round($totalLateMinutes / $lateCount, 1) : 0;

        $entriesWithEarlyLeave = $completedEntries->whereNotNull('early_leave_minutes');
        $earlyLeaveCount = $entriesWithEarlyLeave->where('early_leave_minutes', '>', 0)->count();
        $totalEarlyLeaveMinutes = $entriesWithEarlyLeave->where('early_leave_minutes', '>', 0)->sum('early_leave_minutes');
        $averageEarlyLeaveMinutes = $earlyLeaveCount > 0 ? round($totalEarlyLeaveMinutes / $earlyLeaveCount, 1) : 0;

        $entriesWithOvertime = $completedEntries->whereNotNull('overtime_minutes');
        $overtimeCount = $entriesWithOvertime->where('overtime_minutes', '>', 0)->count();
        $totalOvertimeMinutes = $entriesWithOvertime->where('overtime_minutes', '>', 0)->sum('overtime_minutes');
        $averageOvertimeMinutes = $overtimeCount > 0 ? round($totalOvertimeMinutes / $overtimeCount, 1) : 0;

        return [
            'late_count' => $lateCount,
            'early_count' => $earlyCount,
            'on_time_count' => $onTimeCount,
            'total_late_minutes' => (int)$totalLateMinutes,
            'average_late_minutes' => $averageLateMinutes,
            'early_leave_count' => $earlyLeaveCount,
            'total_early_leave_minutes' => (int)$totalEarlyLeaveMinutes,
            'average_early_leave_minutes' => $averageEarlyLeaveMinutes,
            'overtime_count' => $overtimeCount,
            'total_overtime_minutes' => (int)$totalOvertimeMinutes,
            'average_overtime_minutes' => $averageOvertimeMinutes,
        ];
    }

    private function calculatePeriodSummaries(Collection $completedEntries): array
    {
        return [
            'today' => $this->calculatePeriodSummary(
                $completedEntries->where('start_time', '>=', Carbon::today())
            ),
            'week' => $this->calculatePeriodSummary(
                $completedEntries->where('start_time', '>=', Carbon::now()->startOfWeek())
            ),
            'month' => $this->calculatePeriodSummary(
                $completedEntries->where('start_time', '>=', Carbon::now()->startOfMonth())
            ),
        ];
    }

    private function calculatePeriodSummary(Collection $entries): array
    {
        $totalMinutes = $this->calculateTotalMinutes($entries);
        $workingDays = $this->countUniqueDays($entries);

        return [
            'hours' => (int)floor($totalMinutes / 60),
            'minutes' => $totalMinutes % 60,
            'working_days' => $workingDays,
            'late_count' => $entries->where('lateness_minutes', '>', 0)->count(),
            'early_count' => $entries->where('lateness_minutes', '<', 0)->count(),
        ];
    }

    public function calculateCompanyStatistics(Collection $completedEntries, Collection $activeEntries, int $companyId, int $employeeCount): array
    {
        $totalMinutes = $this->calculateTotalMinutes($completedEntries);
        $workingDays = $this->countUniqueDays($completedEntries);
        $totalEntriesCount = $completedEntries->count();
        $employeesWithEntries = $completedEntries->pluck('user_id')->unique()->count();
        $averageWorkingDaysPerEmployee = $employeesWithEntries > 0
            ? round($workingDays / $employeesWithEntries, 1)
            : 0;

        return [
            'company_id' => $companyId,
            'employee_count' => $employeeCount,
            'total_hours' => round($totalMinutes / 60, 2),
            'total_minutes' => $totalMinutes,
            'total_entries_count' => $totalEntriesCount,
            'total_working_days' => $workingDays,
            'average_working_days_per_employee' => $averageWorkingDaysPerEmployee,
            'active_entries_count' => $activeEntries->count(),
            'active_employees' => $activeEntries->pluck('user_id')->unique()->count(),
            'total_employees_with_entries' => $employeesWithEntries,
            'attendance' => $this->calculateAttendanceStatistics($completedEntries),
            'summary' => $this->calculatePeriodSummaries($completedEntries),
        ];
    }
}
