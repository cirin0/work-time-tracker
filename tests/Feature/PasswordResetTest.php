<?php

namespace Tests\Feature;

use App\Models\EmailVerificationCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_password_reset_code()
    {
        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'reset@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['message']);

        $this->assertDatabaseHas('email_verification_codes', [
            'user_id' => $user->id,
            'type' => 'password_reset',
        ]);
    }

    public function test_user_can_reset_password_with_valid_code()
    {
        $user = User::factory()->create([
            'email' => 'reset2@example.com',
            'password' => bcrypt('old-password'),
            'email_verified_at' => now(),
        ]);

        $code = '123456';
        EmailVerificationCode::query()->create([
            'user_id' => $user->id,
            'code' => $code,
            'type' => 'password_reset',
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'reset2@example.com',
            'code' => $code,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Password has been reset successfully',
            ]);

        // Verify user can login with new password
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'reset2@example.com',
            'password' => 'new-password',
        ]);

        $loginResponse->assertStatus(200);
    }

    public function test_password_reset_fails_with_invalid_code()
    {
        $user = User::factory()->create([
            'email' => 'reset3@example.com',
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'reset3@example.com',
            'code' => '999999',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Invalid verification code',
            ]);
    }

    public function test_password_reset_fails_with_expired_code()
    {
        $user = User::factory()->create([
            'email' => 'reset4@example.com',
            'email_verified_at' => now(),
        ]);

        $code = '123456';
        EmailVerificationCode::query()->create([
            'user_id' => $user->id,
            'code' => $code,
            'type' => 'password_reset',
            'expires_at' => now()->subMinutes(20),
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'reset4@example.com',
            'code' => $code,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Verification code has expired',
            ]);
    }

    public function test_forgot_password_requires_valid_email()
    {
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_reset_password_requires_password_confirmation()
    {
        $user = User::factory()->create([
            'email' => 'reset5@example.com',
            'email_verified_at' => now(),
        ]);

        $code = '123456';
        EmailVerificationCode::query()->create([
            'user_id' => $user->id,
            'code' => $code,
            'type' => 'password_reset',
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'reset5@example.com',
            'code' => $code,
            'password' => 'new-password',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_forgot_password_deletes_old_codes()
    {
        $user = User::factory()->create([
            'email' => 'reset6@example.com',
            'email_verified_at' => now(),
        ]);

        // Create first code
        $this->postJson('/api/auth/forgot-password', [
            'email' => 'reset6@example.com',
        ]);

        $this->assertDatabaseCount('email_verification_codes', 1);

        // Request new code
        $this->postJson('/api/auth/forgot-password', [
            'email' => 'reset6@example.com',
        ]);

        // Should still have only one code
        $this->assertDatabaseCount('email_verification_codes', 1);
    }

    public function test_reset_password_requires_minimum_password_length()
    {
        $user = User::factory()->create([
            'email' => 'reset7@example.com',
            'email_verified_at' => now(),
        ]);

        $code = '123456';
        EmailVerificationCode::query()->create([
            'user_id' => $user->id,
            'code' => $code,
            'type' => 'password_reset',
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'reset7@example.com',
            'code' => $code,
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_code_is_marked_as_verified_after_password_reset()
    {
        $user = User::factory()->create([
            'email' => 'reset8@example.com',
            'email_verified_at' => now(),
        ]);

        $code = '123456';
        $verificationCode = EmailVerificationCode::query()->create([
            'user_id' => $user->id,
            'code' => $code,
            'type' => 'password_reset',
            'expires_at' => now()->addMinutes(15),
        ]);

        $this->postJson('/api/auth/reset-password', [
            'email' => 'reset8@example.com',
            'code' => $code,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $verificationCode->refresh();
        $this->assertNotNull($verificationCode->verified_at);
    }

    public function test_cannot_reuse_verification_code()
    {
        $user = User::factory()->create([
            'email' => 'reset9@example.com',
            'email_verified_at' => now(),
        ]);

        $code = '123456';
        EmailVerificationCode::query()->create([
            'user_id' => $user->id,
            'code' => $code,
            'type' => 'password_reset',
            'expires_at' => now()->addMinutes(15),
        ]);

        // First reset should succeed
        $response1 = $this->postJson('/api/auth/reset-password', [
            'email' => 'reset9@example.com',
            'code' => $code,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);
        $response1->assertStatus(200);

        // Second attempt with same code should fail
        $response2 = $this->postJson('/api/auth/reset-password', [
            'email' => 'reset9@example.com',
            'code' => $code,
            'password' => 'another-password',
            'password_confirmation' => 'another-password',
        ]);
        $response2->assertStatus(400)
            ->assertJson([
                'message' => 'Invalid verification code',
            ]);
    }
}
