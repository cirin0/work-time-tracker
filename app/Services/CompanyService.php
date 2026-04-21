<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use App\Repositories\CompanyRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CompanyService
{
    public function __construct(
        protected CompanyRepository $companyRepository,
        protected CacheService      $cacheService
    )
    {
    }

    public function getCompany(): array
    {
        return ['company' => $this->companyRepository->first()];
    }

    public function create(array $data, ?User $creator = null): array
    {
        if (isset($data['logo']) && $data['logo']) {
            $data['logo'] = $data['logo']->store('companies_logos', 'public');
        }

        $qr_secret = (string)Str::uuid();
        $data['qr_secret'] = $qr_secret;

        $data['lateness_grace_minutes'] = $data['lateness_grace_minutes'] ?? 0;
        $data['overtime_threshold_hours'] = $data['overtime_threshold_hours'] ?? 0;

        $company = $this->companyRepository->create($data);

        $creator?->update(['company_id' => $company->id]);

        return ['company' => $company];
    }

    public function update(Company $company, array $data): array
    {
        $this->companyRepository->update($company, $data);
        $this->cacheService->clearCompanyCache($company->id);

        return ['company' => $company->fresh()];
    }

    public function assignManagerToCompany(Company $company, int $managerId): array
    {
        $manager = User::findOrFail($managerId);

        if (!$manager->isManager() && !$manager->isAdmin()) {
            return ['error' => true, 'message' => 'The specified user is not a manager or admin.'];
        }

        $company->update(['manager_id' => $managerId]);

        $manager->update(['company_id' => $company->id]);

        return ['company' => $company->fresh()];
    }

    public function updateLogo(Company $company, UploadedFile $logo): array
    {
        if ($company->logo) {
            Storage::disk('public')->delete($company->logo);
        }
        $path = $logo->store('companies_logos', 'public');
        $company->update(['logo' => $path]);
        $this->cacheService->clearCompanyCache($company->id);

        return ['company' => $company->fresh()];
    }

    public function delete(Company $company): array
    {
        $this->cacheService->clearCompanyCache($company->id);
        $deleted = $this->companyRepository->delete($company);

        return ['deleted' => $deleted];
    }

    public function addEmployeeToCompany(Company $company, int $employeeId): array
    {
        $employee = User::findOrFail($employeeId);

        if ($employee->company_id !== null) {
            return ['error' => true, 'message' => 'This user already belongs to a company.'];
        }

        if ($company->manager_id === null) {
            return ['error' => true, 'message' => 'This company does not have a manager assigned.'];
        }

        $defaultSchedule = $company->defaultWorkSchedule();

        $employee->update([
            'company_id' => $company->id,
            'manager_id' => $company->manager_id,
            'work_schedule_id' => $defaultSchedule?->id,
        ]);

        return ['employee' => $employee->fresh()];
    }

    public function removeEmployeeFromCompany(Company $company, int $employeeId): array
    {
        $employee = User::findOrFail($employeeId);

        if ($employee->company_id !== $company->id) {
            return ['error' => true, 'message' => 'This user does not belong to this company.'];
        }

        $employee->update([
            'company_id' => null,
            'manager_id' => null,
        ]);

        return ['employee' => $employee->fresh()];
    }
}
