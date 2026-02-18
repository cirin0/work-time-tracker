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

    // for test get all companies
    public function index(): JsonResponse
    {
        $companies = Company::all();

        return response()->json($companies);
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

    public function store(StoreCompanyRequest $request): JsonResponse
    {
        $data = $this->companyService->create($request->validated());

        return response()->json([
            'message' => 'Company created successfully',
            'company' => new CompanyStoreResource($data['company']),
        ], 201);
    }

    public function update(UpdateCompanyRequest $request, Company $company): JsonResponse
    {
        $data = $this->companyService->update($company, $request->validated());

        return response()->json([
            'message' => 'Company updated successfully',
            'company' => new CompanyStoreResource($data['company']),
        ]);
    }

    public function destroy(Company $company): JsonResponse
    {
        $this->companyService->delete($company);

        return response()->json([
            'message' => 'Company deleted successfully',
        ], 204);
    }
}
