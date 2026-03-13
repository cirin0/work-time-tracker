<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\WorkMode;
use App\Http\Resources\TimeEntryResource;
use App\Http\Resources\TimeEntrySummaryResource;
use App\Models\Company;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\TimeEntryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_start_and_stop_time_entry(): void
    {
        $user = User::factory()->create(['pin_code' => bcrypt('1234')]);

        $response = $this->actingAs($user, 'api')->postJson('/api/time-entries');
        $response->assertCreated();
        $entry = TimeEntry::query()->where('user_id', $user->id)->first();
        $entry->load('user');

        $response->assertExactJson([
            'message' => 'Time entry started successfully.',
            'data' => (new TimeEntryResource($entry))->resolve(),
        ]);

        $response = $this->actingAs($user, 'api')->patchJson('/api/time-entries/active/stop', ['pin_code' => '1234']);
        $response->assertOk();
        $entry->refresh()->load('user');

        $response->assertExactJson([
            'message' => 'Time entry stopped successfully.',
            'data' => (new TimeEntryResource($entry))->resolve(),
        ]);
    }

    public function test_user_cannot_start_time_entry_if_already_active(): void
    {
        $user = User::factory()->create();
        TimeEntry::factory()->create(['user_id' => $user->id, 'stop_time' => null]);

        $this->actingAs($user, 'api')->postJson('/api/time-entries')->assertStatus(400);
    }

    public function test_user_cannot_stop_time_entry_that_is_already_stopped(): void
    {
        $user = User::factory()->create();
        TimeEntry::factory()->create(['user_id' => $user->id, 'stop_time' => now()]);

        $this->actingAs($user, 'api')->patchJson('/api/time-entries/active/stop', ['pin_code' => '1234'])->assertStatus(400);
    }

    public function test_user_can_view_their_time_entries(): void
    {
        $user = User::factory()->create();
        TimeEntry::factory()->count(5)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'api')->getJson('/api/time-entries');
        $response->assertOk();

        $response->assertJsonStructure([
            'data' => [],
            'links',
            'meta',
        ]);

        $response->assertJsonPath('meta.total', 5);
    }

    public function test_user_can_get_active_time_entry(): void
    {
        $user = User::factory()->create();
        $activeEntry = TimeEntry::factory()->create([
            'user_id' => $user->id,
            'stop_time' => null,
        ]);
        $activeEntry->load('user');

        $response = $this->actingAs($user, 'api')->getJson('/api/time-entries/active');
        $response->assertOk();

        $response->assertExactJson([
            'message' => 'Active time entry retrieved successfully.',
            'data' => (new TimeEntryResource($activeEntry))->resolve(),
        ]);
    }

    public function test_user_gets_null_when_no_active_time_entry(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')->getJson('/api/time-entries/active')
            ->assertOk()
            ->assertJson([
                'message' => 'No active time entry found.',
                'data' => null,
            ]);
    }

    public function test_user_can_view_single_time_entry(): void
    {
        $user = User::factory()->create();
        $timeEntry = TimeEntry::factory()->create(['user_id' => $user->id]);
        $timeEntry->load('user');

        $response = $this->actingAs($user, 'api')->getJson("/api/time-entries/{$timeEntry->id}");
        $response->assertOk();

        $response->assertExactJson([
            'message' => 'Time entry retrieved successfully.',
            'data' => (new TimeEntryResource($timeEntry))->resolve(),
        ]);
    }

    public function test_user_cannot_view_other_users_time_entry(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $timeEntry = TimeEntry::factory()->create(['user_id' => $otherUser->id]);

        $this->actingAs($user, 'api')->getJson("/api/time-entries/{$timeEntry->id}")
            ->assertForbidden();
    }

    public function test_user_can_delete_their_time_entry(): void
    {
        $user = User::factory()->create();
        $timeEntry = TimeEntry::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'api')->deleteJson("/api/time-entries/{$timeEntry->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('time_entries', ['id' => $timeEntry->id]);
    }

    public function test_user_cannot_delete_other_users_time_entry(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $timeEntry = TimeEntry::factory()->create(['user_id' => $otherUser->id]);

        $this->actingAs($user, 'api')->deleteJson("/api/time-entries/{$timeEntry->id}")
            ->assertForbidden();
    }

    public function test_user_can_get_time_summary(): void
    {
        $user = User::factory()->create();
        TimeEntry::factory()->create([
            'user_id' => $user->id,
            'start_time' => now()->subHours(2),
            'stop_time' => now(),
        ]);

        $response = $this->actingAs($user, 'api')->getJson('/api/time-entries/summary/me');
        $response->assertOk();

        $summaryData = app(TimeEntryService::class)->getTimeSummary($user);

        $response->assertExactJson([
            'message' => 'Time summary retrieved successfully.',
            'data' => (new TimeEntrySummaryResource($summaryData))->resolve(),
        ]);
    }

    public function test_unauthenticated_user_cannot_access_time_entry_routes(): void
    {
        $this->postJson('/api/time-entries')->assertUnauthorized();
        $this->getJson('/api/time-entries')->assertUnauthorized();
        $this->getJson('/api/time-entries/summary/me')->assertUnauthorized();
    }

    public function test_user_can_create_multiple_entries_in_same_day(): void
    {
        $user = User::factory()->create(['pin_code' => bcrypt('1234')]);

        // Перший запис: 08:00 - 10:30
        $this->actingAs($user, 'api')->postJson('/api/time-entries')->assertCreated();
        $firstEntry = TimeEntry::query()->where('user_id', $user->id)->first();

        $this->actingAs($user, 'api')
            ->patchJson('/api/time-entries/active/stop', ['pin_code' => '1234'])
            ->assertOk();

        // Другий запис: після закінчення першого
        $this->actingAs($user, 'api')->postJson('/api/time-entries')->assertCreated();
        $secondEntry = TimeEntry::query()
            ->where('user_id', $user->id)
            ->whereNull('stop_time')
            ->first();

        $this->assertNotEquals($firstEntry->id, $secondEntry->id);
        $this->assertEquals($firstEntry->date, $secondEntry->date);

        // Перевіряємо що обидва записи за сьогодні
        $todayEntries = TimeEntry::query()
            ->where('user_id', $user->id)
            ->whereDate('date', today())
            ->get();

        $this->assertCount(2, $todayEntries);
    }

    public function test_statistics_count_working_days_not_entries(): void
    {
        $user = User::factory()->create(['pin_code' => bcrypt('1234')]);
        $today = now();

        // Створюємо 3 записи за сьогодні (один день)
        for ($i = 0; $i < 3; $i++) {
            TimeEntry::factory()->create([
                'user_id' => $user->id,
                'date' => $today->toDateString(),
                'start_time' => $today->copy()->addHours($i * 2),
                'stop_time' => $today->copy()->addHours($i * 2 + 1),
                'duration' => 3600,
            ]);
        }

        // Створюємо 2 записи за вчора (ще один день)
        for ($i = 0; $i < 2; $i++) {
            TimeEntry::factory()->create([
                'user_id' => $user->id,
                'date' => $today->copy()->subDay()->toDateString(),
                'start_time' => $today->copy()->subDay()->addHours($i * 2),
                'stop_time' => $today->copy()->subDay()->addHours($i * 2 + 1),
                'duration' => 3600,
            ]);
        }

        $response = $this->actingAs($user, 'api')->getJson('/api/time-entries/summary/me');
        $response->assertOk();

        $data = $response->json('data');

        // Має бути 2 робочих дні (сьогодні + вчора), а не 5 записів
        $this->assertEquals(2, $data['working_days']);

        // Загальний час: 5 записів * 1 година = 5 годин
        $this->assertEquals(5, $data['total_hours']);

        // Середній час: 5 годин / 2 дні = 150 хвилин на день
        $this->assertEquals(150, $data['average_work_time']);
    }

    public function test_user_can_work_with_breaks_during_day(): void
    {
        $user = User::factory()->create(['pin_code' => bcrypt('1234')]);

        // Сценарій: прийшов, пішов на обід, повернувся

        // 1. Прийшов о 08:00
        $this->actingAs($user, 'api')->postJson('/api/time-entries')->assertCreated();

        // 2. Пішов о 12:00 (на обід)
        $this->actingAs($user, 'api')
            ->patchJson('/api/time-entries/active/stop', ['pin_code' => '1234'])
            ->assertOk();

        // 3. Повернувся о 13:00
        $this->actingAs($user, 'api')->postJson('/api/time-entries')->assertCreated();

        // Перевіряємо що є 2 записи за сьогодні
        $entries = TimeEntry::query()
            ->where('user_id', $user->id)
            ->whereDate('date', today())
            ->get();

        $this->assertCount(2, $entries);

        // Перший запис закритий, другий активний
        $this->assertNotNull($entries[0]->stop_time);
        $this->assertNull($entries[1]->stop_time);
    }

    public function test_user_can_view_paginated_time_entries(): void
    {
        $user = User::factory()->create();
        TimeEntry::factory()->count(20)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'api')->getJson('/api/time-entries?per_page=10');
        $response->assertOk();

        $response->assertJsonStructure([
            'data' => [],
            'links' => [
                'first',
                'last',
                'prev',
                'next',
            ],
            'meta' => [
                'current_page',
                'from',
                'last_page',
                'per_page',
                'to',
                'total',
            ],
        ]);

        $response->assertJsonPath('meta.per_page', 10);
        $response->assertJsonPath('meta.total', 20);
        $response->assertJsonPath('meta.last_page', 2);
    }

    public function test_time_entries_pagination_defaults_to_15_per_page(): void
    {
        $user = User::factory()->create();
        TimeEntry::factory()->count(20)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'api')->getJson('/api/time-entries');
        $response->assertOk();

        $response->assertJsonPath('meta.per_page', 15);
    }

    public function test_admin_can_start_time_entry_without_gps_and_qr_code(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'work_mode' => WorkMode::OFFICE,
        ]);

        $response = $this->actingAs($admin, 'api')->postJson('/api/time-entries', [
            'start_comment' => 'Admin starting time entry without GPS',
        ]);

        $response->assertCreated();
        $response->assertJsonStructure([
            'message',
            'data' => [
                'id',
                'user',
                'start_time',
                'entry_type',
                'start_comment',
            ],
        ]);

        $this->assertDatabaseHas('time_entries', [
            'user_id' => $admin->id,
            'start_comment' => 'Admin starting time entry without GPS',
        ]);
    }

    public function test_admin_with_office_mode_bypasses_gps_radius_check(): void
    {
        $company = Company::factory()->create([
            'latitude' => 50.4501,
            'longitude' => 30.5234,
            'radius_meters' => 100,
        ]);

        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'work_mode' => WorkMode::OFFICE,
            'company_id' => $company->id,
        ]);

        $response = $this->actingAs($admin, 'api')->postJson('/api/time-entries', [
            'start_comment' => 'Admin without GPS',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('time_entries', [
            'user_id' => $admin->id,
            'start_comment' => 'Admin without GPS',
        ]);
    }

    public function test_admin_with_office_mode_bypasses_qr_code_check(): void
    {
        $company = Company::factory()->create();

        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'work_mode' => WorkMode::OFFICE,
            'company_id' => $company->id,
        ]);

        // Admin can start without QR code
        $response = $this->actingAs($admin, 'api')->postJson('/api/time-entries', [
            'start_comment' => 'Admin without QR code',
        ]);

        $response->assertCreated();
    }

    public function test_non_admin_with_office_mode_requires_gps_and_qr_code(): void
    {
        $company = Company::factory()->create([
            'latitude' => 50.4501,
            'longitude' => 30.5234,
            'radius_meters' => 100,
        ]);

        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'work_mode' => WorkMode::OFFICE,
            'company_id' => $company->id,
        ]);

        $response = $this->actingAs($employee, 'api')->postJson('/api/time-entries', [
            'start_comment' => 'Employee without GPS',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['latitude', 'longitude', 'qr_code']);
    }

    public function test_admin_entry_type_is_manual_when_no_gps_provided(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'work_mode' => WorkMode::OFFICE,
        ]);

        $response = $this->actingAs($admin, 'api')->postJson('/api/time-entries', [
            'start_comment' => 'Manual admin entry',
        ]);

        $response->assertCreated();

        $entry = TimeEntry::query()->where('user_id', $admin->id)->first();
        $this->assertEquals('manual', $entry->entry_type->value);
    }
}
