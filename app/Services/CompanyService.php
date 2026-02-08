<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use App\Repositories\CompanyRepository;
use Illuminate\Support\Facades\Storage;

class CompanyService
{
    public function __construct(protected CompanyRepository $companyRepository)
    {
    }

    public function create(array $data): array
    {
        if (isset($data['logo']) && $data['logo']) {
            $data['logo'] = $data['logo']->store('companies_logos', 'public');
        }

        return ['company' => $this->companyRepository->create($data)];
    }

    public function getCompanyById(Company $company): array
    {
        return ['company' => $this->companyRepository->find($company->id)];
    }

    public function getCompanyByName(string $company): array
    {
        return ['company' => $this->companyRepository->findByName($company)];
    }

    public function update(Company $company, array $data): array
    {
        if (isset($data['logo']) && $data['logo']) {
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }
            $data['logo'] = $data['logo']->store('companies_logos', 'public');
        }

        $this->companyRepository->update($company, $data);

        return ['company' => $company->fresh()];
    }

    public function delete(Company $company): array
    {
        $deleted = $this->companyRepository->delete($company);

        return ['deleted' => $deleted];
    }

    public function addEmployeeToCompany(Company $company, int $employeeId, int $managerId): array
    {
        $user = User::findOrFail($employeeId);

        if ($user->company_id !== null) {
            return ['message' => 'This user already belongs to a company.'];
        }

        if ($user->manager_id !== null) {
            return ['message' => 'This user is already assigned to a manager.'];
        }

        $user->update([
            'company_id' => $company->id,
            'manager_id' => $managerId,
        ]);

        return ['employee' => $user->fresh()];
    }

    public function removeEmployeeFromCompany(Company $company, int $employeeId): array
    {
        $user = User::findOrFail($employeeId);

        if ($user->company_id !== $company->id) {
            return ['message' => 'This user does not belong to this company.'];
        }

        $user->update([
            'company_id' => null,
            'manager_id' => null,
        ]);

        return ['employee' => $user->fresh()];
    }
}
