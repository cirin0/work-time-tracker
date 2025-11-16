<?php

namespace App\Repositories;

use App\Models\Company;

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

    public function update(Company $company, array $data): Company
    {
        $company->update($data);

        return $company;
    }

    public function delete(Company $company): ?bool
    {
        return $company->delete();
    }
}
