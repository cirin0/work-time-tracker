<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_get_all_users()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->count(5)->create();

        $response = $this->actingAs($admin, 'api')->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonCount(6, 'data');
    }

    public function test_admin_can_update_user_role()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'employee']);

        $response = $this->actingAs($admin, 'api')->postJson("/api/users/{$user->id}/role", ['role' => 'manager']);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'manager']);
    }

    public function test_non_admin_cannot_update_user_role()
    {
        $user = User::factory()->create(['role' => 'employee']);
        $anotherUser = User::factory()->create();

        $response = $this->actingAs($user, 'api')->postJson("/api/users/{$anotherUser->id}/role", ['role' => 'admin']);

        $response->assertStatus(403);
    }

    public function test_user_can_update_their_own_profile()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->patchJson('/api/me', ['name' => 'New Name']);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name']);
    }

    public function test_admin_can_update_another_user_profile()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();

        $response = $this->actingAs($admin, 'api')->patchJson("/api/users/{$user->id}", ['name' => 'Admin Updated Name']);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Admin Updated Name']);
    }

    public function test_non_admin_cannot_update_another_users_profile()
    {
        $user = User::factory()->create(['role' => 'employee']);
        $anotherUser = User::factory()->create();

        $response = $this->actingAs($user, 'api')->patchJson("/api/users/{$anotherUser->id}", ['name' => 'New Name']);

        $response->assertStatus(403);
    }

    public function test_user_can_delete_their_own_account()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->deleteJson("/api/users/{$user->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_can_delete_a_user()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();

        $response = $this->actingAs($admin, 'api')->deleteJson("/api/users/{$user->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_user_can_upload_avatar()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->actingAs($user, 'api')->postJson("/api/users/{$user->id}/avatar", [
            'avatar' => $file,
        ]);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertNotNull($user->avatar);
        Storage::disk('public')->assertExists($user->avatar);
    }
}
