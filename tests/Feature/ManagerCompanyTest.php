<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows manager to add employee to their company', function () {
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
});

it('denies non-manager from adding employee to company', function () {
    $manager = User::factory()->create(['role' => 'manager']);
    $otherManager = User::factory()->create(['role' => 'manager']);
    $company = Company::factory()->create(['manager_id' => $manager->id]);
    $employee = User::factory()->create(['role' => 'employee', 'company_id' => null, 'manager_id' => null]);

    $response = $this->actingAs($otherManager, 'api')
        ->postJson("/api/manager/companies/{$company->id}/add-employee", [
            'employee_id' => $employee->id,
        ]);

    $response->assertForbidden();
});

it('returns 409 when adding employee who already belongs to a company', function () {
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
});

it('returns 409 when adding employee who already has a manager', function () {
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
});

it('returns validation error when employee_id is missing', function () {
    $manager = User::factory()->create(['role' => 'manager']);
    $company = Company::factory()->create(['manager_id' => $manager->id]);

    $response = $this->actingAs($manager, 'api')
        ->postJson("/api/manager/companies/{$company->id}/add-employee", []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('employee_id');
});

it('allows manager to remove employee from their company', function () {
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
});

it('denies non-manager from removing employee from company', function () {
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
});

it('returns 409 when removing employee who does not belong to the company', function () {
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
});

it('allows manager to remove employee by id from their company', function () {
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
});

it('denies non-manager from removing employee by id from company', function () {
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
});
