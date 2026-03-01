<?php

namespace Tests\Feature;

use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Notifications\VerificationCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    public function test_user_can_request_password_change_code(): void
    {
        Notification::fake();
        Config::set('app.env', 'production');

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/me/request-password-change-code');

        $response->assertOk()
            ->assertJson([
                'message' => 'Verification code sent to your email',
            ]);

        // Verify code was created
        $verificationCode = EmailVerificationCode::where('user_id', $this->user->id)
            ->where('type', 'password_change')
            ->first();

        $this->assertNotNull($verificationCode);
        $this->assertEquals(6, strlen($verificationCode->code));

        // Verify email was sent in production
        Notification::assertSentTo($this->user, VerificationCodeNotification::class);
    }

    public function test_user_can_request_password_change_code_in_local_environment(): void
    {
        Notification::fake();
        Config::set('app.env', 'local');

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/me/request-password-change-code');

        $response->assertOk();

        // Code still created in local
        $verificationCode = EmailVerificationCode::where('user_id', $this->user->id)
            ->where('type', 'password_change')
            ->first();
        $this->assertNotNull($verificationCode);

        // But email not sent in local
        Notification::assertNothingSent();
    }

    public function test_user_can_change_password_with_valid_code(): void
    {
        $code = '123456';
        EmailVerificationCode::create([
            'user_id' => $this->user->id,
            'code' => $code,
            'type' => 'password_change',
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/me/change-password', [
                'current_password' => 'oldpassword123',
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'newpassword123',
                'code' => $code,
            ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Password changed successfully',
            ]);

        $this->user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $this->user->password));

        // Code should be marked as verified
        $verificationCode = EmailVerificationCode::where('user_id', $this->user->id)
            ->where('code', $code)
            ->first();
        $this->assertNotNull($verificationCode->verified_at);
    }

    public function test_user_cannot_change_password_with_incorrect_current_password(): void
    {
        $code = '123456';
        EmailVerificationCode::create([
            'user_id' => $this->user->id,
            'code' => $code,
            'type' => 'password_change',
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/me/change-password', [
                'current_password' => 'wrongpassword',
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'newpassword123',
                'code' => $code,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'The current password is incorrect.',
            ]);

        $this->user->refresh();
        $this->assertTrue(Hash::check('oldpassword123', $this->user->password));
    }

    public function test_user_cannot_change_password_with_invalid_code(): void
    {
        EmailVerificationCode::create([
            'user_id' => $this->user->id,
            'code' => '123456',
            'type' => 'password_change',
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/me/change-password', [
                'current_password' => 'oldpassword123',
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'newpassword123',
                'code' => '999999', // Wrong code
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Invalid verification code',
            ]);

        $this->user->refresh();
        $this->assertTrue(Hash::check('oldpassword123', $this->user->password));
    }

    public function test_user_cannot_change_password_with_expired_code(): void
    {
        $code = '123456';
        EmailVerificationCode::create([
            'user_id' => $this->user->id,
            'code' => $code,
            'type' => 'password_change',
            'expires_at' => now()->subMinutes(1), // Expired
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/me/change-password', [
                'current_password' => 'oldpassword123',
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'newpassword123',
                'code' => $code,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Verification code has expired',
            ]);
    }

    public function test_user_cannot_change_password_without_code(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/me/change-password', [
                'current_password' => 'oldpassword123',
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'newpassword123',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['code']);
    }

    public function test_user_cannot_change_password_without_confirmation(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/me/change-password', [
                'current_password' => 'oldpassword123',
                'new_password' => 'newpassword123',
                'code' => '123456',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['new_password']);
    }

    public function test_user_cannot_change_password_with_mismatched_confirmation(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/me/change-password', [
                'current_password' => 'oldpassword123',
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'differentpassword',
                'code' => '123456',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['new_password']);
    }

    public function test_user_cannot_change_password_with_short_new_password(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/me/change-password', [
                'current_password' => 'oldpassword123',
                'new_password' => 'short',
                'new_password_confirmation' => 'short',
                'code' => '123456',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['new_password']);
    }

    public function test_user_cannot_change_password_without_current_password(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/me/change-password', [
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'newpassword123',
                'code' => '123456',
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
            'code' => '123456',
        ]);

        $response->assertUnauthorized();
    }

    public function test_unauthenticated_user_cannot_request_password_change_code(): void
    {
        $response = $this->postJson('/api/me/request-password-change-code');

        $response->assertUnauthorized();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'password' => Hash::make('oldpassword123'),
            'email_verified_at' => now(),
        ]);
    }
}
