<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_all_users()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        User::factory()->count(5)->create();

        $response = $this->actingAs($admin, 'api')
            ->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'role',
                    ],
                ],
            ]);
    }

    public function test_admin_can_view_user_details()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($admin, 'api')
            ->getJson("/api/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'name',
                'email',
                'role',
            ]);
    }

    public function test_admin_can_update_user_role()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $user = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
        ]);

        $response = $this->actingAs($admin, 'api')
            ->postJson("/api/users/{$user->id}/role", [
                'role' => UserRole::MANAGER->value,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => UserRole::MANAGER->value,
        ]);
    }

    public function test_user_can_update_their_own_profile()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->patchJson('/api/me', [
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    }

    public function test_admin_can_update_another_user()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($admin, 'api')
            ->patchJson("/api/users/{$user->id}", [
                'name' => 'Admin Updated Name',
                'email' => 'admin-updated@example.com',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Admin Updated Name',
            'email' => 'admin-updated@example.com',
        ]);
    }

    public function test_non_admin_cannot_update_another_user()
    {
        $user = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
        ]);

        $anotherUser = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->patchJson("/api/users/{$anotherUser->id}", [
                'name' => 'Hacked Name',
            ]);

        $response->assertStatus(403);
    }

    public function test_user_can_delete_their_account()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->deleteJson("/api/users/{$user->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function test_non_admin_cannot_update_user_role()
    {
        $regularUser = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
        ]);

        $anotherUser = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
        ]);

        $response = $this->actingAs($regularUser, 'api')
            ->postJson("/api/users/{$anotherUser->id}/role", [
                'role' => UserRole::MANAGER->value,
            ]);

        $response->assertStatus(403);
    }
}
