<?php

namespace App\Repositories;

use App\Models\Company;
use App\Models\User;

class CompanyRepository
{
    public function create(array $data): Company
    {
        return Company::query()->create($data);
    }

    public function findById(Company $company): ?Company
    {
        return Company::query()->findOrFail($company->id);
    }

    public function findByName(string $company): ?Company
    {
        //        $company->load('manager', 'employees');
        return Company::query()->where('name', $company)->firstOrFail();
    }

    public function delete(Company $company): ?bool
    {
        return $company->delete();
    }

    public function addEmployee(User $user, int $companyId, int $managerId): User
    {
        $user->update([
            'company_id' => $companyId,
            'manager_id' => $managerId,
        ]);

        return $user->fresh();
    }

    public function update(Company $company, array $data): Company
    {
        $company->update($data);

        return $company;
    }

    public function removeEmployee(User $user): User
    {
        $user->update([
            'company_id' => null,
            'manager_id' => null,
        ]);

        return $user->fresh();
    }
}
