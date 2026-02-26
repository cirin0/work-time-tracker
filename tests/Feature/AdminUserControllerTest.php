<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\WorkMode;
use App\Models\Company;
use App\Models\User;
use Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Company $company;

    public function test_admin_can_get_all_users()
    {
        User::factory()->count(5)->create(['company_id' => $this->company->id]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'work_mode',
                    ],
                ],
            ]);
    }

    public function test_admin_can_update_user_details()
    {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/admin/users/{$user->id}", [
                'name' => 'New Name',
                'email' => 'new@example.com',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.email', 'new@example.com');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);
    }

    public function test_admin_can_update_user_role()
    {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => UserRole::EMPLOYEE,
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/admin/users/{$user->id}/role", [
                'role' => UserRole::MANAGER->value,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'User role updated successfully.')
            ->assertJsonPath('data.role', UserRole::MANAGER->value);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => UserRole::MANAGER->value,
        ]);
    }

    public function test_admin_can_update_user_work_mode()
    {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
            'work_mode' => WorkMode::OFFICE,
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/admin/users/{$user->id}/work-mode", [
                'work_mode' => WorkMode::REMOTE->value,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'User work mode updated successfully.')
            ->assertJsonPath('data.work_mode', WorkMode::REMOTE->value);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'work_mode' => WorkMode::REMOTE->value,
        ]);
    }

    public function test_admin_can_reset_user_password()
    {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $newPassword = 'newpassword123';

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/users/{$user->id}/reset-password", [
                'password' => $newPassword,
                'password_confirmation' => $newPassword,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'User password reset successfully.');

        $user->refresh();
        $this->assertTrue(Hash::check($newPassword, $user->password));
    }

    public function test_admin_can_delete_user()
    {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/admin/users/{$user->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function test_non_admin_cannot_access_admin_endpoints()
    {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => UserRole::EMPLOYEE,
        ]);

        $targetUser = User::factory()->create(['company_id' => $this->company->id]);

        $response = $this->actingAs($user, 'api')
            ->patchJson("/api/admin/users/{$targetUser->id}", [
                'name' => 'Hacked Name',
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_cannot_update_user_with_duplicate_email()
    {
        $user1 = User::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'existing@example.com',
        ]);

        $user2 = User::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'user2@example.com',
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/admin/users/{$user2->id}", [
                'email' => 'existing@example.com',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_admin_reset_password_requires_confirmation()
    {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/users/{$user->id}/reset-password", [
                'password' => 'newpassword123',
                'password_confirmation' => 'differentpassword',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_admin_update_role_validates_role_value()
    {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/admin/users/{$user->id}/role", [
                'role' => 'invalid_role',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    }

    public function test_admin_update_work_mode_validates_work_mode_value()
    {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/admin/users/{$user->id}/work-mode", [
                'work_mode' => 'invalid_mode',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['work_mode']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'company_id' => $this->company->id,
        ]);
    }
}
