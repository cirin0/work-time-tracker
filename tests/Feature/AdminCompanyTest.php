<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Http\Resources\CompanyStoreResource;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCompanyTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $manager;

    protected User $employee;

    protected Company $company;

    public function test_admin_can_create_company(): void
    {
        $response = $this->actingAs($this->admin, 'api')->postJson('/api/admin/companies', [
            'name' => 'New Test Company',
            'email' => 'newcompany@test.com',
            'phone' => '1234567890',
        ]);

        $response->assertCreated();
        $company = Company::where('name', 'New Test Company')->first();

        $this->assertNotNull($company);
        $response->assertExactJson([
            'message' => 'Company created successfully',
            'company' => (new CompanyStoreResource($company))->resolve(),
        ]);
    }

    public function test_admin_can_create_company_with_manager(): void
    {
        $response = $this->actingAs($this->admin, 'api')->postJson('/api/admin/companies', [
            'name' => 'Company With Manager',
            'email' => 'company@test.com',
            'manager_id' => $this->manager->id,
        ]);

        $response->assertCreated();
        $company = Company::where('name', 'Company With Manager')->first();

        $this->assertNotNull($company);
        $this->assertEquals($this->manager->id, $company->manager_id);
    }

    public function test_non_admin_cannot_create_company(): void
    {
        $response = $this->actingAs($this->employee, 'api')->postJson('/api/admin/companies', [
            'name' => 'Test Company',
            'email' => 'company@test.com',
        ]);

        $response->assertForbidden();
    }

    public function test_admin_can_update_company(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/admin/companies/{$this->company->id}", [
                'name' => 'Updated Company Name',
                'email' => 'updated@test.com',
            ]);

        $response->assertOk();
        $this->company->refresh();

        $this->assertEquals('Updated Company Name', $this->company->name);
        $this->assertEquals('updated@test.com', $this->company->email);
    }

    public function test_admin_can_delete_company(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/admin/companies/{$this->company->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('companies', ['id' => $this->company->id]);
    }

    public function test_admin_can_assign_manager_to_company(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/companies/{$this->company->id}/assign-manager", [
                'manager_id' => $this->manager->id,
            ]);

        $response->assertOk();
        $this->company->refresh();

        $this->assertEquals($this->manager->id, $this->company->manager_id);
        $response->assertJsonFragment(['message' => 'Manager assigned to company successfully.']);
    }

    public function test_admin_cannot_assign_employee_role_as_manager(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/companies/{$this->company->id}/assign-manager", [
                'manager_id' => $this->employee->id,
            ]);

        $response->assertForbidden();
        $response->assertJsonFragment(['message' => 'The specified user is not a manager or admin.']);
    }

    public function test_admin_can_add_employee_to_company_with_manager(): void
    {
        $newEmployee = User::factory()->create(['role' => UserRole::EMPLOYEE]);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/companies/{$this->company->id}/add-employee", [
                'employee_id' => $newEmployee->id,
            ]);

        $response->assertOk();
        $newEmployee->refresh();

        $this->assertEquals($this->company->id, $newEmployee->company_id);
        $this->assertEquals($this->manager->id, $newEmployee->manager_id);
        $response->assertJsonFragment(['message' => 'Employee added to company successfully.']);
    }

    public function test_admin_cannot_add_employee_already_in_company(): void
    {
        $existingEmployee = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'company_id' => $this->company->id,
            'manager_id' => $this->manager->id,
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/companies/{$this->company->id}/add-employee", [
                'employee_id' => $existingEmployee->id,
                'manager_id' => $this->manager->id,
            ]);

        $response->assertStatus(409);
        $response->assertJsonFragment(['message' => 'This user already belongs to a company.']);
    }

    public function test_admin_cannot_add_employee_with_invalid_manager(): void
    {
        $companyWithoutManager = Company::factory()->create(['manager_id' => null]);
        $newEmployee = User::factory()->create(['role' => UserRole::EMPLOYEE]);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/companies/{$companyWithoutManager->id}/add-employee", [
                'employee_id' => $newEmployee->id,
            ]);

        $response->assertStatus(409);
        $response->assertJsonFragment(['message' => 'This company does not have a manager assigned.']);
    }

    public function test_admin_can_remove_employee_from_company(): void
    {
        $employeeInCompany = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'company_id' => $this->company->id,
            'manager_id' => $this->manager->id,
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/companies/{$this->company->id}/remove-employee", [
                'employee_id' => $employeeInCompany->id,
            ]);

        $response->assertOk();
        $employeeInCompany->refresh();

        $this->assertNull($employeeInCompany->company_id);
        $this->assertNull($employeeInCompany->manager_id);
        $response->assertJsonFragment(['message' => 'Employee removed from company successfully.']);
    }

    public function test_admin_can_remove_employee_from_company_by_id(): void
    {
        $employeeInCompany = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'company_id' => $this->company->id,
            'manager_id' => $this->manager->id,
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/companies/{$this->company->id}/remove-employee/{$employeeInCompany->id}");

        $response->assertOk();
        $employeeInCompany->refresh();

        $this->assertNull($employeeInCompany->company_id);
        $this->assertNull($employeeInCompany->manager_id);
    }

    public function test_admin_cannot_remove_employee_not_in_company(): void
    {
        $otherCompany = Company::factory()->create();
        $employeeInOtherCompany = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'company_id' => $otherCompany->id,
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/companies/{$this->company->id}/remove-employee", [
                'employee_id' => $employeeInOtherCompany->id,
            ]);

        $response->assertStatus(409);
        $response->assertJsonFragment(['message' => 'This user does not belong to this company.']);
    }

    public function test_non_admin_cannot_assign_manager(): void
    {
        $response = $this->actingAs($this->manager, 'api')
            ->postJson("/api/admin/companies/{$this->company->id}/assign-manager", [
                'manager_id' => $this->manager->id,
            ]);

        $response->assertForbidden();
    }

    public function test_non_admin_cannot_add_employee_via_admin_route(): void
    {
        $newEmployee = User::factory()->create(['role' => UserRole::EMPLOYEE]);

        $response = $this->actingAs($this->manager, 'api')
            ->postJson("/api/admin/companies/{$this->company->id}/add-employee", [
                'employee_id' => $newEmployee->id,
                'manager_id' => $this->manager->id,
            ]);

        $response->assertForbidden();
    }

    public function test_validation_fails_without_required_fields_for_assign_manager(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/companies/{$this->company->id}/assign-manager", []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['manager_id']);
    }

    public function test_validation_fails_without_required_fields_for_add_employee(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/companies/{$this->company->id}/add-employee", []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['employee_id']);
    }

    public function test_validation_fails_with_non_existent_employee(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/companies/{$this->company->id}/add-employee", [
                'employee_id' => 999999,
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['employee_id']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $this->employee = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $this->company = Company::factory()->create(['manager_id' => $this->manager->id]);
    }
}
