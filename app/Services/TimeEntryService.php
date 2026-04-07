<?php

namespace App\Services;

use App\Enums\EntryType;
use App\Enums\LeaveRequestStatus;
use App\Enums\LeaveRequestType;
use App\Enums\WorkMode;
use App\Models\LeaveRequest;
use App\Models\TimeEntry;
use App\Models\User;
use App\Repositories\TimeEntryRepository;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class TimeEntryService
{
    public function __construct(
        protected TimeEntryRepository           $timeEntryRepository,
        protected LatenessCalculator            $latenessCalculator,
        protected GpsDistanceCalculator         $gpsDistanceCalculator,
        protected QrCodeValidator               $qrCodeValidator,
        protected TimeEntryStatisticsCalculator $statisticsCalculator,
        protected CacheService                  $cacheService
    )
    {
    }

    public function startTimeEntry(User $user, array $data): array
    {
        if (empty($user->pin_code)) {
            throw new BadRequestHttpException('You must set up a PIN code before starting work.');
        }

        if (!$user->isAdmin() && empty($user->company_id)) {
            throw new BadRequestHttpException('You must be assigned to a company before starting work.');
        }

        $activeEntry = $this->timeEntryRepository->getActiveEntryForUser($user);

        if ($activeEntry) {
            throw new BadRequestHttpException('An active time entry already exists. Please stop it before starting a new one.');
        }

        $today = now()->format('Y-m-d');
        $approvedLeave = LeaveRequest::query()
            ->where('user_id', $user->id)
            ->where('status', LeaveRequestStatus::APPROVED)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->first();

        $isOnBusinessTrip = false;

        if ($approvedLeave) {
            if (in_array($approvedLeave->type, [
                LeaveRequestType::SICK,
                LeaveRequestType::VACATION,
                LeaveRequestType::UNPAID,
                LeaveRequestType::PERSONAL,
            ])) {
                throw new BadRequestHttpException('You cannot clock in during approved leave.');
            }

            if ($approvedLeave->type === LeaveRequestType::BUSINESS_TRIP) {
                $isOnBusinessTrip = true;
            }
        }

        $hasGpsData = isset($data['latitude']) && isset($data['longitude']);
        $isAdmin = $user->isAdmin();

        if ($isAdmin && !$hasGpsData) {
            $entryType = EntryType::MANUAL;
        } elseif ($isOnBusinessTrip) {
            $entryType = $hasGpsData ? EntryType::REMOTE : EntryType::MANUAL;
        } else {
            $entryType = match ($user->work_mode) {
                WorkMode::OFFICE => EntryType::GPS_QR,
                WorkMode::REMOTE => EntryType::REMOTE,
                WorkMode::HYBRID => $hasGpsData ? EntryType::GPS : EntryType::MANUAL,
            };
        }

        if (!$isAdmin && $user->work_mode === WorkMode::OFFICE && !$isOnBusinessTrip) {
            $company = $this->cacheService->getCompany($user->company_id);

            $distance = $this->gpsDistanceCalculator->calculate(
                (float)$data['latitude'],
                (float)$data['longitude'],
                (float)$company->latitude,
                (float)$company->longitude
            );

            if ($distance > $company->radius_meters) {
                return ['message' => 'You are not within the company radius.'];
            }

            if (!isset($data['qr_code']) || !$this->qrCodeValidator->verify($company, $data['qr_code'])) {
                throw new BadRequestHttpException('Invalid or expired QR code.');
            }
        }

        $now = now();

        $isFirstEntryOfDay = !$this->timeEntryRepository->hasEntryForDate($user->id, $now->format('Y-m-d'));

        $latenessData = $isFirstEntryOfDay
            ? $this->latenessCalculator->calculate($user, $now)
            : ['lateness_minutes' => null, 'scheduled_start_time' => null];

        if (!$isFirstEntryOfDay) {
            TimeEntry::query()
                ->where('user_id', $user->id)
                ->whereDate('date', $now->format('Y-m-d'))
                ->whereNotNull('stop_time')
                ->update([
                    'early_leave_minutes' => null,
                    'overtime_minutes' => null,
                    'scheduled_end_time' => null,
                ]);
        }

        $timeEntry = $this->timeEntryRepository->create([
            'user_id' => $user->id,
            'date' => $now->format('Y-m-d'),
            'start_time' => $now,
            'entry_type' => $entryType,
            'start_comment' => $data['start_comment'] ?? null,
            'lateness_minutes' => $latenessData['lateness_minutes'],
            'scheduled_start_time' => $latenessData['scheduled_start_time'],
            'location_data' => $hasGpsData ? [
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
            ] : null,
        ]);

        return ['time_entry' => $timeEntry];
    }

    public function stopActiveTimeEntry(User $user, array $data): array
    {
        if (!Hash::check($data['pin_code'], $user->pin_code)) {
            throw new BadRequestHttpException('Invalid pin code.');
        }

        $activeEntry = $this->timeEntryRepository->getActiveEntryForUser($user);

        if (!$activeEntry) {
            throw new BadRequestHttpException('No active time entry found.');
        }

        $startTime = $activeEntry->start_time;
        $stopTime = now();
        $duration = abs((int)$stopTime->diffInSeconds($startTime));

        $earlyLeaveData = $this->latenessCalculator->calculateEarlyLeave($user, $stopTime);
        $overtimeData = $this->latenessCalculator->calculateOvertime($user, $stopTime);

        $updateData = [
            'stop_time' => $stopTime,
            'duration' => $duration,
            'early_leave_minutes' => $earlyLeaveData['early_leave_minutes'],
            'scheduled_end_time' => $earlyLeaveData['scheduled_end_time'],
            'overtime_minutes' => $overtimeData['overtime_minutes'],
        ];

        if (isset($data['stop_comment'])) {
            $updateData['stop_comment'] = $data['stop_comment'];
        }

        $this->timeEntryRepository->update($activeEntry, $updateData);

        return ['time_entry' => $activeEntry->fresh()];
    }

    public function getUserTimeEntries(User $user, ?int $perPage = null): array
    {
        $timeEntries = $this->timeEntryRepository->getAllForUser($user, $perPage);

        return ['time_entries' => $timeEntries];
    }

    public function getActiveTimeEntry(User $user): array
    {
        $activeEntry = $this->timeEntryRepository->getActiveEntryForUser($user);

        return ['time_entry' => $activeEntry];
    }

    public function getTimeEntryById(User $user, TimeEntry $timeEntry): array
    {
        if ($timeEntry->user_id !== $user->id) {
            throw new AccessDeniedHttpException('You do not have permission to view this time entry.');
        }

        $entry = $this->timeEntryRepository->find($timeEntry->id);

        return ['time_entry' => $entry];
    }

    public function getTimeSummary(User $user): array
    {
        $completedEntries = $this->timeEntryRepository->getCompletedForUser($user);

        return $this->statisticsCalculator->calculateStatistics($completedEntries, $user->id);
    }

    public function deleteTimeEntry(User $user, TimeEntry $timeEntry): array
    {
        if (!$user->isAdmin() && $timeEntry->user_id !== $user->id) {
            throw new AccessDeniedHttpException('You do not have permission to delete this time entry.');
        }

        $this->timeEntryRepository->delete($timeEntry);

        return ['success' => true];
    }

    public function getUserTimeEntriesById(int $userId): array
    {
        $timeEntries = $this->timeEntryRepository->getAllForUserById($userId);

        return ['time_entries' => $timeEntries];
    }

    public function getTimeSummaryById(int $userId): array
    {
        $completedEntries = $this->timeEntryRepository->getCompletedForUserById($userId);

        return $this->statisticsCalculator->calculateStatistics($completedEntries, $userId);
    }

    public function getTimeEntriesForExport(User $user, ?string $from = null, ?string $to = null): array
    {
        $entries = $this->timeEntryRepository->getEntriesForExport($user->id, $from, $to);

        return ['time_entries' => $entries];
    }

    public function getAllEmployeeStatistics(int $companyId, int $perPage = 15): array
    {
        $paginatedUsers = User::query()
            ->where('company_id', $companyId)
            ->paginate($perPage);

        $userIds = $paginatedUsers->pluck('id')->toArray();
        $entries = $this->timeEntryRepository->getCompletedForUsers($userIds);
        $grouped = $entries->groupBy('user_id');

        $stats = [];
        foreach ($paginatedUsers as $user) {
            $userEntries = $grouped->get($user->id, collect());
            // Only add to statistics if there are entries, to maintain old behavior
            if ($userEntries->isNotEmpty()) {
                $userStats = $this->statisticsCalculator->calculateStatistics($userEntries, $user->id);
                $userStats['user'] = $user;
                $stats[] = $userStats;
            }
        }

        return [
            'statistics' => $stats,
            'pagination' => [
                'current_page' => $paginatedUsers->currentPage(),
                'last_page' => $paginatedUsers->lastPage(),
                'total' => $paginatedUsers->total(),
                'per_page' => $paginatedUsers->perPage(),
            ]
        ];
    }

    public function getCompanyStatistics(int $companyId): array
    {
        $completedEntries = $this->timeEntryRepository->getCompletedForCompany($companyId);
        $activeEntries = $this->timeEntryRepository->getActiveForCompany($companyId);

        $employeeCount = User::query()->where('company_id', $companyId)->count();

        return $this->statisticsCalculator->calculateCompanyStatistics(
            $completedEntries,
            $activeEntries,
            $companyId,
            $employeeCount
        );
    }

    public function getActiveCompanyEntries(int $companyId): array
    {
        $entries = $this->timeEntryRepository->getActiveForCompany($companyId);

        return ['time_entries' => $entries];
    }
}
