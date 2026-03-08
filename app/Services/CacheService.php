<?php

namespace App\Services;

use App\Models\Company;
use App\Models\WorkSchedule;
use Illuminate\Support\Facades\Cache;

class CacheService
{
    public function getCompany(int $companyId): ?Company
    {
        return Cache::remember(
            "company:{$companyId}",
            3600,
            fn() => Company::query()->find($companyId)
        );
    }

    public function getWorkSchedule(int $scheduleId): ?WorkSchedule
    {
        return Cache::remember(
            "work_schedule:{$scheduleId}",
            3600,
            fn() => WorkSchedule::query()->with('dailySchedules')->find($scheduleId)
        );
    }

    public function getDailyQrCode(Company $company): array
    {
        $cacheKey = "qr_code:{$company->id}:" . date('Y-m-d');

        return Cache::remember(
            $cacheKey,
            now()->endOfDay(),
            function () use ($company) {
                $dailyToken = hash('sha256', $company->qr_secret . date('Y-m-d'));

                return [
                    'qr_data' => $dailyToken,
                    'expires_at' => now()->endOfDay()->toIso8601String(),
                ];
            }
        );
    }

    public function clearCompanyCache(int $companyId): void
    {
        Cache::forget("company:{$companyId}");
    }

    public function clearWorkScheduleCache(int $scheduleId): void
    {
        Cache::forget("work_schedule:{$scheduleId}");
    }
}
