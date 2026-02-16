<?php

namespace App\Repositories;

use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class TimeEntryRepository
{
    public function find(int $id): ?TimeEntry
    {
        return TimeEntry::query()
            ->with('user')
            ->find($id);
    }

    public function getActiveEntryForUser(User $user): ?TimeEntry
    {
        return TimeEntry::query()
            ->with('user')
            ->where('user_id', $user->id)
            ->whereNull('stop_time')
            ->first();
    }

    public function getAllForUser(User $user): Collection
    {
        return TimeEntry::query()
            ->with('user')
            ->where('user_id', $user->id)
            ->orderBy('start_time', 'desc')
            ->get();
    }

    public function getCompletedForUser(User $user): Collection
    {
        return TimeEntry::query()
            ->with('user')
            ->where('user_id', $user->id)
            ->whereNotNull('stop_time')
            ->get();
    }

    public function create(array $data): TimeEntry
    {
        return TimeEntry::query()->create($data);
    }

    public function update(TimeEntry $timeEntry, array $data): bool
    {
        return $timeEntry->update($data);
    }

    public function delete(TimeEntry $timeEntry): ?bool
    {
        return $timeEntry->delete();
    }
}
