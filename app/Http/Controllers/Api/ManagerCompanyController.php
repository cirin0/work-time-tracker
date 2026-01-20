<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddEmployeeToCompanyRequest;
use App\Http\Requests\RemoveEmployeeFromCompanyRequest;
use App\Http\Resources\UserResource;
use App\Models\Company;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ManagerCompanyController extends Controller
{
    public function __construct(protected CompanyService $companyService)
    {
    }

    public function addEmployeeToCompany(AddEmployeeToCompanyRequest $request, Company $company): JsonResponse
    {
        $manager = Auth::user();
        if ($manager->id !== $company->manager_id) {
            return response()->json(['error' => 'You are not authorized to add employees to this company.'], 403);
        }

        $result = $this->companyService->addEmployeeToCompany(
            $company,
            $request->validated('employee_id'),
            Auth::id()
        );

        return response()->json([
            'message' => 'Employee added to company successfully.',
            'employee' => new UserResource($result['employee']),
        ]);
    }

    public function deleteEmployeeFromCompany(RemoveEmployeeFromCompanyRequest $request, Company $company): JsonResponse
    {
        $manager = Auth::user();
        if ($manager->id !== $company->manager_id) {
            return response()->json(['error' => 'You are not authorized to remove employees from this company.'], 403);
        }

        $result = $this->companyService->removeEmployeeFromCompany(
            $company,
            $request->validated('employee_id')
        );

        return response()->json([
            'message' => 'Employee removed from company successfully.',
            'employee' => new UserResource($result['employee']),
        ]);
    }

    public function deleteEmployeeFromCompanyById(Company $company, int $employeeId): JsonResponse
    {
        $manager = Auth::user();
        if ($manager->id !== $company->manager_id) {
            return response()->json(['error' => 'You are not authorized to remove employees from this company.'], 403);
        }

        $result = $this->companyService->removeEmployeeFromCompany($company, $employeeId);

        return response()->json([
            'message' => 'Employee removed from company successfully.',
            'employee' => new UserResource($result['employee']),
        ]);
    }
}
