<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'password' => Hash::make('oldpassword123'),
        ]);
    }

    public function test_user_can_change_password_with_valid_credentials(): void
    {
        $response = $this->actingAs($this->user, 'api')->postJson('/api/me/change-password', [
            'current_password' => 'oldpassword123',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Password changed successfully',
            ]);

        $this->user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $this->user->password));
    }

    public function test_user_cannot_change_password_with_incorrect_current_password(): void
    {
        $response = $this->actingAs($this->user, 'api')->postJson('/api/me/change-password', [
            'current_password' => 'wrongpassword',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);

        $response->assertForbidden()
            ->assertJson([
                'message' => 'The current password is incorrect.',
            ]);

        $this->user->refresh();
        $this->assertTrue(Hash::check('oldpassword123', $this->user->password));
    }

    public function test_user_cannot_change_password_without_confirmation(): void
    {
        $response = $this->actingAs($this->user, 'api')->postJson('/api/me/change-password', [
            'current_password' => 'oldpassword123',
            'new_password' => 'newpassword123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['new_password']);
    }

    public function test_user_cannot_change_password_with_mismatched_confirmation(): void
    {
        $response = $this->actingAs($this->user, 'api')->postJson('/api/me/change-password', [
            'current_password' => 'oldpassword123',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'differentpassword',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['new_password']);
    }

    public function test_user_cannot_change_password_with_short_new_password(): void
    {
        $response = $this->actingAs($this->user, 'api')->postJson('/api/me/change-password', [
            'current_password' => 'oldpassword123',
            'new_password' => 'short',
            'new_password_confirmation' => 'short',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['new_password']);
    }

    public function test_user_cannot_change_password_without_current_password(): void
    {
        $response = $this->actingAs($this->user, 'api')->postJson('/api/me/change-password', [
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['current_password']);
    }

    public function test_unauthenticated_user_cannot_change_password(): void
    {
        $response = $this->postJson('/api/me/change-password', [
            'current_password' => 'oldpassword123',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);

        $response->assertUnauthorized();
    }
}
