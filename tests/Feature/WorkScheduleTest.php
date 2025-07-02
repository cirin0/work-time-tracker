<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;

    public function test_user_can_list_work_schedules_for_their_company()
    {
        WorkSchedule::factory()->count(3)->create(['company_id' => $this->company->id]);
        WorkSchedule::factory()->count(2)->create(); // Schedules for another company

        $response = $this->getJson('/api/work-schedules');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_create_a_work_schedule()
    {
        $scheduleData = [
            'name' => 'Standard 9-5',
            'is_default' => false,
            'daily_schedules' => [
                ['day_of_week' => 'monday', 'start_time' => '09:00', 'end_time' => '17:00', 'break_duration' => 60, 'is_working_day' => true],
                ['day_of_week' => 'tuesday', 'start_time' => '09:00', 'end_time' => '17:00', 'break_duration' => 60, 'is_working_day' => true],
            ]
        ];

        $response = $this->postJson('/api/work-schedules', $scheduleData);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Standard 9-5']);

        $this->assertDatabaseHas('work_schedules', ['name' => 'Standard 9-5', 'company_id' => $this->company->id]);
        $this->assertDatabaseCount('daily_schedules', 2);
    }

    public function test_user_can_view_a_specific_work_schedule()
    {
        $schedule = WorkSchedule::factory()->create(['company_id' => $this->company->id]);

        $response = $this->getJson("/api/work-schedules/{$schedule->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $schedule->id]);
    }

    public function test_user_can_update_a_work_schedule()
    {
        $schedule = WorkSchedule::factory()->create(['company_id' => $this->company->id]);

        $updateData = ['name' => 'Updated Schedule Name'];

        $response = $this->putJson("/api/work-schedules/{$schedule->id}", $updateData);

        $response->assertStatus(200);
        $this->assertDatabaseHas('work_schedules', ['id' => $schedule->id, 'name' => 'Updated Schedule Name']);
    }

    public function test_user_can_delete_a_work_schedule()
    {
        $schedule = WorkSchedule::factory()->create(['company_id' => $this->company->id]);

        $response = $this->deleteJson("/api/work-schedules/{$schedule->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('work_schedules', ['id' => $schedule->id]);
    }

    public function test_store_fails_with_invalid_data()
    {
        $scheduleData = [
            'name' => '', // Invalid name
            'daily_schedules' => [
                // End time is before start time
                ['day_of_week' => 'monday', 'start_time' => '17:00', 'end_time' => '09:00', 'break_duration' => 60, 'is_working_day' => true],
            ]
        ];

        $response = $this->postJson('/api/work-schedules', $scheduleData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'daily_schedules.0.end_time']);
    }

    public function test_user_cannot_view_schedule_from_another_company()
    {
        $otherCompanySchedule = WorkSchedule::factory()->create();

        $response = $this->getJson("/api/work-schedules/{$otherCompanySchedule->id}");

        // Assuming the service/policy returns 404 to prevent leaking information
        $response->assertStatus(404);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user, 'api');
    }
}
