<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\DailySchedule;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BreakDurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_total_minutes_excludes_break_duration(): void
    {
        $company = Company::factory()->create();
        $workSchedule = $this->createWorkScheduleWithBreak($company->id, 'monday', 60);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'work_schedule_id' => $workSchedule->id,
        ]);

        // Create a time entry for Monday: worked 9:00-18:00 (9 hours)
        $monday = Carbon::now()->startOfWeek();
        TimeEntry::factory()->create([
            'user_id' => $user->id,
            'date' => $monday->format('Y-m-d'),
            'start_time' => $monday->copy()->setTime(9, 0),
            'stop_time' => $monday->copy()->setTime(18, 0),
            'duration' => 9 * 3600,
        ]);

        $response = $this->actingAs($user, 'api')->getJson('/api/time-entries/summary/me');

        $response->assertOk();

        // Should be 8 hours (9 hours - 1 hour break)
        $this->assertEquals(8, $response->json('data.total_hours'));
        $this->assertEquals(0, $response->json('data.total_minutes'));
    }

    private function createWorkScheduleWithBreak(int $companyId, string $day, int $breakMinutes): WorkSchedule
    {
        $workSchedule = WorkSchedule::create([
            'name' => 'Test Schedule',
            'company_id' => $companyId,
            'is_default' => false,
        ]);

        DailySchedule::create([
            'work_schedule_id' => $workSchedule->id,
            'day_of_week' => $day,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'break_duration' => $breakMinutes,
            'is_working_day' => true,
        ]);

        return $workSchedule;
    }

    public function test_overtime_calculation_includes_break_duration(): void
    {
        $company = Company::factory()->create();
        $workSchedule = $this->createWorkScheduleWithBreak($company->id, 'monday', 60);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'work_schedule_id' => $workSchedule->id,
            'pin_code' => bcrypt('1234'),
        ]);

        $monday = Carbon::now()->startOfWeek();

        $entry = TimeEntry::factory()->create([
            'user_id' => $user->id,
            'date' => $monday->format('Y-m-d'),
            'start_time' => $monday->copy()->setTime(9, 0),
            'stop_time' => null,
            'scheduled_start_time' => '09:00:00',
        ]);

        // Stop at 20:00 (1 hour overtime: worked until 20:00, expected 19:00)
        Carbon::setTestNow($monday->copy()->setTime(20, 0));

        $this->actingAs($user, 'api')->patchJson("/api/time-entries/active/stop", [
            'pin_code' => '1234',
        ]);

        $entry->refresh();

        // Should have 60 minutes overtime (20:00 - 19:00)
        $this->assertEquals(60, $entry->overtime_minutes);
    }

    public function test_early_leave_calculation_includes_break_duration(): void
    {
        $company = Company::factory()->create();
        $workSchedule = $this->createWorkScheduleWithBreak($company->id, 'monday', 60);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'work_schedule_id' => $workSchedule->id,
            'pin_code' => bcrypt('1234'),
        ]);

        $monday = Carbon::now()->startOfWeek();

        $entry = TimeEntry::factory()->create([
            'user_id' => $user->id,
            'date' => $monday->format('Y-m-d'),
            'start_time' => $monday->copy()->setTime(9, 0),
            'stop_time' => null,
            'scheduled_start_time' => '09:00:00',
        ]);

        // Stop at 17:00 (2 hours early: expected 19:00, left at 17:00)
        Carbon::setTestNow($monday->copy()->setTime(17, 0));

        $this->actingAs($user, 'api')->patchJson("/api/time-entries/active/stop", [
            'pin_code' => '1234',
        ]);

        $entry->refresh();

        // Should have 120 minutes early leave (19:00 - 17:00)
        $this->assertEquals(120, $entry->early_leave_minutes);
    }

    public function test_no_overtime_when_leaving_at_scheduled_time_plus_break(): void
    {
        $company = Company::factory()->create();
        $workSchedule = $this->createWorkScheduleWithBreak($company->id, 'monday', 60);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'work_schedule_id' => $workSchedule->id,
            'pin_code' => bcrypt('1234'),
        ]);

        $monday = Carbon::now()->startOfWeek();

        $entry = TimeEntry::factory()->create([
            'user_id' => $user->id,
            'date' => $monday->format('Y-m-d'),
            'start_time' => $monday->copy()->setTime(9, 0),
            'stop_time' => null,
            'scheduled_start_time' => '09:00:00',
        ]);

        // Stop at 19:00 (exactly scheduled end + break)
        Carbon::setTestNow($monday->copy()->setTime(19, 0));

        $this->actingAs($user, 'api')->patchJson("/api/time-entries/active/stop", [
            'pin_code' => '1234',
        ]);

        $entry->refresh();

        // Should have 0 overtime
        $this->assertEquals(0, $entry->overtime_minutes);
    }

    public function test_break_duration_zero_works_correctly(): void
    {
        $company = Company::factory()->create();
        $workSchedule = $this->createWorkScheduleWithBreak($company->id, 'saturday', 0);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'work_schedule_id' => $workSchedule->id,
        ]);

        $saturday = Carbon::now()->startOfWeek()->addDays(5);
        TimeEntry::factory()->create([
            'user_id' => $user->id,
            'date' => $saturday->format('Y-m-d'),
            'start_time' => $saturday->copy()->setTime(9, 0),
            'stop_time' => $saturday->copy()->setTime(17, 0),
            'duration' => 8 * 3600,
        ]);

        $response = $this->actingAs($user, 'api')->getJson('/api/time-entries/summary/me');

        $response->assertOk();

        // Should be 8 hours (no break to subtract)
        $this->assertEquals(8, $response->json('data.total_hours'));
        $this->assertEquals(0, $response->json('data.total_minutes'));
    }

    public function test_user_without_work_schedule_calculates_correctly(): void
    {
        $user = User::factory()->create([
            'work_schedule_id' => null,
        ]);

        $today = Carbon::today();
        TimeEntry::factory()->create([
            'user_id' => $user->id,
            'date' => $today->format('Y-m-d'),
            'start_time' => $today->copy()->setTime(9, 0),
            'stop_time' => $today->copy()->setTime(17, 0),
            'duration' => 8 * 3600,
        ]);

        $response = $this->actingAs($user, 'api')->getJson('/api/time-entries/summary/me');

        $response->assertOk();

        // Should be 8 hours (no schedule, no break to subtract)
        $this->assertEquals(8, $response->json('data.total_hours'));
        $this->assertEquals(0, $response->json('data.total_minutes'));
    }

    public function test_multiple_entries_same_day_subtract_break_only_once(): void
    {
        $company = Company::factory()->create();
        $workSchedule = $this->createWorkScheduleWithBreak($company->id, 'monday', 60);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'work_schedule_id' => $workSchedule->id,
        ]);

        $monday = Carbon::now()->startOfWeek();

        // First entry: 9:00-12:00 (3 hours)
        TimeEntry::factory()->create([
            'user_id' => $user->id,
            'date' => $monday->format('Y-m-d'),
            'start_time' => $monday->copy()->setTime(9, 0),
            'stop_time' => $monday->copy()->setTime(12, 0),
            'duration' => 3 * 3600,
        ]);

        // Second entry: 13:00-18:00 (5 hours)
        TimeEntry::factory()->create([
            'user_id' => $user->id,
            'date' => $monday->format('Y-m-d'),
            'start_time' => $monday->copy()->setTime(13, 0),
            'stop_time' => $monday->copy()->setTime(18, 0),
            'duration' => 5 * 3600,
        ]);

        $response = $this->actingAs($user, 'api')->getJson('/api/time-entries/summary/me');

        $response->assertOk();

        // Total: 8 hours - 1 hour break (once per day) = 7 hours
        $this->assertEquals(7, $response->json('data.total_hours'));
        $this->assertEquals(0, $response->json('data.total_minutes'));
    }
}
