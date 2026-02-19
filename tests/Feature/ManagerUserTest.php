<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Http\Resources\TimeEntryResource;
use App\Models\Company;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagerUserTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected User $manager;

    protected User $employee;

    public function test_manager_can_view_company_users(): void
    {
        User::factory()->count(3)->create(['company_id' => $this->company->id]);

        $response = $this->actingAs($this->manager, 'api')
            ->getJson('/api/manager/users');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email'],
                ],
            ]);
    }

    public function test_manager_can_view_employee_details(): void
    {
        $response = $this->actingAs($this->manager, 'api')
            ->getJson("/api/manager/users/{$this->employee->id}");

        $response->assertOk()
            ->assertJson([
                'message' => 'User retrieved successfully.',
            ])
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'name', 'email', 'avatar'],
            ]);
    }

    public function test_manager_cannot_view_employee_from_another_company(): void
    {
        $anotherCompany = Company::factory()->create();
        $anotherEmployee = User::factory()->create(['company_id' => $anotherCompany->id]);

        $response = $this->actingAs($this->manager, 'api')
            ->getJson("/api/manager/users/{$anotherEmployee->id}");

        $response->assertForbidden()
            ->assertJson([
                'message' => 'You do not have permission to view this user.',
            ]);
    }

    public function test_manager_can_view_employee_time_entries(): void
    {
        TimeEntry::factory()->count(3)->create(['user_id' => $this->employee->id]);

        $response = $this->actingAs($this->manager, 'api')
            ->getJson("/api/manager/users/{$this->employee->id}/time-entries");

        $response->assertOk();

        $entries = TimeEntry::with('user')
            ->where('user_id', $this->employee->id)
            ->orderBy('start_time', 'desc')
            ->get();

        $expectedData = TimeEntryResource::collection($entries)->resolve();

        $response->assertExactJson([
            'message' => 'Time entries retrieved successfully.',
            'data' => $expectedData,
        ]);
    }

    public function test_manager_cannot_view_time_entries_of_user_from_another_company(): void
    {
        $anotherCompany = Company::factory()->create();
        $anotherEmployee = User::factory()->create(['company_id' => $anotherCompany->id]);

        $response = $this->actingAs($this->manager, 'api')
            ->getJson("/api/manager/users/{$anotherEmployee->id}/time-entries");

        $response->assertForbidden()
            ->assertJson([
                'message' => 'You do not have permission to view this user\'s time entries.',
            ]);
    }

    public function test_manager_can_view_employee_time_summary(): void
    {
        TimeEntry::factory()->count(5)->create([
            'user_id' => $this->employee->id,
            'stop_time' => now(),
        ]);

        $response = $this->actingAs($this->manager, 'api')
            ->getJson("/api/manager/users/{$this->employee->id}/time-summary");

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'user_id',
                    'total_hours',
                    'total_minutes',
                    'entries_count',
                    'average_work_time',
                    'summary' => ['today', 'week', 'month'],
                ],
            ]);
    }

    public function test_manager_cannot_view_summary_of_user_from_another_company(): void
    {
        $anotherCompany = Company::factory()->create();
        $anotherEmployee = User::factory()->create(['company_id' => $anotherCompany->id]);

        $response = $this->actingAs($this->manager, 'api')
            ->getJson("/api/manager/users/{$anotherEmployee->id}/time-summary");

        $response->assertForbidden()
            ->assertJson([
                'message' => 'You do not have permission to view this user\'s statistics.',
            ]);
    }

    public function test_manager_can_view_employee_work_schedule(): void
    {
        $workSchedule = WorkSchedule::factory()->create(['company_id' => $this->company->id]);
        $this->employee->update(['work_schedule_id' => $workSchedule->id]);

        $response = $this->actingAs($this->manager, 'api')
            ->getJson("/api/manager/users/{$this->employee->id}/work-schedule");

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'user',
                'work_schedule',
            ]);
    }

    public function test_manager_cannot_view_work_schedule_of_user_from_another_company(): void
    {
        $anotherCompany = Company::factory()->create();
        $anotherEmployee = User::factory()->create(['company_id' => $anotherCompany->id]);

        $response = $this->actingAs($this->manager, 'api')
            ->getJson("/api/manager/users/{$anotherEmployee->id}/work-schedule");

        $response->assertForbidden()
            ->assertJson([
                'message' => 'You do not have permission to view this user\'s work schedule.',
            ]);
    }

    public function test_manager_can_update_employee_work_schedule(): void
    {
        $workSchedule = WorkSchedule::factory()->create(['company_id' => $this->company->id]);

        $response = $this->actingAs($this->manager, 'api')
            ->patchJson("/api/manager/users/{$this->employee->id}/work-schedule", [
                'work_schedule_id' => $workSchedule->id,
            ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Work schedule updated successfully.',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->employee->id,
            'work_schedule_id' => $workSchedule->id,
        ]);
    }

    public function test_manager_cannot_update_work_schedule_of_user_from_another_company(): void
    {
        $anotherCompany = Company::factory()->create();
        $anotherEmployee = User::factory()->create(['company_id' => $anotherCompany->id]);
        $workSchedule = WorkSchedule::factory()->create(['company_id' => $this->company->id]);

        $response = $this->actingAs($this->manager, 'api')
            ->patchJson("/api/manager/users/{$anotherEmployee->id}/work-schedule", [
                'work_schedule_id' => $workSchedule->id,
            ]);

        $response->assertForbidden()
            ->assertJson([
                'message' => 'You do not have permission to update this user\'s work schedule.',
            ]);
    }

    public function test_employee_cannot_access_manager_endpoints(): void
    {
        $response = $this->actingAs($this->employee, 'api')
            ->getJson('/api/manager/users');

        $response->assertForbidden();
    }

    public function test_manager_can_view_company_statistics(): void
    {
        $employee1 = User::factory()->create(['company_id' => $this->company->id]);
        $employee2 = User::factory()->create(['company_id' => $this->company->id]);

        // Створюємо завершені записи
        TimeEntry::factory()->count(3)->create([
            'user_id' => $employee1->id,
            'stop_time' => now()->subHours(2),
            'duration' => 7200, // 2 години
        ]);

        TimeEntry::factory()->count(2)->create([
            'user_id' => $employee2->id,
            'stop_time' => now()->subHours(1),
            'duration' => 3600, // 1 година
        ]);

        // Створюємо активні записи
        TimeEntry::factory()->create([
            'user_id' => $employee1->id,
            'stop_time' => null,
        ]);

        $response = $this->actingAs($this->manager, 'api')
            ->getJson('/api/manager/statistics');

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'company_id',
                    'total_hours',
                    'total_minutes',
                    'entries_count',
                    'active_entries_count',
                    'active_employees',
                    'total_employees_with_entries',
                    'summary' => [
                        'today' => ['minutes', 'hours', 'entries'],
                        'week' => ['minutes', 'hours', 'entries'],
                        'month' => ['minutes', 'hours', 'entries'],
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals($this->company->id, $data['company_id']);
        $this->assertGreaterThan(0, $data['entries_count']);
        $this->assertEquals(1, $data['active_entries_count']);
        $this->assertEquals(1, $data['active_employees']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->manager = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => UserRole::MANAGER,
        ]);
        $this->employee = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => UserRole::EMPLOYEE,
        ]);
    }
}
