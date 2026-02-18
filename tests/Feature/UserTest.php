<?php

namespace Tests\Feature;

use App\Http\Resources\ProfileResource;
use App\Http\Resources\UserResource;
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
        User::factory()->count(2)->create();

        $response = $this->actingAs($admin, 'api')->getJson('/api/users');

        $response->assertStatus(200);

        $users = User::with(['company', 'manager', 'workSchedule'])->get();
        $expectedData = UserResource::collection($users)->resolve();

        $response->assertExactJson([
            'data' => $expectedData,
            'links' => [
                'first' => 'http://localhost/api/users?page=1',
                'last' => 'http://localhost/api/users?page=1',
                'prev' => null,
                'next' => null,
            ],
            'meta' => [
                'current_page' => 1,
                'from' => 1,
                'last_page' => 1,
                'links' => [
                    [
                        'url' => null,
                        'label' => '&laquo; Previous',
                        'active' => false,
                        'page' => null,
                    ],
                    [
                        'url' => 'http://localhost/api/users?page=1',
                        'label' => '1',
                        'active' => true,
                        'page' => 1,
                    ],
                    [
                        'url' => null,
                        'label' => 'Next &raquo;',
                        'active' => false,
                        'page' => null,
                    ],
                ],
                'path' => 'http://localhost/api/users',
                'per_page' => 10,
                'to' => 3,
                'total' => 3,
            ],
        ]);
    }

    public function test_admin_can_update_user_role()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'employee']);

        $response = $this->actingAs($admin, 'api')->postJson("/api/users/{$user->id}/role", ['role' => 'manager']);

        $response->assertStatus(200);
        $user->refresh();

        $response->assertJson([
            'message' => 'User role updated successfully',
        ]);

        $this->assertEquals('manager', $user->role->value);
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
        $user->refresh();

        $response->assertExactJson([
            'message' => 'Profile updated successfully',
            'user' => (new ProfileResource($user))->resolve(),
        ]);
    }

    public function test_admin_can_update_another_user_profile()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();

        $response = $this->actingAs($admin, 'api')->patchJson("/api/users/{$user->id}", ['name' => 'Admin Updated Name']);

        $response->assertStatus(200);
        $user->refresh();

        $response->assertJson([
            'message' => 'User updated successfully',
        ]);

        $this->assertEquals('Admin Updated Name', $user->name);
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

        $response->assertJson([
            'message' => 'Avatar updated successfully',
        ]);

        $this->assertNotNull($user->avatar);
        Storage::disk('public')->assertExists($user->avatar);
    }
}
