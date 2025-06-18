<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyRequest;
use App\Models\Company;
use App\Services\CompanyService;

class CompanyController extends Controller
{

    public function __construct(protected CompanyService $companyService)
    {
    }

    public function showById(Company $company)
    {
        return $this->companyService->getCompanyById($company);
    }

    public function showByName(string $company)
    {
        return $this->companyService->getCompanyByName($company);
    }

    public function update(CompanyRequest $request, Company $company)
    {
        $data = $request->validated();
        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('logos', 'public'); // not work
        }
        $company = $this->companyService->updateCompany($company, $data);
        return response()->json([
            'message' => 'Company updated successfully',
            'company' => $company,
        ]);
    }

    public function store(CompanyRequest $request)
    {
        $data = $request->validated();
        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }
        $company = $this->companyService->createCompany($data);
        return response()->json([
            'message' => 'Company created successfully',
            'company' => $company,
        ], 201);

    }

    public function destroy(Company $company)
    {
        $this->companyService->deleteCompany($company);
        return response()->json([
            'message' => 'Company deleted successfully',
        ], 204);
    }
}
