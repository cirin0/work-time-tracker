<?php

namespace App\Services;

use App\Http\Resources\CompanyResource;
use App\Models\Company;
use App\Repositories\CompanyRepository;
use Illuminate\Support\Facades\Storage;

class CompanyService
{

    public function __construct(protected CompanyRepository $companyRepository)
    {
    }

    public function createCompany(array $data): Company
    {
        if (isset($data['logo']) && $data['logo']) {
            $data['logo'] = $data['logo']->store('companies_logos', 'public');
        }
        return $this->companyRepository->save($data);
    }

    public function getCompanyById(Company $company): CompanyResource
    {
        return new CompanyResource($company);
    }

    public function getCompanyByName(string $company): CompanyResource
    {
        return new CompanyResource($this->companyRepository->findByName($company));
    }

    public function updateCompany(Company $company, array $data): Company
    {
        if (isset($data['logo']) && $data['logo']) {
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }
            $data['logo'] = $data['logo']->store('companies_logos', 'public');
        }
        return $this->companyRepository->update($company, $data);
    }

    public function deleteCompany(Company $company): bool
    {
        return $this->companyRepository->delete($company);
    }
}
