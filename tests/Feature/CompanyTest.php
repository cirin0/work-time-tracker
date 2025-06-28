<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_company()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->postJson('/api/companies', [
            'name' => 'Test Company',
            'email' => 'company@test.com',
            'website' => 'test.com'
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('companies', ['name' => 'Test Company']);
    }

    public function test_unauthenticated_user_cannot_create_company()
    {
        $response = $this->postJson('/api/companies', [
            'name' => 'Test Company',
        ]);

        $response->assertStatus(401);
    }

    public function test_company_creation_requires_a_name()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->postJson('/api/companies', [
            'email' => 'company@test.com',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('name');
    }

    public function test_authenticated_user_can_view_company()
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();

        $response = $this->actingAs($user, 'api')->getJson("/api/companies/{$company->id}");

        $response->assertStatus(200)
            ->assertJson(['data' => ['name' => $company->name]]);
    }

    public function test_admin_can_update_any_company()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $company = Company::factory()->create();

        $response = $this->actingAs($admin, 'api')->putJson("/api/companies/{$company->id}", [
            'name' => 'Updated Name'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('companies', ['id' => $company->id, 'name' => 'Updated Name']);
    }

    public function test_admin_can_delete_any_company()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $company = Company::factory()->create();

        $response = $this->actingAs($admin, 'api')->deleteJson("/api/companies/{$company->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('companies', ['id' => $company->id]);
    }
}
