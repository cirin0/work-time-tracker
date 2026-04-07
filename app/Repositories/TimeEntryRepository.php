<?php

namespace App\Repositories;

use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

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

    public function getTodayEntriesForUser(User $user): Collection
    {
        return TimeEntry::query()
            ->with('user')
            ->where('user_id', $user->id)
            ->whereDate('date', today())
            ->orderBy('start_time')
            ->get();
    }

    public function getAllForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return TimeEntry::query()
            ->with('user')
            ->where('user_id', $user->id)
            ->orderBy('start_time', 'desc')
            ->paginate($perPage);
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

    public function getAllForUserById(int $userId): Collection
    {
        return TimeEntry::query()
            ->with('user')
            ->where('user_id', $userId)
            ->orderBy('start_time', 'desc')
            ->get();
    }

    public function getCompletedForUserById(int $userId): Collection
    {
        return TimeEntry::query()
            ->with('user')
            ->where('user_id', $userId)
            ->whereNotNull('stop_time')
            ->get();
    }

    public function getCompletedForCompany(int $companyId): Collection
    {
        return TimeEntry::query()
            ->with('user')
            ->whereHas('user', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->whereNotNull('stop_time')
            ->get();
    }

    public function getCompletedForUsers(array $userIds): Collection
    {
        return TimeEntry::query()
            ->with('user')
            ->whereIn('user_id', $userIds)
            ->whereNotNull('stop_time')
            ->get();
    }

    public function getActiveForCompany(int $companyId): Collection
    {
        return TimeEntry::query()
            ->with('user')
            ->whereHas('user', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->whereNull('stop_time')
            ->get();
    }

    public function getEntriesForExport(int $userId, ?string $from = null, ?string $to = null): Collection
    {
        return TimeEntry::query()
            ->with('user')
            ->where('user_id', $userId)
            ->when($from, fn($q) => $q->whereDate('date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('date', '<=', $to))
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();
    }

    public function hasEntryForDate(int $userId, string $date): bool
    {
        return TimeEntry::query()
            ->where('user_id', $userId)
            ->whereDate('date', $date)
            ->exists();
    }

    public function getActiveEntriesForDate(int $userId, string $date, ?int $excludeEntryId = null): Collection
    {
        return TimeEntry::query()
            ->where('user_id', $userId)
            ->whereDate('date', $date)
            ->whereNull('stop_time')
            ->when($excludeEntryId, fn($q) => $q->where('id', '!=', $excludeEntryId))
            ->get();
    }
}
