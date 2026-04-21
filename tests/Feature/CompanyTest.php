<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Http\Resources\CompanyStoreResource;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_company(): void
    {
        Company::query()->delete();

        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'company_id' => null]);

        $response = $this->actingAs($admin, 'api')->postJson('/api/admin/company', [
            'name' => 'Test Company',
            'email' => 'company@test.com',
            'phone' => '1234567890',
        ]);

        $response->assertCreated();
        $company = Company::latest()->first();

        $admin->refresh();
        $this->assertEquals($company->id, $admin->company_id);

        $this->assertNotNull($company->qr_secret);
        $this->assertEquals(36, strlen($company->qr_secret));

        $response->assertExactJson([
            'message' => 'Company created successfully',
            'data' => (new CompanyStoreResource($company))->resolve(),
        ]);
    }

    public function test_non_admin_cannot_create_company(): void
    {
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE]);

        $response = $this->actingAs($employee, 'api')->postJson('/api/admin/company', [
            'name' => 'Test Company',
            'email' => 'company@test.com',
            'phone' => '1234567890',
        ]);

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_create_company(): void
    {
        $response = $this->postJson('/api/admin/company', [
            'name' => 'Test Company',
        ]);

        $response->assertUnauthorized();
    }

    public function test_company_creation_requires_a_name(): void
    {
        Company::query()->delete();
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $response = $this->actingAs($admin, 'api')->postJson('/api/admin/company', [
            'email' => 'company@test.com',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('name');
    }

    public function test_authenticated_user_can_view_company(): void
    {
        $user = User::factory()->create();
        Company::factory()->create();

        $response = $this->actingAs($user, 'api')->getJson('/api/company');

        $response->assertOk();

        $company = Company::first();

        $response->assertJsonFragment(['id' => $company->id]);
        $response->assertJsonFragment(['name' => $company->name]);
    }

    public function test_non_admin_cannot_update_company(): void
    {
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        Company::factory()->create();

        $response = $this->actingAs($employee, 'api')
            ->patchJson('/api/admin/company', [
                'name' => 'Updated Name',
            ]);

        $response->assertForbidden();
    }

    public function test_non_admin_cannot_delete_company(): void
    {
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $company = Company::factory()->create();
        $companyId = $company->id;

        $response = $this->actingAs($manager, 'api')->deleteJson('/api/admin/company');

        $response->assertForbidden();
        $this->assertDatabaseHas('companies', ['id' => $companyId]);
    }
}
