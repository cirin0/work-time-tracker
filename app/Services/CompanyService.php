<?php

namespace App\Services;

use App\Models\Company;
use App\Repositories\CompanyRepository;
use Illuminate\Support\Facades\Storage;

class CompanyService
{
    public function __construct(protected CompanyRepository $repository)
    {
    }

    public function create(array $data): array
    {
        if (isset($data['logo']) && $data['logo']) {
            $data['logo'] = $data['logo']->store('companies_logos', 'public');
        }

        return ['company' => $this->repository->create($data)];
    }

    public function getCompanyById(Company $company): array
    {
        return ['company' => $this->repository->findById($company)];
    }

    public function getCompanyByName(string $company): array
    {
        return ['company' => $this->repository->findByName($company)];
    }

    public function update(Company $company, array $data): array
    {
        if (isset($data['logo']) && $data['logo']) {
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }
            $data['logo'] = $data['logo']->store('companies_logos', 'public');
        }
        $updatedCompany = $this->repository->update($company, $data);

        return ['company' => $updatedCompany];
    }

    public function delete(Company $company): ?bool
    {
        return $this->repository->delete($company);
    }
}
