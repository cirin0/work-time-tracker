<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\WorkMode;
use App\Models\Company;
use App\Models\DailySchedule;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LatenessTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected WorkSchedule $workSchedule;
    protected User $user;

    public function test_time_entry_tracks_lateness_when_user_arrives_late(): void
    {
        // Mock time to 09:15 (15 minutes late)
        Carbon::setTestNow(Carbon::today()->setTime(9, 15));

        $response = $this->actingAs($this->user, 'api')->postJson('/api/time-entries');
        $response->assertCreated();

        $timeEntry = TimeEntry::query()->where('user_id', $this->user->id)->first();

        $this->assertEquals(15, $timeEntry->lateness_minutes);
        $this->assertEquals('09:00:00', $timeEntry->scheduled_start_time);

        Carbon::setTestNow();
    }

    public function test_time_entry_tracks_early_arrival(): void
    {
        // Mock time to 08:45 (15 minutes early)
        Carbon::setTestNow(Carbon::today()->setTime(8, 45));

        $response = $this->actingAs($this->user, 'api')->postJson('/api/time-entries');
        $response->assertCreated();

        $timeEntry = TimeEntry::query()->where('user_id', $this->user->id)->first();

        $this->assertEquals(-15, $timeEntry->lateness_minutes);
        $this->assertEquals('09:00:00', $timeEntry->scheduled_start_time);

        Carbon::setTestNow();
    }

    public function test_time_entry_tracks_on_time_arrival(): void
    {
        // Mock time to exactly 09:00
        Carbon::setTestNow(Carbon::today()->setTime(9, 0));

        $response = $this->actingAs($this->user, 'api')->postJson('/api/time-entries');
        $response->assertCreated();

        $timeEntry = TimeEntry::query()->where('user_id', $this->user->id)->first();

        $this->assertEquals(0, $timeEntry->lateness_minutes);
        $this->assertEquals('09:00:00', $timeEntry->scheduled_start_time);

        Carbon::setTestNow();
    }

    public function test_lateness_is_null_when_no_work_schedule(): void
    {
        $userNoSchedule = User::factory()->create([
            'company_id' => $this->company->id,
            'work_schedule_id' => null,
            'work_mode' => WorkMode::REMOTE,
        ]);

        $response = $this->actingAs($userNoSchedule, 'api')->postJson('/api/time-entries');
        $response->assertCreated();

        $timeEntry = TimeEntry::query()->where('user_id', $userNoSchedule->id)->first();

        $this->assertNull($timeEntry->lateness_minutes);
        $this->assertNull($timeEntry->scheduled_start_time);
    }

    public function test_summary_includes_attendance_statistics(): void
    {
        // Create entries with different lateness
        TimeEntry::factory()->create([
            'user_id' => $this->user->id,
            'start_time' => now()->subDays(1),
            'stop_time' => now()->subDays(1)->addHours(8),
            'lateness_minutes' => 10,
            'scheduled_start_time' => '09:00',
        ]);

        TimeEntry::factory()->create([
            'user_id' => $this->user->id,
            'start_time' => now()->subDays(2),
            'stop_time' => now()->subDays(2)->addHours(8),
            'lateness_minutes' => -5,
            'scheduled_start_time' => '09:00',
        ]);

        TimeEntry::factory()->create([
            'user_id' => $this->user->id,
            'start_time' => now()->subDays(3),
            'stop_time' => now()->subDays(3)->addHours(8),
            'lateness_minutes' => 0,
            'scheduled_start_time' => '09:00',
        ]);

        $response = $this->actingAs($this->user, 'api')->getJson('/api/time-entries/summary/me');
        $response->assertOk();

        $response->assertJsonStructure([
            'data' => [
                'attendance' => [
                    'late_count',
                    'early_count',
                    'on_time_count',
                    'total_late_minutes',
                    'average_late_minutes',
                    'early_leave_count',
                    'total_early_leave_minutes',
                    'average_early_leave_minutes',
                    'overtime_count',
                    'total_overtime_minutes',
                    'average_overtime_minutes',
                ],
            ],
        ]);

        $data = $response->json('data.attendance');
        $this->assertEquals(1, $data['late_count']);
        $this->assertEquals(1, $data['early_count']);
        $this->assertEquals(1, $data['on_time_count']);
    }

    public function test_manager_can_see_employee_attendance_statistics(): void
    {
        $manager = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => UserRole::MANAGER,
        ]);

        // Create time entries with lateness for employee
        TimeEntry::factory()->create([
            'user_id' => $this->user->id,
            'start_time' => now()->subDays(1),
            'stop_time' => now()->subDays(1)->addHours(8),
            'lateness_minutes' => 20,
            'scheduled_start_time' => '09:00',
        ]);

        $response = $this->actingAs($manager, 'api')
            ->getJson("/api/managers/users/{$this->user->id}/time-summary");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'attendance' => [
                    'late_count',
                    'early_count',
                    'on_time_count',
                    'early_leave_count',
                    'total_early_leave_minutes',
                    'average_early_leave_minutes',
                    'overtime_count',
                    'total_overtime_minutes',
                    'average_overtime_minutes',
                ],
            ],
        ]);
    }

    public function test_company_statistics_include_attendance_data(): void
    {
        $manager = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => UserRole::MANAGER,
        ]);

        // Create time entries for multiple users
        TimeEntry::factory()->create([
            'user_id' => $this->user->id,
            'start_time' => now()->subHours(8),
            'stop_time' => now(),
            'lateness_minutes' => 15,
            'scheduled_start_time' => '09:00',
        ]);

        $response = $this->actingAs($manager, 'api')->getJson('/api/managers/statistics');
        $response->assertOk();

        $response->assertJsonStructure([
            'data' => [
                'attendance' => [
                    'late_count',
                    'early_count',
                    'on_time_count',
                    'total_late_minutes',
                    'average_late_minutes',
                    'early_leave_count',
                    'total_early_leave_minutes',
                    'average_early_leave_minutes',
                    'overtime_count',
                    'total_overtime_minutes',
                    'average_overtime_minutes',
                ],
                'summary' => [
                    'today' => ['late_count', 'early_count'],
                    'week' => ['late_count', 'early_count'],
                    'month' => ['late_count', 'early_count'],
                ],
            ],
        ]);
    }

    public function test_time_entry_resource_includes_lateness_flags(): void
    {
        $timeEntry = TimeEntry::factory()->create([
            'user_id' => $this->user->id,
            'start_time' => now(),
            'stop_time' => now()->addHours(8),
            'lateness_minutes' => 10,
            'scheduled_start_time' => '09:00',
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/time-entries/{$timeEntry->id}");

        $response->assertOk();

        // TimeEntryResource no longer includes attendance fields
        // These fields are now only in TimeEntrySummaryResource
        $response->assertJsonMissing(['lateness_minutes', 'is_late', 'is_early', 'is_on_time']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create([
            'latitude' => '50.4501',
            'longitude' => '30.5234',
            'radius_meters' => 100,
            'qr_secret' => 'test-secret',
        ]);

        $this->workSchedule = WorkSchedule::factory()->create([
            'company_id' => $this->company->id,
            'is_default' => true,
        ]);

        // Update the daily schedule for today instead of creating new one
        $today = strtolower(Carbon::now()->format('l'));
        DailySchedule::query()
            ->where('work_schedule_id', $this->workSchedule->id)
            ->where('day_of_week', $today)
            ->update([
                'start_time' => '09:00',
                'end_time' => '17:00',
                'is_working_day' => true,
            ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $this->workSchedule->id,
            'work_mode' => WorkMode::REMOTE,
            'pin_code' => bcrypt('1234'),
        ]);
    }
}
