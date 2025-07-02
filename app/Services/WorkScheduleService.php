<?php

namespace App\Services;

use App\Models\DailySchedule;
use App\Models\WorkSchedule;
use App\Repositories\WorkScheduleRepository;
use Illuminate\Database\Eloquent\Collection;

class WorkScheduleService
{
    public function __construct(protected WorkScheduleRepository $workScheduleRepository)
    {
    }

    public function getAllWorkSchedulesById(mixed $companyId): Collection
    {
        return $this->workScheduleRepository->getAllWorkSchedules($companyId);
    }

    public function create(mixed $data)
    {
        if ($data['is_default'] ?? false) {
            $this->workScheduleRepository->updateDefaultWorkSchedule($data['company_id']);
        }

        $dailySchedulesData = $this->extractDailySchedulesData($data);
        unset($data['daily_schedules']);

        $workSchedule = $this->workScheduleRepository->create($data);

        $this->createDailySchedules($workSchedule, $dailySchedulesData);

        return $workSchedule->load('dailySchedules');
    }

    private function extractDailySchedulesData(array $data): array
    {
        $dailySchedules = $data['daily_schedules'] ?? [];

        if (empty($dailySchedules)) {
            $daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
            $defaultStartTime = $data['start_time'] ?? '09:00:00';
            $defaultEndTime = $data['end_time'] ?? '18:00:00';
            $defaultBreakDuration = $data['break_duration'] ?? 60;

            foreach ($daysOfWeek as $day) {
                $dailySchedules[] = [
                    'day_of_week' => $day,
                    'start_time' => $defaultStartTime,
                    'end_time' => $defaultEndTime,
                    'break_duration' => $defaultBreakDuration,
                    'is_working_day' => true
                ];
            }

            $dailySchedules[] = [
                'day_of_week' => 'saturday',
                'start_time' => $defaultStartTime,
                'end_time' => $defaultEndTime,
                'break_duration' => $defaultBreakDuration,
                'is_working_day' => false
            ];

            $dailySchedules[] = [
                'day_of_week' => 'sunday',
                'start_time' => $defaultStartTime,
                'end_time' => $defaultEndTime,
                'break_duration' => $defaultBreakDuration,
                'is_working_day' => false
            ];
        }
        return $dailySchedules;
    }

    private function createDailySchedules(WorkSchedule $workSchedule, array $dailySchedulesData): void
    {
        foreach ($dailySchedulesData as $schedulesData) {
            $schedulesData['work_schedule_id'] = $workSchedule->id;
            DailySchedule::query()->create($schedulesData);
        }
    }

    public function delete(string $id): bool
    {
        return $this->workScheduleRepository->delete($id);
    }

    public function update(string $id, mixed $data): WorkSchedule|string
    {
        if (isset($data['is_default']) && $data['is_default'] ?? false) {
            $this->workScheduleRepository->updateDefaultWorkSchedule($data['company_id']);
        }
        return $this->workScheduleRepository->update($id, $data);
    }

    public function getWorkScheduleById(string $id): ?WorkSchedule
    {
        $workSchedule = $this->workScheduleRepository->find($id);
        if (!$workSchedule) {
            abort(404, 'Work schedule not found');
        }
        return $workSchedule;
    }
}
