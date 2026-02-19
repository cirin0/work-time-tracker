<?php

namespace Tests\Feature;

use App\Http\Resources\TimeEntryResource;
use App\Http\Resources\TimeEntrySummaryResource;
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

        $entries = TimeEntry::with('user')->where('user_id', $user->id)->orderBy('start_time', 'desc')->get();
        $expectedData = TimeEntryResource::collection($entries)->resolve();

        $response->assertExactJson([
            'message' => 'Time entries retrieved successfully.',
            'data' => $expectedData,
        ]);
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
}
