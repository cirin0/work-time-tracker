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
        if (Company::count() > 0) {
            return response()->json([
                'message' => 'Company already exists. Only one company per instance is allowed.',
            ], 400);
        }

        $data = $this->companyService->create(
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'message' => 'Company created successfully',
            'data' => new CompanyStoreResource($data['company']),
        ], 201);
    }

    public function update(UpdateCompanyRequest $request): JsonResponse
    {
        $company = Company::firstOrFail();
        $data = $this->companyService->update($company, $request->validated());

        return response()->json([
            'message' => 'Company updated successfully',
            'data' => new CompanyStoreResource($data['company']),
        ]);
    }

    public function updateLogo(UploadLogoRequest $request): JsonResponse
    {
        $company = Company::firstOrFail();
        $data = $this->companyService->updateLogo($company, $request->validated('logo'));

        return response()->json([
            'message' => 'Company logo updated successfully',
            'user' => new CompanyStoreResource($data['company']),
        ]);
    }

    public function destroy(): JsonResponse
    {
        $company = Company::firstOrFail();
        $this->companyService->delete($company);

        return response()->json([
            'message' => 'Company deleted successfully',
        ], 204);
    }

    public function assignManager(AssignManagerToCompanyRequest $request): JsonResponse
    {
        $company = Company::firstOrFail();
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

    public function addEmployee(AdminAddEmployeeRequest $request): JsonResponse
    {
        $company = Company::firstOrFail();
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

    public function removeEmployee(AdminRemoveEmployeeRequest $request)
    {
        $company = Company::firstOrFail();
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
