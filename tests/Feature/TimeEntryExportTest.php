<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Exports\TimeEntryExport;
use App\Models\Company;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class TimeEntryExportTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected User $employee;

    protected User $manager;

    public function test_authenticated_user_can_export_their_own_time_entries(): void
    {
        Excel::fake();

        TimeEntry::factory()->count(3)->create([
            'user_id' => $this->employee->id,
            'stop_time' => now(),
            'duration' => 3600,
        ]);

        $this->actingAs($this->employee, 'api')
            ->get('/api/time-entries/export')
            ->assertOk();

        $expectedFilename = 'time-entries-' . now()->format('Y-m-d') . '.xlsx';

        Excel::assertDownloaded($expectedFilename, function (TimeEntryExport $export) {
            return $export->collection()->count() === 3;
        });
    }

    public function test_export_only_includes_current_users_entries(): void
    {
        Excel::fake();

        $otherUser = User::factory()->create(['company_id' => $this->company->id]);

        TimeEntry::factory()->count(2)->create([
            'user_id' => $this->employee->id,
            'stop_time' => now(),
        ]);
        TimeEntry::factory()->count(5)->create([
            'user_id' => $otherUser->id,
            'stop_time' => now(),
        ]);

        $this->actingAs($this->employee, 'api')
            ->get('/api/time-entries/export')
            ->assertOk();

        Excel::assertDownloaded(
            'time-entries-' . now()->format('Y-m-d') . '.xlsx',
            function (TimeEntryExport $export) {
                return $export->collection()->count() === 2;
            }
        );
    }

    public function test_export_respects_from_date_filter(): void
    {
        Excel::fake();

        TimeEntry::factory()->create([
            'user_id' => $this->employee->id,
            'date' => now()->subDays(10)->format('Y-m-d'),
            'start_time' => now()->subDays(10),
            'stop_time' => now()->subDays(10),
        ]);
        TimeEntry::factory()->create([
            'user_id' => $this->employee->id,
            'date' => now()->subDays(2)->format('Y-m-d'),
            'start_time' => now()->subDays(2),
            'stop_time' => now()->subDays(2),
        ]);

        $from = now()->subDays(5)->format('Y-m-d');

        $this->actingAs($this->employee, 'api')
            ->get("/api/time-entries/export?from={$from}")
            ->assertOk();

        Excel::assertDownloaded(
            'time-entries-' . now()->format('Y-m-d') . '.xlsx',
            function (TimeEntryExport $export) {
                return $export->collection()->count() === 1;
            }
        );
    }

    public function test_export_respects_to_date_filter(): void
    {
        Excel::fake();

        TimeEntry::factory()->create([
            'user_id' => $this->employee->id,
            'date' => now()->subDays(10)->format('Y-m-d'),
            'start_time' => now()->subDays(10),
            'stop_time' => now()->subDays(10),
        ]);
        TimeEntry::factory()->create([
            'user_id' => $this->employee->id,
            'date' => now()->subDays(2)->format('Y-m-d'),
            'start_time' => now()->subDays(2),
            'stop_time' => now()->subDays(2),
        ]);

        $to = now()->subDays(5)->format('Y-m-d');

        $this->actingAs($this->employee, 'api')
            ->get("/api/time-entries/export?to={$to}")
            ->assertOk();

        Excel::assertDownloaded(
            'time-entries-' . now()->format('Y-m-d') . '.xlsx',
            function (TimeEntryExport $export) {
                return $export->collection()->count() === 1;
            }
        );
    }

    public function test_export_with_no_entries_returns_empty_file(): void
    {
        Excel::fake();

        $this->actingAs($this->employee, 'api')
            ->get('/api/time-entries/export')
            ->assertOk();

        Excel::assertDownloaded(
            'time-entries-' . now()->format('Y-m-d') . '.xlsx',
            function (TimeEntryExport $export) {
                return $export->collection()->count() === 0;
            }
        );
    }

    public function test_unauthenticated_user_cannot_export(): void
    {
        $this->getJson('/api/time-entries/export')
            ->assertUnauthorized();
    }

    public function test_export_headings_contain_expected_columns(): void
    {
        Excel::fake();

        $this->actingAs($this->employee, 'api')
            ->get('/api/time-entries/export')
            ->assertOk();

        Excel::assertDownloaded(
            'time-entries-' . now()->format('Y-m-d') . '.xlsx',
            function (TimeEntryExport $export) {
                $headings = $export->headings();

                return in_array('Дата', $headings)
                    && in_array('Час початку', $headings)
                    && in_array('Час завершення', $headings)
                    && in_array('Тривалість (хв)', $headings)
                    && in_array('Тип запису', $headings);
            }
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->employee = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => UserRole::EMPLOYEE,
        ]);
        $this->manager = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => UserRole::MANAGER,
        ]);
    }
}
