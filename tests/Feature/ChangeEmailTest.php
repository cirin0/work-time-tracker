<?php

namespace Tests\Feature;

use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Notifications\VerificationCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ChangeEmailTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    public function test_user_can_request_email_change_code(): void
    {
        Notification::fake();
        Config::set('app.env', 'production');

        $newEmail = 'new-email@example.com';

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/me/request-email-change', [
                'new_email' => $newEmail,
            ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Verification code has been sent to your new email address',
            ]);

        $verificationCode = EmailVerificationCode::query()
            ->where('user_id', $this->user->id)
            ->where('type', 'email_change')
            ->where('target', $newEmail)
            ->first();

        $this->assertNotNull($verificationCode);
        $this->assertSame(6, strlen($verificationCode->code));

        Notification::assertSentOnDemand(VerificationCodeNotification::class, function (VerificationCodeNotification $notification, array $channels, object $notifiable) use ($newEmail): bool {
            return $notifiable->routeNotificationFor('mail') === $newEmail;
        });
    }

    public function test_user_can_change_email_with_valid_code(): void
    {
        $newEmail = 'target@example.com';
        $code = '123456';

        EmailVerificationCode::query()->create([
            'user_id' => $this->user->id,
            'code' => $code,
            'type' => 'email_change',
            'target' => $newEmail,
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/me/verify-email-change', [
                'new_email' => $newEmail,
                'code' => $code,
            ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Email changed successfully',
            ]);

        $this->user->refresh();
        $this->assertSame($newEmail, $this->user->email);
        $this->assertNotNull($this->user->email_verified_at);

        $verificationCode = EmailVerificationCode::query()
            ->where('user_id', $this->user->id)
            ->where('type', 'email_change')
            ->where('code', $code)
            ->first();

        $this->assertNotNull($verificationCode?->verified_at);
    }

    public function test_user_cannot_change_email_with_invalid_code(): void
    {
        EmailVerificationCode::query()->create([
            'user_id' => $this->user->id,
            'code' => '123456',
            'type' => 'email_change',
            'target' => 'valid@example.com',
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/me/verify-email-change', [
                'new_email' => 'valid@example.com',
                'code' => '999999',
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Invalid verification code',
            ]);
    }

    public function test_user_cannot_change_email_with_expired_code(): void
    {
        $newEmail = 'expired@example.com';
        $code = '123456';

        EmailVerificationCode::query()->create([
            'user_id' => $this->user->id,
            'code' => $code,
            'type' => 'email_change',
            'target' => $newEmail,
            'expires_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/me/verify-email-change', [
                'new_email' => $newEmail,
                'code' => $code,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Verification code has expired',
            ]);
    }

    public function test_user_cannot_use_code_created_for_another_email(): void
    {
        EmailVerificationCode::query()->create([
            'user_id' => $this->user->id,
            'code' => '123456',
            'type' => 'email_change',
            'target' => 'first@example.com',
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/me/verify-email-change', [
                'new_email' => 'second@example.com',
                'code' => '123456',
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Invalid verification code',
            ]);
    }

    public function test_repeat_request_replaces_previous_email_change_code(): void
    {
        Config::set('app.env', 'local');

        $this->actingAs($this->user, 'api')
            ->postJson('/api/me/request-email-change', [
                'new_email' => 'first@example.com',
            ])
            ->assertOk();

        Cache::forget('email_change_request:' . $this->user->id);

        $this->actingAs($this->user, 'api')
            ->postJson('/api/me/request-email-change', [
                'new_email' => 'second@example.com',
            ])
            ->assertOk();

        $this->assertDatabaseCount('email_verification_codes', 1);
        $this->assertDatabaseHas('email_verification_codes', [
            'user_id' => $this->user->id,
            'type' => 'email_change',
            'target' => 'second@example.com',
            'verified_at' => null,
        ]);
    }

    public function test_verify_email_change_validates_email_uniqueness(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/me/verify-email-change', [
                'new_email' => 'taken@example.com',
                'code' => '123456',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['new_email']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'current@example.com',
            'email_verified_at' => now(),
        ]);
    }
}
