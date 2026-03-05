<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Exports\CompanyStatisticsExport;
use App\Exports\EmployeeStatisticsExport;
use App\Models\Company;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ManagerStatisticsTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected User $manager;

    protected User $employee;

    public function test_manager_can_get_all_employees_statistics(): void
    {
        TimeEntry::factory()->count(3)->create([
            'user_id' => $this->employee->id,
            'stop_time' => now()->subHour(),
            'duration' => 3600,
        ]);

        $response = $this->actingAs($this->manager, 'api')
            ->getJson('/api/managers/users/statistics');

        $response->assertOk()
            ->assertJson([
                'message' => 'Employee statistics retrieved successfully.',
            ])
            ->assertJsonStructure([
                'message',
                'data' => [
                    '*' => [
                        'user_id',
                        'total_hours',
                        'total_minutes',
                        'working_days',
                        'average_work_time',
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
                        'summary' => ['today', 'week', 'month'],
                    ],
                ],
            ]);
    }

    // ─── GET /api/managers/users/statistics ──────────────────────────────────

    public function test_employees_without_entries_are_not_in_statistics_list(): void
    {
        // employee has no entries
        $response = $this->actingAs($this->manager, 'api')
            ->getJson('/api/managers/users/statistics');

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }

    public function test_statistics_only_include_company_employees(): void
    {
        $otherCompany = Company::factory()->create();
        $otherEmployee = User::factory()->create(['company_id' => $otherCompany->id]);

        TimeEntry::factory()->count(3)->create([
            'user_id' => $otherEmployee->id,
            'stop_time' => now(),
        ]);

        TimeEntry::factory()->count(2)->create([
            'user_id' => $this->employee->id,
            'stop_time' => now(),
        ]);

        $response = $this->actingAs($this->manager, 'api')
            ->getJson('/api/managers/users/statistics');

        $response->assertOk();

        $userIds = collect($response->json('data'))->pluck('user_id')->toArray();
        $this->assertContains($this->employee->id, $userIds);
        $this->assertNotContains($otherEmployee->id, $userIds);
    }

    public function test_employee_cannot_access_users_statistics_endpoint(): void
    {
        $response = $this->actingAs($this->employee, 'api')
            ->getJson('/api/managers/users/statistics');

        $response->assertForbidden();
    }

    public function test_unauthenticated_cannot_access_users_statistics(): void
    {
        $this->getJson('/api/managers/users/statistics')
            ->assertUnauthorized();
    }

    public function test_statistics_data_matches_expected_totals(): void
    {
        TimeEntry::factory()->count(2)->create([
            'user_id' => $this->employee->id,
            'stop_time' => now()->subHour(),
            'duration' => 7200, // 2 hours
            'lateness_minutes' => 10,
        ]);

        $response = $this->actingAs($this->manager, 'api')
            ->getJson('/api/managers/users/statistics');

        $response->assertOk();

        $employeeStats = collect($response->json('data'))
            ->firstWhere('user_id', $this->employee->id);

        $this->assertNotNull($employeeStats);
        $this->assertEquals($this->employee->id, $employeeStats['user_id']);
        $this->assertGreaterThan(0, $employeeStats['total_hours']);
        $this->assertGreaterThan(0, $employeeStats['working_days']);
    }

    public function test_manager_can_export_company_statistics(): void
    {
        Excel::fake();

        TimeEntry::factory()->count(2)->create([
            'user_id' => $this->employee->id,
            'stop_time' => now(),
        ]);

        $this->actingAs($this->manager, 'api')
            ->get('/api/managers/company/statistics/export')
            ->assertOk();

        $expectedFilename = 'company-statistics-' . now()->format('Y-m-d') . '.xlsx';

        Excel::assertDownloaded($expectedFilename, function (CompanyStatisticsExport $export) {
            return $export->collection()->count() === 1; // one employee with entries
        });
    }

    // ─── GET /api/managers/statistics/export ─────────────────────────────────

    public function test_company_statistics_export_headings_are_complete(): void
    {
        Excel::fake();

        $this->actingAs($this->manager, 'api')
            ->get('/api/managers/company/statistics/export')
            ->assertOk();

        Excel::assertDownloaded(
            'company-statistics-' . now()->format('Y-m-d') . '.xlsx',
            function (CompanyStatisticsExport $export) {
                $headings = $export->headings();

                return in_array('Employee', $headings)
                    && in_array('Email', $headings)
                    && in_array('Working Days', $headings)
                    && in_array('Total Hours', $headings)
                    && in_array('Late Count', $headings);
            }
        );
    }

    public function test_employee_cannot_export_company_statistics(): void
    {
        $this->actingAs($this->employee, 'api')
            ->get('/api/managers/company/statistics/export')
            ->assertForbidden();
    }

    public function test_unauthenticated_cannot_export_company_statistics(): void
    {
        $this->get('/api/managers/company/statistics/export')
            ->assertUnauthorized();
    }

    public function test_manager_can_export_individual_employee_statistics(): void
    {
        Excel::fake();

        TimeEntry::factory()->count(3)->create([
            'user_id' => $this->employee->id,
            'stop_time' => now(),
            'duration' => 3600,
        ]);

        $this->actingAs($this->manager, 'api')
            ->get("/api/managers/users/{$this->employee->id}/statistics/export")
            ->assertOk();

        $expectedFilename = 'employee-statistics-' . $this->employee->id . '-' . now()->format('Y-m-d') . '.xlsx';

        Excel::assertDownloaded($expectedFilename, function (EmployeeStatisticsExport $export) {
            return $export instanceof EmployeeStatisticsExport;
        });
    }

    // ─── GET /api/managers/users/{user}/statistics/export ────────────────────

    public function test_manager_cannot_export_statistics_for_employee_from_another_company(): void
    {
        $otherCompany = Company::factory()->create();
        $otherEmployee = User::factory()->create(['company_id' => $otherCompany->id]);

        $response = $this->actingAs($this->manager, 'api')
            ->get("/api/managers/users/{$otherEmployee->id}/statistics/export");

        $response->assertForbidden()
            ->assertJson([
                'message' => 'You do not have permission to export this user\'s statistics.',
            ]);
    }

    public function test_employee_cannot_export_another_employees_statistics(): void
    {
        $anotherEmployee = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => UserRole::EMPLOYEE,
        ]);

        $this->actingAs($this->employee, 'api')
            ->get("/api/managers/users/{$anotherEmployee->id}/statistics/export")
            ->assertForbidden();
    }

    public function test_unauthenticated_cannot_export_employee_statistics(): void
    {
        $this->get("/api/managers/users/{$this->employee->id}/statistics/export")
            ->assertUnauthorized();
    }

    public function test_employee_statistics_export_contains_user_data(): void
    {
        Excel::fake();

        TimeEntry::factory()->count(2)->create([
            'user_id' => $this->employee->id,
            'stop_time' => now(),
            'duration' => 3600,
            'lateness_minutes' => 5,
        ]);

        $this->actingAs($this->manager, 'api')
            ->get("/api/managers/users/{$this->employee->id}/statistics/export")
            ->assertOk();

        $expectedFilename = 'employee-statistics-' . $this->employee->id . '-' . now()->format('Y-m-d') . '.xlsx';

        Excel::assertDownloaded($expectedFilename, function (EmployeeStatisticsExport $export) {
            $rows = $export->array();
            // First row is ['Employee', name]
            return $rows[0][0] === 'Employee'
                && $rows[0][1] === $this->employee->name;
        });
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
