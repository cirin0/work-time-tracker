<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Notifications\VerificationCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    public function test_user_registration_creates_verification_code_and_sends_email()
    {
        Notification::fake();
        Config::set('app.env', 'production');

        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Verification code sent to your email',
            ]);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);

        // Check verification code was created
        $verificationCode = EmailVerificationCode::where('user_id', $user->id)
            ->where('type', 'registration')
            ->first();
        $this->assertNotNull($verificationCode);
        $this->assertEquals(6, strlen($verificationCode->code));
        $this->assertNull($verificationCode->verified_at);

        // Check email was sent
        Notification::assertSentTo($user, VerificationCodeNotification::class);
    }

    public function test_user_registration_in_local_environment_skips_email()
    {
        Notification::fake();
        Config::set('app.env', 'local');

        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201);

        $user = User::where('email', 'test@example.com')->first();

        // Verification code still created
        $verificationCode = EmailVerificationCode::where('user_id', $user->id)->first();
        $this->assertNotNull($verificationCode);

        // But email not sent
        Notification::assertNothingSent();
    }

    public function test_user_can_verify_email_with_valid_code()
    {
        $user = User::factory()->create([
            'email' => 'verify@example.com',
            'email_verified_at' => null,
            'company_id' => $this->company->id,
        ]);

        $code = '123456';
        EmailVerificationCode::create([
            'user_id' => $user->id,
            'code' => $code,
            'type' => 'registration',
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->postJson('/api/auth/verify-email', [
            'email' => 'verify@example.com',
            'code' => $code,
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Email verified successfully']);

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);

        // Code should be marked as verified
        $verificationCode = EmailVerificationCode::where('user_id', $user->id)->first();
        $this->assertNotNull($verificationCode->verified_at);
    }

    public function test_user_cannot_verify_email_with_invalid_code()
    {
        $user = User::factory()->create([
            'email' => 'invalid@example.com',
            'email_verified_at' => null,
            'company_id' => $this->company->id,
        ]);

        EmailVerificationCode::create([
            'user_id' => $user->id,
            'code' => '123456',
            'type' => 'registration',
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->postJson('/api/auth/verify-email', [
            'email' => 'invalid@example.com',
            'code' => '999999', // Wrong code
        ]);

        $response->assertStatus(400)
            ->assertJson(['message' => 'Invalid verification code']);

        $user->refresh();
        $this->assertNull($user->email_verified_at);
    }

    public function test_user_cannot_verify_email_with_expired_code()
    {
        $user = User::factory()->create([
            'email' => 'expired@example.com',
            'email_verified_at' => null,
            'company_id' => $this->company->id,
        ]);

        $code = '123456';
        EmailVerificationCode::create([
            'user_id' => $user->id,
            'code' => $code,
            'type' => 'registration',
            'expires_at' => now()->subMinutes(1), // Expired
        ]);

        $response = $this->postJson('/api/auth/verify-email', [
            'email' => 'expired@example.com',
            'code' => $code,
        ]);

        $response->assertStatus(400)
            ->assertJson(['message' => 'Verification code has expired']);

        $user->refresh();
        $this->assertNull($user->email_verified_at);
    }

    public function test_user_cannot_login_without_verified_email()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => null,
            'company_id' => $this->company->id,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Please verify your email before logging in']);
    }

    public function test_verified_user_can_login()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'company_id' => $this->company->id,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'expires_in',
                'user',
            ]);
    }

    public function test_user_can_request_password_change_code()
    {
        Notification::fake();
        Config::set('app.env', 'production');

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'company_id' => $this->company->id,
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/me/request-password-change-code');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Verification code sent to your email']);

        // Check code was created
        $verificationCode = EmailVerificationCode::where('user_id', $user->id)
            ->where('type', 'password_change')
            ->first();
        $this->assertNotNull($verificationCode);
        $this->assertEquals(6, strlen($verificationCode->code));

        // Check email was sent
        Notification::assertSentTo($user, VerificationCodeNotification::class);
    }

    public function test_user_can_change_password_with_valid_code()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'password' => Hash::make('oldpassword'),
            'company_id' => $this->company->id,
        ]);

        $code = '123456';
        EmailVerificationCode::create([
            'user_id' => $user->id,
            'code' => $code,
            'type' => 'password_change',
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/me/change-password', [
                'current_password' => 'oldpassword',
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'newpassword123',
                'code' => $code,
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Password changed successfully']);

        // Verify password was changed
        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));

        // Verify code was marked as used
        $verificationCode = EmailVerificationCode::where('user_id', $user->id)
            ->where('code', $code)
            ->first();
        $this->assertNotNull($verificationCode->verified_at);
    }

    public function test_user_cannot_change_password_with_wrong_current_password()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'password' => Hash::make('oldpassword'),
            'company_id' => $this->company->id,
        ]);

        $code = '123456';
        EmailVerificationCode::create([
            'user_id' => $user->id,
            'code' => $code,
            'type' => 'password_change',
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/me/change-password', [
                'current_password' => 'wrongpassword',
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'newpassword123',
                'code' => $code,
            ]);

        $response->assertStatus(400)
            ->assertJson(['message' => 'The current password is incorrect.']);
    }

    public function test_user_cannot_change_password_with_invalid_code()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'password' => Hash::make('oldpassword'),
            'company_id' => $this->company->id,
        ]);

        EmailVerificationCode::create([
            'user_id' => $user->id,
            'code' => '123456',
            'type' => 'password_change',
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/me/change-password', [
                'current_password' => 'oldpassword',
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'newpassword123',
                'code' => '999999', // Wrong code
            ]);

        $response->assertStatus(400)
            ->assertJson(['message' => 'Invalid verification code']);
    }

    public function test_admin_can_reset_password_without_code()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
            'company_id' => $this->company->id,
        ]);

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'password' => Hash::make('oldpassword'),
            'company_id' => $this->company->id,
        ]);

        $response = $this->actingAs($admin, 'api')
            ->postJson("/api/admin/users/{$user->id}/reset-password", [
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertStatus(200);

        // Verify password was changed
        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    public function test_requesting_new_password_change_code_invalidates_old_codes()
    {
        Notification::fake();
        Config::set('app.env', 'production');

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'company_id' => $this->company->id,
        ]);

        // Create first code
        EmailVerificationCode::create([
            'user_id' => $user->id,
            'code' => '111111',
            'type' => 'password_change',
            'expires_at' => now()->addMinutes(15),
        ]);

        // Request new code
        $this->actingAs($user, 'api')
            ->postJson('/api/me/request-password-change-code');

        // Old code should be deleted
        $oldCode = EmailVerificationCode::where('user_id', $user->id)
            ->where('code', '111111')
            ->first();
        $this->assertNull($oldCode);

        // New code should exist
        $newCode = EmailVerificationCode::where('user_id', $user->id)
            ->where('type', 'password_change')
            ->whereNull('verified_at')
            ->first();
        $this->assertNotNull($newCode);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create();
    }
}
