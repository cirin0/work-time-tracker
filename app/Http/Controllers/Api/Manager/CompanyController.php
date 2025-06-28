<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyController extends Controller
{
    public function addEmployeeToCompany(Request $request, Company $company)
    {
        $manager = Auth::user();
        if ($manager->id !== $company->manager_id) {
            return response()->json(['error' => 'You are not authorized to add employees to this company or the company does not exist.'], 403);
        }
        if (!$company->exists) {
            return response()->json(['error' => 'Company not found.'], 404);
        }

        $validated = $request->validate([
            'employee_id' => 'required|integer|exists:users,id',
        ]);

        $userToAdd = User::findOrFail($validated['employee_id']);

        if ($userToAdd->company_id !== null) {
            return response()->json(['message' => 'This user already belongs to a company.'], 409);
        }

        $userToAdd->update([
            'company_id' => $company->id,
            'manager_id' => $manager->id,
        ]);

        return response()->json([
            'message' => 'Employee added to company successfully.',
        ]);
    }

    public function deleteEmployeeFromCompany(Request $request, Company $company)
    {
        $manager = Auth::user();
        if ($manager->id !== $company->manager_id) {
            return response()->json(['error' => 'You are not authorized to remove employees from this company or the company does not exist.'], 403);
        }
        if (!$company->exists) {
            return response()->json(['error' => 'Company not found.'], 404);
        }

        $validated = $request->validate([
            'employee_id' => 'required|integer|exists:users,id',
        ]);

        $userToRemove = User::findOrFail($validated['employee_id']);

        if ($userToRemove->company_id !== $company->id) {
            return response()->json(['message' => 'This user does not belong to this company.'], 409);
        }

        $userToRemove->update([
            'company_id' => null,
            'manager_id' => null,
        ]);

        return response()->json([
            'message' => 'Employee removed from company successfully.',
        ]);
    }

    public function deleteEmployeeFromCompanyById(Company $company, int $employeeId)
    {
        $manager = Auth::user();
        if ($manager->id !== $company->manager_id) {
            return response()->json(['error' => 'You are not authorized to remove employees from this company or the company does not exist.'], 403);
        }
        if (!$company->exists) {
            return response()->json(['error' => 'Company not found.'], 404);
        }

        $userToRemove = User::findOrFail($employeeId);

        if ($userToRemove->company_id !== $company->id) {
            return response()->json(['message' => 'This user does not belong to this company.'], 409);
        }

        $userToRemove->update([
            'company_id' => null,
            'manager_id' => null,
        ]);

        return response()->json([
            'message' => 'Employee removed from company successfully.',
        ]);
    }
}
