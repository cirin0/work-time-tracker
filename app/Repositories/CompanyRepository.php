<?php

namespace App\Repositories;

use App\Models\Company;

class CompanyRepository
{

    public function first(): Company
    {
        return Company::query()
            ->with(['manager', 'employees', 'workSchedules'])
            ->withCount('employees')
            ->first();
    }

    public function create(array $data): Company
    {
        return Company::query()->create($data);
    }

    public function update(Company $company, array $data): bool
    {
        return $company->update($data);
    }

    public function delete(Company $company): ?bool
    {
        return $company->delete();
    }
}
