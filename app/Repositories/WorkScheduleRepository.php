<?php

namespace App\Repositories;

use App\Models\WorkSchedule;
use Illuminate\Database\Eloquent\Collection;

class WorkScheduleRepository
{
    public function find(int $id): ?WorkSchedule
    {
        return WorkSchedule::query()
            ->with('dailySchedules')
            ->find($id);
    }

    public function getAll(): Collection
    {
        return WorkSchedule::query()->get();
    }

    public function getAllForCompany(int $companyId): Collection
    {
        return WorkSchedule::query()
            ->with('dailySchedules')
            ->where('company_id', $companyId)
            ->get();
    }

    public function create(array $data): WorkSchedule
    {
        return WorkSchedule::query()->create($data);
    }

    public function update(WorkSchedule $workSchedule, array $data): bool
    {
        return $workSchedule->update($data);
    }

    public function delete(WorkSchedule $workSchedule): ?bool
    {
        return $workSchedule->delete();
    }
}
