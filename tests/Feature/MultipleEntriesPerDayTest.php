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

class MultipleEntriesPerDayTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_entry_calculates_lateness(): void
    {
        $company = Company::factory()->create();
        $workSchedule = $this->createWorkSchedule($company->id);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'work_schedule_id' => $workSchedule->id,
            'pin_code' => bcrypt('1234'),
        ]);

        $monday = Carbon::now()->startOfWeek();

        // Start first entry at 9:30 (30 minutes late)
        Carbon::setTestNow($monday->copy()->setTime(9, 30));

        $this->actingAs($user, 'api')->postJson('/api/time-entries')->assertCreated();

        $entry = TimeEntry::where('user_id', $user->id)->first();

        // Should have lateness calculated (30 min late - 5 min grace = 25 min)
        $this->assertEquals(25, $entry->lateness_minutes);
        $this->assertEquals('09:00:00', $entry->scheduled_start_time);
    }

    private function createWorkSchedule(int $companyId): WorkSchedule
    {
        $workSchedule = WorkSchedule::create([
            'name' => 'Test Schedule',
            'company_id' => $companyId,
            'is_default' => false,
        ]);

        DailySchedule::create([
            'work_schedule_id' => $workSchedule->id,
            'day_of_week' => 'monday',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'break_duration' => 60,
            'is_working_day' => true,
        ]);

        return $workSchedule;
    }

    public function test_second_entry_does_not_calculate_lateness(): void
    {
        $company = Company::factory()->create();
        $workSchedule = $this->createWorkSchedule($company->id);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'work_schedule_id' => $workSchedule->id,
            'pin_code' => bcrypt('1234'),
        ]);

        $monday = Carbon::now()->startOfWeek();

        // First entry: 9:00-12:00
        Carbon::setTestNow($monday->copy()->setTime(9, 0));
        $this->actingAs($user, 'api')->postJson('/api/time-entries')->assertCreated();

        Carbon::setTestNow($monday->copy()->setTime(12, 0));
        $this->actingAs($user, 'api')->patchJson('/api/time-entries/active/stop', [
            'pin_code' => '1234',
        ])->assertOk();

        // Second entry: 13:00
        Carbon::setTestNow($monday->copy()->setTime(13, 0));
        $this->actingAs($user, 'api')->postJson('/api/time-entries')->assertCreated();

        $entries = TimeEntry::where('user_id', $user->id)
            ->orderBy('start_time')
            ->get();

        // First entry should have lateness
        $this->assertEquals(0, $entries[0]->lateness_minutes);
        $this->assertEquals('09:00:00', $entries[0]->scheduled_start_time);

        // Second entry should NOT have lateness
        $this->assertNull($entries[1]->lateness_minutes);
        $this->assertNull($entries[1]->scheduled_start_time);
    }

    public function test_first_entry_does_not_calculate_early_leave_or_overtime(): void
    {
        $company = Company::factory()->create();
        $workSchedule = $this->createWorkSchedule($company->id);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'work_schedule_id' => $workSchedule->id,
            'pin_code' => bcrypt('1234'),
        ]);

        $monday = Carbon::now()->startOfWeek();

        // First entry: 9:00-12:00
        Carbon::setTestNow($monday->copy()->setTime(9, 0));
        $this->actingAs($user, 'api')->postJson('/api/time-entries')->assertCreated();

        Carbon::setTestNow($monday->copy()->setTime(12, 0));
        $this->actingAs($user, 'api')->patchJson('/api/time-entries/active/stop', [
            'pin_code' => '1234',
        ])->assertOk();

        // Start second entry to make first entry "not the last"
        Carbon::setTestNow($monday->copy()->setTime(13, 0));
        $this->actingAs($user, 'api')->postJson('/api/time-entries')->assertCreated();

        $entry = TimeEntry::where('user_id', $user->id)->orderBy('start_time')->first();

        // Should NOT have early_leave or overtime (not the last entry anymore)
        $this->assertNull($entry->early_leave_minutes);
        $this->assertNull($entry->overtime_minutes);
        $this->assertNull($entry->scheduled_end_time);
    }

    public function test_last_entry_calculates_early_leave_and_overtime(): void
    {
        $company = Company::factory()->create();
        $workSchedule = $this->createWorkSchedule($company->id);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'work_schedule_id' => $workSchedule->id,
            'pin_code' => bcrypt('1234'),
        ]);

        $monday = Carbon::now()->startOfWeek();

        // First entry: 9:00-12:00
        Carbon::setTestNow($monday->copy()->setTime(9, 0));
        $this->actingAs($user, 'api')->postJson('/api/time-entries')->assertCreated();

        Carbon::setTestNow($monday->copy()->setTime(12, 0));
        $this->actingAs($user, 'api')->patchJson('/api/time-entries/active/stop', [
            'pin_code' => '1234',
        ])->assertOk();

        // Second entry: 13:00-17:00 (2 hours early)
        Carbon::setTestNow($monday->copy()->setTime(13, 0));
        $this->actingAs($user, 'api')->postJson('/api/time-entries')->assertCreated();

        Carbon::setTestNow($monday->copy()->setTime(17, 0));
        $this->actingAs($user, 'api')->patchJson('/api/time-entries/active/stop', [
            'pin_code' => '1234',
        ])->assertOk();

        $entries = TimeEntry::where('user_id', $user->id)
            ->orderBy('start_time')
            ->get();

        // First entry should NOT have early_leave/overtime
        $this->assertNull($entries[0]->early_leave_minutes);
        $this->assertNull($entries[0]->overtime_minutes);

        // Second (last) entry should have early_leave
        // Expected end: 19:00 (18:00 + 60 min break), actual: 17:00
        $this->assertEquals(120, $entries[1]->early_leave_minutes);
        $this->assertEquals(0, $entries[1]->overtime_minutes);
        $this->assertEquals('18:00:00', $entries[1]->scheduled_end_time);
    }

    public function test_last_entry_with_overtime(): void
    {
        $company = Company::factory()->create();
        $workSchedule = $this->createWorkSchedule($company->id);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'work_schedule_id' => $workSchedule->id,
            'pin_code' => bcrypt('1234'),
        ]);

        $monday = Carbon::now()->startOfWeek();

        // First entry: 9:00-12:00
        Carbon::setTestNow($monday->copy()->setTime(9, 0));
        $this->actingAs($user, 'api')->postJson('/api/time-entries')->assertCreated();

        Carbon::setTestNow($monday->copy()->setTime(12, 0));
        $this->actingAs($user, 'api')->patchJson('/api/time-entries/active/stop', [
            'pin_code' => '1234',
        ])->assertOk();

        // Second entry: 13:00-20:00 (1 hour overtime)
        Carbon::setTestNow($monday->copy()->setTime(13, 0));
        $this->actingAs($user, 'api')->postJson('/api/time-entries')->assertCreated();

        Carbon::setTestNow($monday->copy()->setTime(20, 0));
        $this->actingAs($user, 'api')->patchJson('/api/time-entries/active/stop', [
            'pin_code' => '1234',
        ])->assertOk();

        $entries = TimeEntry::where('user_id', $user->id)
            ->orderBy('start_time')
            ->get();

        // Second (last) entry should have overtime
        // Expected end: 19:00 (18:00 + 60 min break), actual: 20:00
        $this->assertEquals(60, $entries[1]->overtime_minutes);
        $this->assertEquals(0, $entries[1]->early_leave_minutes);
    }

    public function test_single_entry_calculates_everything(): void
    {
        $company = Company::factory()->create();
        $workSchedule = $this->createWorkSchedule($company->id);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'work_schedule_id' => $workSchedule->id,
            'pin_code' => bcrypt('1234'),
        ]);

        $monday = Carbon::now()->startOfWeek();

        // Single entry: 9:30-20:00
        Carbon::setTestNow($monday->copy()->setTime(9, 30));
        $this->actingAs($user, 'api')->postJson('/api/time-entries')->assertCreated();

        Carbon::setTestNow($monday->copy()->setTime(20, 0));
        $this->actingAs($user, 'api')->patchJson('/api/time-entries/active/stop', [
            'pin_code' => '1234',
        ])->assertOk();

        $entry = TimeEntry::where('user_id', $user->id)->first();

        // Should have all calculations (first and last entry)
        $this->assertEquals(25, $entry->lateness_minutes); // 30 min late - 5 min grace = 25 min
        $this->assertEquals('09:00:00', $entry->scheduled_start_time);
        $this->assertEquals(60, $entry->overtime_minutes); // 1 hour overtime (20:00 - 19:00)
        $this->assertEquals('18:00:00', $entry->scheduled_end_time);
    }

    public function test_total_hours_with_multiple_entries(): void
    {
        $company = Company::factory()->create();
        $workSchedule = $this->createWorkSchedule($company->id);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'work_schedule_id' => $workSchedule->id,
            'pin_code' => bcrypt('1234'),
        ]);

        $monday = Carbon::now()->startOfWeek();

        // Entry 1: 9:00-12:00 (3 hours)
        TimeEntry::factory()->create([
            'user_id' => $user->id,
            'date' => $monday->format('Y-m-d'),
            'start_time' => $monday->copy()->setTime(9, 0),
            'stop_time' => $monday->copy()->setTime(12, 0),
            'duration' => 3 * 3600,
        ]);

        // Entry 2: 13:00-18:00 (5 hours)
        TimeEntry::factory()->create([
            'user_id' => $user->id,
            'date' => $monday->format('Y-m-d'),
            'start_time' => $monday->copy()->setTime(13, 0),
            'stop_time' => $monday->copy()->setTime(18, 0),
            'duration' => 5 * 3600,
        ]);

        $response = $this->actingAs($user, 'api')->getJson('/api/time-entries/summary/me');

        $response->assertOk();

        // Total: 8 hours - 1 hour break = 7 hours
        $this->assertEquals(7, $response->json('data.total_hours'));
        $this->assertEquals(0, $response->json('data.total_minutes'));
    }
}
