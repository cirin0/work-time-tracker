<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminAddEmployeeRequest;
use App\Http\Requests\AdminRemoveEmployeeRequest;
use App\Http\Requests\AssignManagerToCompanyRequest;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Http\Requests\UploadLogoRequest;
use App\Http\Resources\AdminUserResource;
use App\Http\Resources\CompanyStoreResource;
use App\Models\Company;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;

class AdminCompanyController extends Controller
{
    public function __construct(protected CompanyService $companyService)
    {
    }

    public function store(StoreCompanyRequest $request): JsonResponse
    {
        $data = $this->companyService->create(
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'message' => 'Company created successfully',
            'data' => new CompanyStoreResource($data['company']),
        ], 201);
    }

    public function update(UpdateCompanyRequest $request, Company $company): JsonResponse
    {
        $data = $this->companyService->update($company, $request->validated());

        return response()->json([
            'message' => 'Company updated successfully',
            'data' => new CompanyStoreResource($data['company']),
        ]);
    }

    public function updateLogo(UploadLogoRequest $request, Company $company): JsonResponse
    {
        $data = $this->companyService->updateLogo($company, $request->validated('logo'));

        return response()->json([
            'message' => 'Company logo updated successfully',
            'user' => new CompanyStoreResource($data['company']),
        ]);
    }

    public function destroy(Company $company): JsonResponse
    {
        $this->companyService->delete($company);

        return response()->json([
            'message' => 'Company deleted successfully',
        ], 204);
    }

    public function assignManager(AssignManagerToCompanyRequest $request, Company $company): JsonResponse
    {
        $result = $this->companyService->assignManagerToCompany(
            $company,
            $request->validated('manager_id')
        );

        if (isset($result['error'])) {
            return response()->json(['message' => $result['message']], 403);
        }

        return response()->json([
            'message' => 'Manager assigned to company successfully.',
            'company' => new CompanyStoreResource($result['company']),
        ]);
    }

    public function addEmployee(AdminAddEmployeeRequest $request, Company $company): JsonResponse
    {
        $result = $this->companyService->addEmployeeToCompany(
            $company,
            $request->validated('employee_id')
        );

        if (isset($result['error'])) {
            return response()->json(['message' => $result['message']], 400);
        }

        return response()->json([
            'message' => 'Employee added to company successfully.',
            'employee' => new AdminUserResource($result['employee']),
        ]);
    }

    public function removeEmployee(AdminRemoveEmployeeRequest $request, Company $company)
    {
        $result = $this->companyService->removeEmployeeFromCompany(
            $company,
            $request->validated('employee_id')
        );

        if (isset($result['error'])) {
            return response()->json([
                'message' => $result['message']
            ], 404);
        }

        return response()->noContent();
    }
}
