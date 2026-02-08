<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_company(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $response = $this->actingAs($admin, 'api')->postJson('/api/companies', [
            'name' => 'Test Company',
            'email' => 'company@test.com',
            'phone' => '1234567890',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('companies', [
            'name' => 'Test Company',
            'email' => 'company@test.com',
            'phone' => '1234567890',
        ]);
    }

    public function test_non_admin_cannot_create_company(): void
    {
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE]);

        $response = $this->actingAs($employee, 'api')->postJson('/api/companies', [
            'name' => 'Test Company',
            'email' => 'company@test.com',
            'phone' => '1234567890',
        ]);

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_create_company(): void
    {
        $response = $this->postJson('/api/companies', [
            'name' => 'Test Company',
        ]);

        $response->assertUnauthorized();
    }

    public function test_company_creation_requires_a_name(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $response = $this->actingAs($admin, 'api')->postJson('/api/companies', [
            'email' => 'company@test.com',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('name');
    }

    public function test_authenticated_user_can_view_company(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();

        $response = $this->actingAs($user, 'api')->getJson("/api/companies/{$company->id}");

        $response->assertOk()
            ->assertJsonPath('name', $company->name);
    }

    public function test_admin_can_update_any_company(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $company = Company::factory()->create();

        $response = $this->actingAs($admin, 'api')
            ->putJson("/api/companies/{$company->id}", [
                'name' => 'Updated Name',
                'email' => 'updated@test.com',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'Updated Name',
            'email' => 'updated@test.com',
        ]);
    }

    public function test_non_admin_cannot_update_company(): void
    {
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $company = Company::factory()->create();

        $response = $this->actingAs($employee, 'api')
            ->putJson("/api/companies/{$company->id}", [
                'name' => 'Updated Name',
            ]);

        $response->assertForbidden();
    }

    public function test_admin_can_delete_any_company(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $company = Company::factory()->create();

        $response = $this->actingAs($admin, 'api')->deleteJson("/api/companies/{$company->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('companies', ['id' => $company->id]);
    }

    public function test_non_admin_cannot_delete_company(): void
    {
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $company = Company::factory()->create();

        $response = $this->actingAs($manager, 'api')->deleteJson("/api/companies/{$company->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('companies', ['id' => $company->id]);
    }
}
