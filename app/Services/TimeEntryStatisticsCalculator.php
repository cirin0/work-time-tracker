<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class TimeEntryStatisticsCalculator
{
    public function calculateStatistics(Collection $completedEntries, int $userId): array
    {
        $totalMinutes = $this->calculateTotalMinutes($completedEntries);
        $entriesCount = $completedEntries->count();

        return [
            'user_id' => $userId,
            'total_hours' => (int)floor($totalMinutes / 60),
            'total_minutes' => (int)($totalMinutes % 60),
            'entries_count' => $entriesCount,
            'average_work_time' => $entriesCount > 0 ? (int)round($totalMinutes / $entriesCount) : 0,
            'attendance' => $this->calculateAttendanceStatistics($completedEntries),
            'summary' => $this->calculatePeriodSummaries($completedEntries),
        ];
    }

    private function calculateTotalMinutes(Collection $entries): int
    {
        return $entries->sum(function ($entry) {
            return Carbon::parse($entry->start_time)
                ->diffInMinutes(Carbon::parse($entry->stop_time));
        });
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

        return [
            'hours' => (int)floor($totalMinutes / 60),
            'minutes' => (int)($totalMinutes % 60),
            'entries' => $entries->count(),
            'late_count' => $entries->where('lateness_minutes', '>', 0)->count(),
            'early_count' => $entries->where('lateness_minutes', '<', 0)->count(),
        ];
    }

    public function calculateCompanyStatistics(Collection $completedEntries, Collection $activeEntries, int $companyId): array
    {
        $totalMinutes = $this->calculateTotalMinutes($completedEntries);
        $entriesCount = $completedEntries->count();

        return [
            'company_id' => $companyId,
            'total_hours' => round($totalMinutes / 60, 2),
            'total_minutes' => $totalMinutes,
            'entries_count' => $entriesCount,
            'active_entries_count' => $activeEntries->count(),
            'active_employees' => $activeEntries->pluck('user_id')->unique()->count(),
            'total_employees_with_entries' => $completedEntries->pluck('user_id')->unique()->count(),
            'attendance' => $this->calculateAttendanceStatistics($completedEntries),
            'summary' => $this->calculatePeriodSummaries($completedEntries),
        ];
    }
}
