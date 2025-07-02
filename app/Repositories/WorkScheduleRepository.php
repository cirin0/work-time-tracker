<?php

namespace App\Repositories;

use App\Models\WorkSchedule;
use Illuminate\Database\Eloquent\Collection;

class WorkScheduleRepository
{
    public function all()
    {
        return WorkSchedule::all();
    }

    public function find($id): ?WorkSchedule
    {
        return WorkSchedule::with('dailySchedules')->find($id);
    }

    public function create(array $data)
    {
        return WorkSchedule::create($data);
    }

    public function delete($id): bool
    {
        return WorkSchedule::destroy($id);
    }

    public function getAllWorkSchedules($companyId): Collection
    {
        return WorkSchedule::with('dailySchedules')
            ->where('company_id', $companyId)
            ->get();
    }

    public function updateDefaultWorkSchedule($company_id): void
    {
        WorkSchedule::where('company_id', $company_id)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }

    public function update($id, array $data): bool
    {
        return WorkSchedule::findOrFail($id)->update($data);
    }
}
