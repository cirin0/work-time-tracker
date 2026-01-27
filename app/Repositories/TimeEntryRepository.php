<?php

namespace App\Repositories;

use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class TimeEntryRepository
{
    public function getActiveEntryForUser(User $user): ?TimeEntry
    {
        return $user->timeEntries()->whereNull('stop_time')->first();
    }

    public function create(User $user, array $data): TimeEntry
    {
        return TimeEntry::query()->create(array_merge($data, [
            'user_id' => $user->id,
        ]));
    }

    public function update(TimeEntry $timeEntry, array $data): TimeEntry
    {
        $timeEntry->update($data);

        return $timeEntry->fresh();
    }

    public function delete(TimeEntry $timeEntry): ?bool
    {
        return $timeEntry->delete();
    }

    public function getAllForUser(User $user): Collection
    {
        return TimeEntry::query()
            ->where('user_id', $user->id)
            ->orderBy('start_time', 'desc')
            ->get();
    }

    public function getSummaryForUser(User $user): Collection
    {
        return TimeEntry::query()
            ->where('user_id', $user->id)
            ->whereNotNull('stop_time')
            ->get();
    }

    public function getById(int $id): ?TimeEntry
    {
        return TimeEntry::query()->find($id);
    }
}
