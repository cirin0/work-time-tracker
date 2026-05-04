<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\NewEmailNotification;
use App\Notifications\ProfileUpdatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminUserProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $employee;

    public function test_admin_can_update_employee_name_and_sends_notification_to_old_email(): void
    {
        Notification::fake();

        $newName = 'Updated Employee Name';
        $oldEmail = $this->employee->email;

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/admin/users/{$this->employee->id}", [
                'name' => $newName,
            ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'User updated successfully.',
            ]);

        $this->employee->refresh();
        $this->assertSame($newName, $this->employee->name);

        Notification::assertSentOnDemand(
            ProfileUpdatedNotification::class,
            function (ProfileUpdatedNotification $notification, array $channels) use ($oldEmail, $newName): bool {
                return $notification->changes === ['name' => $newName];
            }
        );
    }

    public function test_admin_can_update_employee_email_and_sends_notifications(): void
    {
        Notification::fake();

        $oldEmail = $this->employee->email;
        $newEmail = 'newemail@example.com';

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/admin/users/{$this->employee->id}", [
                'email' => $newEmail,
            ]);

        $response->assertOk();

        $this->employee->refresh();
        $this->assertSame($newEmail, $this->employee->email);

        // Should send notification to old email with ProfileUpdatedNotification
        Notification::assertSentOnDemand(
            ProfileUpdatedNotification::class,
            function (ProfileUpdatedNotification $notification) use ($newEmail): bool {
                return $notification->changes === ['email' => $newEmail];
            }
        );

        // Should send notification to new email with NewEmailNotification
        Notification::assertSentTo(
            $this->employee,
            NewEmailNotification::class,
            function (NewEmailNotification $notification) use ($oldEmail): bool {
                return $notification->oldEmail === $oldEmail;
            }
        );
    }

    public function test_admin_can_update_employee_name_and_email_together(): void
    {
        Notification::fake();

        $oldEmail = $this->employee->email;
        $newName = 'New Name';
        $newEmail = 'combined@example.com';

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/admin/users/{$this->employee->id}", [
                'name' => $newName,
                'email' => $newEmail,
            ]);

        $response->assertOk();

        $this->employee->refresh();
        $this->assertSame($newName, $this->employee->name);
        $this->assertSame($newEmail, $this->employee->email);

        // Should send ProfileUpdatedNotification to old email
        Notification::assertSentOnDemand(
            ProfileUpdatedNotification::class,
            function (ProfileUpdatedNotification $notification) use ($newName, $newEmail): bool {
                return $notification->changes === ['name' => $newName, 'email' => $newEmail];
            }
        );

        // And NewEmailNotification to new user
        Notification::assertSentTo(
            $this->employee,
            NewEmailNotification::class,
            function (NewEmailNotification $notification) use ($oldEmail): bool {
                return $notification->oldEmail === $oldEmail;
            }
        );
    }

    public function test_no_notification_sent_if_no_changes_made(): void
    {
        Notification::fake();

        $sameName = $this->employee->name;
        $sameEmail = $this->employee->email;

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/admin/users/{$this->employee->id}", [
                'name' => $sameName,
                'email' => $sameEmail,
            ]);

        $response->assertOk();

        Notification::assertNothingSent();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $this->employee = User::factory()->create(['role' => 'employee', 'email_verified_at' => now()]);
    }
}
