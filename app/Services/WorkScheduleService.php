<?php

namespace App\Services;

use App\Models\DailySchedule;
use App\Models\WorkSchedule;
use App\Repositories\WorkScheduleRepository;

class WorkScheduleService
{
    public function __construct(protected WorkScheduleRepository $workScheduleRepository)
    {
    }

    public function getAllWorkSchedulesById(mixed $companyId): array
    {
        $schedules = $this->workScheduleRepository->getAllForCompany($companyId);

        return ['schedules' => $schedules];
    }

    public function create(mixed $data): array
    {
        if ($data['is_default'] ?? false) {
            $this->resetDefaultWorkSchedule($data['company_id']);
        }

        $dailySchedulesData = $this->extractDailySchedulesData($data);
        unset($data['daily_schedules']);

        $workSchedule = $this->workScheduleRepository->create($data);

        $this->createDailySchedules($workSchedule, $dailySchedulesData);

        return ['work_schedule' => $workSchedule->load('dailySchedules')];
    }

    private function resetDefaultWorkSchedule(int $companyId): void
    {
        WorkSchedule::query()
            ->where('company_id', $companyId)
            ->where('is_default', true)
            ->update(['is_default' => false]);
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

    public function delete(WorkSchedule $workSchedule, ?int $companyId = null): array
    {
        if ($companyId !== null && $workSchedule->company_id !== $companyId) {
            return ['message' => 'Work schedule not found'];
        }

        $deleted = $this->workScheduleRepository->delete($workSchedule);

        return ['deleted' => $deleted];
    }

    public function update(WorkSchedule $workSchedule, mixed $data, ?int $companyId = null): array
    {
        if ($companyId !== null && $workSchedule->company_id !== $companyId) {
            return ['message' => 'Work schedule not found'];
        }

        if (isset($data['is_default']) && $data['is_default'] ?? false) {
            $this->resetDefaultWorkSchedule($data['company_id']);
        }

        $this->workScheduleRepository->update($workSchedule, $data);

        return ['work_schedule' => $workSchedule->fresh()];
    }

    public function getWorkScheduleById(string $id, ?int $companyId = null): array
    {
        $workSchedule = $this->workScheduleRepository->find($id);
        if (! $workSchedule) {
            return ['message' => 'Work schedule not found'];
        }

        if ($companyId !== null && $workSchedule->company_id !== $companyId) {
            return ['message' => 'Work schedule not found'];
        }

        return ['work_schedule' => $workSchedule];
    }
}
