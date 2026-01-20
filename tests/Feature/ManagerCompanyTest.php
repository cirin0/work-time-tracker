<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagerCompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_add_employee_to_their_company()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $company = Company::factory()->create(['manager_id' => $manager->id]);
        $employee = User::factory()->create(['role' => 'employee', 'company_id' => null, 'manager_id' => null]);

        $response = $this->actingAs($manager, 'api')
            ->postJson("/api/manager/companies/{$company->id}/add-employee", [
                'employee_id' => $employee->id,
            ]);

        $response->assertSuccessful()
            ->assertJsonFragment(['message' => 'Employee added to company successfully.']);

        $this->assertDatabaseHas('users', [
            'id' => $employee->id,
            'company_id' => $company->id,
            'manager_id' => $manager->id,
        ]);
    }

    public function test_non_manager_cannot_add_employee_to_company()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $otherManager = User::factory()->create(['role' => 'manager']);
        $company = Company::factory()->create(['manager_id' => $manager->id]);
        $employee = User::factory()->create(['role' => 'employee', 'company_id' => null, 'manager_id' => null]);

        $response = $this->actingAs($otherManager, 'api')
            ->postJson("/api/manager/companies/{$company->id}/add-employee", [
                'employee_id' => $employee->id,
            ]);

        $response->assertForbidden();
    }

    public function test_cannot_add_employee_who_already_belongs_to_a_company()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $company = Company::factory()->create(['manager_id' => $manager->id]);
        $otherCompany = Company::factory()->create();
        $employee = User::factory()->create([
            'role' => 'employee',
            'company_id' => $otherCompany->id,
            'manager_id' => null,
        ]);

        $response = $this->actingAs($manager, 'api')
            ->postJson("/api/manager/companies/{$company->id}/add-employee", [
                'employee_id' => $employee->id,
            ]);

        $response->assertStatus(409)
            ->assertJsonFragment(['message' => 'This user already belongs to a company.']);
    }

    public function test_cannot_add_employee_who_already_has_a_manager()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $otherManager = User::factory()->create(['role' => 'manager']);
        $company = Company::factory()->create(['manager_id' => $manager->id]);
        $employee = User::factory()->create([
            'role' => 'employee',
            'company_id' => null,
            'manager_id' => $otherManager->id,
        ]);

        $response = $this->actingAs($manager, 'api')
            ->postJson("/api/manager/companies/{$company->id}/add-employee", [
                'employee_id' => $employee->id,
            ]);

        $response->assertStatus(409)
            ->assertJsonFragment(['message' => 'This user is already assigned to a manager.']);
    }

    public function test_add_employee_requires_employee_id()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $company = Company::factory()->create(['manager_id' => $manager->id]);

        $response = $this->actingAs($manager, 'api')
            ->postJson("/api/manager/companies/{$company->id}/add-employee", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('employee_id');
    }

    public function test_manager_can_remove_employee_from_their_company()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $company = Company::factory()->create(['manager_id' => $manager->id]);
        $employee = User::factory()->create([
            'role' => 'employee',
            'company_id' => $company->id,
            'manager_id' => $manager->id,
        ]);

        $response = $this->actingAs($manager, 'api')
            ->postJson("/api/manager/companies/{$company->id}/remove-employee", [
                'employee_id' => $employee->id,
            ]);

        $response->assertSuccessful()
            ->assertJsonFragment(['message' => 'Employee removed from company successfully.']);

        $this->assertDatabaseHas('users', [
            'id' => $employee->id,
            'company_id' => null,
            'manager_id' => null,
        ]);
    }

    public function test_non_manager_cannot_remove_employee_from_company()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $otherManager = User::factory()->create(['role' => 'manager']);
        $company = Company::factory()->create(['manager_id' => $manager->id]);
        $employee = User::factory()->create([
            'role' => 'employee',
            'company_id' => $company->id,
            'manager_id' => $manager->id,
        ]);

        $response = $this->actingAs($otherManager, 'api')
            ->postJson("/api/manager/companies/{$company->id}/remove-employee", [
                'employee_id' => $employee->id,
            ]);

        $response->assertForbidden();
    }

    public function test_cannot_remove_employee_who_does_not_belong_to_the_company()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $company = Company::factory()->create(['manager_id' => $manager->id]);
        $otherCompany = Company::factory()->create();
        $employee = User::factory()->create([
            'role' => 'employee',
            'company_id' => $otherCompany->id,
            'manager_id' => null,
        ]);

        $response = $this->actingAs($manager, 'api')
            ->postJson("/api/manager/companies/{$company->id}/remove-employee", [
                'employee_id' => $employee->id,
            ]);

        $response->assertStatus(409)
            ->assertJsonFragment(['message' => 'This user does not belong to this company.']);
    }

    public function test_manager_can_remove_employee_by_id_from_their_company()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $company = Company::factory()->create(['manager_id' => $manager->id]);
        $employee = User::factory()->create([
            'role' => 'employee',
            'company_id' => $company->id,
            'manager_id' => $manager->id,
        ]);

        $response = $this->actingAs($manager, 'api')
            ->postJson("/api/manager/companies/{$company->id}/remove-employee/{$employee->id}");

        $response->assertSuccessful()
            ->assertJsonFragment(['message' => 'Employee removed from company successfully.']);

        $this->assertDatabaseHas('users', [
            'id' => $employee->id,
            'company_id' => null,
            'manager_id' => null,
        ]);
    }

    public function test_non_manager_cannot_remove_employee_by_id_from_company()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $otherManager = User::factory()->create(['role' => 'manager']);
        $company = Company::factory()->create(['manager_id' => $manager->id]);
        $employee = User::factory()->create([
            'role' => 'employee',
            'company_id' => $company->id,
            'manager_id' => $manager->id,
        ]);

        $response = $this->actingAs($otherManager, 'api')
            ->postJson("/api/manager/companies/{$company->id}/remove-employee/{$employee->id}");

        $response->assertForbidden();
    }
}
