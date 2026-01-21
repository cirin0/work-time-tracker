<?php

namespace App\Services;

use App\Models\TimeEntry;
use App\Models\User;
use App\Repositories\TimeEntryRepository;
use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class TimeEntryService
{
    public function __construct(protected TimeEntryRepository $timeEntryRepository)
    {
    }

    public function startTimeEntry(User $user, array $data): array
    {
        $activeEntry = $this->timeEntryRepository->getActiveEntryForUser($user);

        if ($activeEntry) {
            abort(400, 'An active time entry already exists. Please stop it before starting a new one.');
        }

        $timeEntry = $this->timeEntryRepository->create($user, [
            'start_time' => now(),
            'comment' => $data['comment'] ?? null,
        ]);

        return ['time_entry' => $timeEntry];
    }

    public function stopTimeEntry(User $user, TimeEntry $timeEntry, array $data): array
    {
        if ($timeEntry->user_id !== $user->id) {
            throw new AccessDeniedHttpException('You do not have permission to stop this time entry.');
        }

        if ($timeEntry->stop_time !== null) {
            abort(400, 'The time entry is already stopped.');
        }

        $startTime = $timeEntry->start_time;
        $stopTime = now();
        $duration = abs((int)$stopTime->diffInSeconds($startTime));

        $updateData = [
            'stop_time' => $stopTime,
            'duration' => $duration,
        ];

        if (isset($data['comment'])) {
            $updateData['comment'] = $data['comment'];
        }

        $updatedTimeEntry = $this->timeEntryRepository->update($timeEntry, $updateData);

        return ['time_entry' => $updatedTimeEntry];
    }

    public function getUserTimeEntries(User $user): array
    {
        $timeEntries = $this->timeEntryRepository->getAllForUser($user);

        return ['time_entries' => $timeEntries];
    }

    public function getActiveTimeEntry(User $user): array
    {
        $activeEntry = $this->timeEntryRepository->getActiveEntryForUser($user);

        if (!$activeEntry) {
            abort(404, 'No active time entry found for the user.');
        }

        return ['time_entry' => $activeEntry];
    }

    /*
     * TODO:
     * AccessDeniedHttpException видає помолку, можна використати в LeaveRequestService
     */

    public function getTimeEntryById(User $user, TimeEntry $timeEntry): array
    {
        if ($timeEntry->user_id !== $user->id) {
            throw new AccessDeniedHttpException('You do not have permission to view this time entry.');
        }
        $timeEntry = $this->timeEntryRepository->getById($timeEntry->id);

        return ['time_entry' => $timeEntry];
    }

    public function getTimeSummary(User $user): array
    {
        $completedEntries = $this->timeEntryRepository->getSummaryForUser($user);

        $totalMinutes = $completedEntries->sum(function ($entry) {
            return Carbon::parse($entry->start_time)
                ->diffInMinutes(Carbon::parse($entry->stop_time));
        });

        $totalHours = round($totalMinutes / 60, 2);
        $entriesCount = $completedEntries->count();
        $averageWorkTime = $entriesCount > 0 ? round($totalMinutes / $entriesCount, 2) : 0;

        return [
            'user_id' => $user->id,
            'total_hours' => $totalHours,
            'total_minutes' => $totalMinutes,
            'entries_count' => $entriesCount,
            'average_work_time' => $averageWorkTime,
            'summary' => [
                'today' => $completedEntries->where('start_time', '>=', Carbon::today())->sum(function ($entry) {
                    return Carbon::parse($entry->start_time)
                        ->diffInMinutes(Carbon::parse($entry->stop_time));
                }),
                'week' => $completedEntries->where('start_time', '>=', Carbon::now()->startOfWeek())->sum(function ($entry) {
                    return Carbon::parse($entry->start_time)
                        ->diffInMinutes(Carbon::parse($entry->stop_time));
                }),
                'month' => $completedEntries->where('start_time', '>=', Carbon::now()->startOfMonth())->sum(function ($entry) {
                    return Carbon::parse($entry->start_time)
                        ->diffInMinutes(Carbon::parse($entry->stop_time));
                }),
            ],
        ];
    }

    public function deleteTimeEntry(User $user, TimeEntry $timeEntry): ?bool
    {
        if ($timeEntry->user_id !== $user->id) {
            throw new AccessDeniedHttpException('You do not have permission to delete this time entry.');
        }

        return $this->timeEntryRepository->delete($timeEntry);
    }
}
