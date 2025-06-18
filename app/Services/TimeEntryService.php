<?php

namespace App\Services;

use App\Repositories\TimeEntryRepository;
use Exception;
use Illuminate\Database\Eloquent\Collection;

class TimeEntryService
{

    public function __construct(protected TimeEntryRepository $timeEntryRepository)
    {
    }

    public function startTimeEntry(int $userId, ?string $comment = null)
    {
        // Check if user already has an active time entry
        $activeEntry = $this->timeEntryRepository->getActiveEntryForUser($userId);

        if ($activeEntry) {
            throw new Exception('You already have an active time entry. Please stop it before starting a new one.');
        }

        // Create a new time entry
        return $this->timeEntryRepository->create([
            'user_id' => $userId,
            'start_time' => now(),
            'comment' => $comment,
        ]);
    }

    public function stopTimeEntry(int $userId, ?string $comment = null)
    {
        // Get the active time entry for the user
        $activeEntry = $this->timeEntryRepository->getActiveEntryForUser($userId);

        if (!$activeEntry) {
            throw new \Exception('You do not have an active time entry to stop.');
        }

        // Calculate duration in seconds
        $startTime = $activeEntry->start_time;
        $stopTime = now();
        $duration = abs((int)$stopTime->diffInSeconds($startTime));

        // Update the time entry
        $data = [
            'stop_time' => $stopTime,
            'duration' => $duration,
        ];

        // Update comment if provided
        if ($comment) {
            $data['comment'] = $comment;
        }

        return $this->timeEntryRepository->update($activeEntry->id, $data);
    }

    public function getTimeEntries(int $userId): Collection
    {
        return $this->timeEntryRepository->getAllForUser($userId);
    }

    public function getTimeSummary(int $userId): array
    {
        return $this->timeEntryRepository->getSummaryForUser($userId);
    }

    public function deleteTimeEntry(int $id, int $userId): ?bool
    {
        $timeEntry = $this->timeEntryRepository->getById($id);

        // Check if the time entry belongs to the user
        if ($timeEntry->user_id !== $userId) {
            throw new \Exception('You do not have permission to delete this time entry.');
        }

        return $this->timeEntryRepository->delete($id);
    }
}
