<?php

namespace App\Repositories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Collection;

class CompanyRepository
{
    public function find(int $id): ?Company
    {
        return Company::query()
            ->with(['manager', 'employees'])
            ->withCount('employees')
            ->find($id);
    }

    public function findByName(string $name): ?Company
    {
        return Company::query()
            ->with(['manager', 'employees'])
            ->withCount('employees')
            ->where('name', $name)
            ->first();
    }

    public function getAll(): Collection
    {
        return Company::query()
            ->with(['manager', 'employees'])
            ->withCount('employees')
            ->get();
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
