<?php

namespace App\Repositories;

use App\Models\TimeEntry;
use Illuminate\Database\Eloquent\Collection;

class TimeEntryRepository
{
    public function getActiveEntryForUser(int $userId)
    {
        return TimeEntry::query()->where('user_id', $userId)
            ->whereNull('stop_time')
            ->first();
    }

    public function create(array $data)
    {
        return TimeEntry::query()->create($data);
    }

    public function update(int $id, array $data)
    {
        $timeEntry = TimeEntry::query()->findOrFail($id);
        $timeEntry->update($data);
        return $timeEntry;
    }

    public function delete(int $id): ?bool
    {
        $timeEntry = TimeEntry::query()->findOrFail($id);
        return $timeEntry->delete();
    }

    public function getById(int $id)
    {
        return TimeEntry::query()->findOrFail($id);
    }

    public function getAllForUser(int $userId): Collection
    {
        return TimeEntry::query()->where('user_id', $userId)
            ->orderBy('start_time', 'desc')
            ->get();
    }

    public function getSummaryForUser(int $userId): array
    {
        $entries = TimeEntry::query()->where('user_id', $userId)
            ->whereNotNull('stop_time')
            ->get();

        $totalDuration = $entries->sum('duration');
        $todayDuration = $entries->filter(function ($entry) {
            return $entry->start_time->isToday();
        })->sum('duration');

        $weekDuration = $entries->filter(function ($entry) {
            return $entry->start_time->isCurrentWeek();
        })->sum('duration');

        $monthDuration = $entries->filter(function ($entry) {
            return $entry->start_time->isCurrentMonth();
        })->sum('duration');

        return [
            'total' => $totalDuration,
            'today' => $todayDuration,
            'week' => $weekDuration,
            'month' => $monthDuration,
        ];
    }
}
