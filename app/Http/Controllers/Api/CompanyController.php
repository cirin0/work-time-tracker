<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\CompanyStoreResource;
use App\Models\Company;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;

class CompanyController extends Controller
{

    public function __construct(protected CompanyService $companyService)
    {
    }

    public function showById(Company $company): CompanyResource
    {
        return $this->companyService->getCompanyById($company);
    }

    public function showByName(string $company): CompanyResource
    {
        return $this->companyService->getCompanyByName($company);
    }

    public function store(StoreCompanyRequest $request): JsonResponse
    {
        $company = $this->companyService->createCompany($request->validated());
        return response()->json([
            'message' => 'Company created successfully',
            'company' => new CompanyStoreResource($company),
        ], 201);
    }

    public function update(UpdateCompanyRequest $request, Company $company): JsonResponse
    {
        $company = $this->companyService->updateCompany($company, $request->validated());
        return response()->json([
            'message' => 'Company updated successfully',
            'company' => new CompanyStoreResource($company),
        ]);
    }

    public function destroy(Company $company): JsonResponse
    {
        $this->companyService->deleteCompany($company);
        return response()->json([
            'message' => 'Company deleted successfully',
        ], 204);
    }
}
