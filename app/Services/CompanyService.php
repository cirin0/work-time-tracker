<?php

namespace App\Services;

use App\Http\Resources\CompanyResource;
use App\Models\Company;
use App\Repositories\CompanyRepository;

class CompanyService
{

    public function __construct(protected CompanyRepository $repository)
    {
    }

    public function createCompany(array $data): Company
    {
        return $this->repository->save($data);
    }

    public function getCompanyById(Company $company): CompanyResource
    {
        return new CompanyResource($company);
    }

    public function getCompanyByName(string $company): CompanyResource
    {
        return new CompanyResource($this->repository->findByName($company));
    }

    public function updateCompany(Company $company, array $data): Company
    {
        return $this->repository->update($company, $data);
    }

    public function deleteCompany(Company $company): bool
    {
        return $this->repository->delete($company);
    }
}
