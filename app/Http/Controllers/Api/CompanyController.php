<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use App\Services\CompanyService;

class CompanyController extends Controller
{
    public function __construct(protected CompanyService $companyService)
    {
    }

    public function show(Company $company): CompanyResource
    {
        $data = $this->companyService->getCompanyById($company);

        return new CompanyResource($data['company']);
    }

    public function showByName(string $company): CompanyResource
    {
        $data = $this->companyService->getCompanyByName($company);

        return new CompanyResource($data['company']);
    }
}
