<?php

namespace Tests\Feature;

use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_clock_in_and_clock_out()
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')->postJson('/api/clock-in')->assertStatus(201);
        $this->assertDatabaseHas('time_entries', ['user_id' => $user->id, 'stop_time' => null]);

        $this->actingAs($user, 'api')->postJson('/api/clock-out')->assertStatus(200);
        $this->assertDatabaseMissing('time_entries', ['user_id' => $user->id, 'stop_time' => null]);
    }

    public function test_user_cannot_clock_in_if_already_clocked_in()
    {
        $user = User::factory()->create();
        TimeEntry::factory()->create(['user_id' => $user->id, 'stop_time' => null]);

        $this->actingAs($user, 'api')->postJson('/api/clock-in')->assertStatus(400);
    }

    public function test_user_cannot_clock_out_if_not_clocked_in()
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')->postJson('/api/clock-out')->assertStatus(400);
    }

    public function test_user_can_view_their_time_entries()
    {
        $user = User::factory()->create();
        TimeEntry::factory()->count(5)->create(['user_id' => $user->id]);

        $this->actingAs($user, 'api')->getJson('/api/time-entries')
            ->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    public function test_user_can_get_time_summary()
    {
        $user = User::factory()->create();
        TimeEntry::factory()->create(['user_id' => $user->id, 'start_time' => now()->subHours(2),
            'stop_time' => now(),]);

        $this->actingAs($user, 'api')->getJson('/api/me/time-summary')
            ->assertStatus(200)
            ->assertJsonStructure(['data' => ['summary' => ['today', 'week']]]);
    }

    public function test_unauthenticated_user_cannot_access_time_entry_routes()
    {
        $this->postJson('/api/clock-in')->assertStatus(401);
        $this->postJson('/api/clock-out')->assertStatus(401);
        $this->getJson('/api/time-entries')->assertStatus(401);
        $this->getJson('/api/me/time-summary')->assertStatus(401);
    }
}
