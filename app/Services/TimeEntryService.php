<?php

namespace App\Services;

use App\Repositories\TimeEntryRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TimeEntryService
{

    public function __construct(protected TimeEntryRepository $timeEntryRepository)
    {
    }

    public function startTimeEntry(?string $comment = null)
    {
        $userId = Auth::id();
        $activeEntry = $this->timeEntryRepository->getActiveEntryForUser($userId);

        if ($activeEntry) {
            throw new Exception('You already have an active time entry. Please stop it before starting a new one.');
        }

        return $this->timeEntryRepository->create([
            'user_id' => $userId,
            'start_time' => now(),
            'comment' => $comment,
        ]);
    }

    public function stopTimeEntry(?string $comment = null)
    {
        $userId = Auth::id();
        $activeEntry = $this->timeEntryRepository->getActiveEntryForUser($userId);

        if (!$activeEntry) {
            throw new NotFoundHttpException('You do not have an active time entry to stop.');
        }

        $startTime = $activeEntry->start_time;
        $stopTime = now();
        $duration = abs((int)$stopTime->diffInSeconds($startTime));

        $data = [
            'stop_time' => $stopTime,
            'duration' => $duration,
        ];

        if ($comment) {
            $data['comment'] = $comment;
        }

        return $this->timeEntryRepository->update($activeEntry->id, $data);
    }

    public function getTimeEntries(): Collection
    {
        return $this->timeEntryRepository->getAllForUser(Auth::id());
    }

    public function getTimeSummary(): array
    {
        $userId = Auth::id();
        $completedEntries = $this->timeEntryRepository->getSummaryForUser($userId);

        $totalMinutes = $completedEntries->sum(function ($entry) {
            return Carbon::parse($entry->start_time)
                ->diffInMinutes(Carbon::parse($entry->stop_time));
        });

        $totalHours = round($totalMinutes / 60, 2);
        $entriesCount = $completedEntries->count();
        $averageWorkTime = $entriesCount > 0 ? round($totalMinutes / $entriesCount, 2) : 0;

        return [
            'user_id' => $userId,
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

    public function deleteTimeEntry(int $id): ?bool
    {
        $timeEntry = $this->timeEntryRepository->getById($id);

        if ($timeEntry->user_id !== Auth::id()) {
            throw new AccessDeniedHttpException('You do not have permission to delete this time entry.');
        }

        return $this->timeEntryRepository->delete($id);
    }
}
