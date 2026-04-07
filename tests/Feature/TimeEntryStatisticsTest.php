<?php

use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;

test('statistics correctly calculate total hours and minutes from duration field', function () {
    $user = User::factory()->create();

    // Create a time entry with 197 seconds duration (3 minutes 17 seconds)
    TimeEntry::factory()->create([
        'user_id' => $user->id,
        'date' => Carbon::today(),
        'start_time' => Carbon::now()->subMinutes(4),
        'stop_time' => Carbon::now(),
        'duration' => 197, // 3 minutes 17 seconds
    ]);

    $response = $this->actingAs($user)->getJson('/api/time-entries/summary/me');

    $response->assertOk();
    $response->assertJsonPath('data.total_hours', 0);
    $response->assertJsonPath('data.total_minutes', 3); // Should be 3 minutes (197 seconds rounded)
});

test('statistics correctly sum multiple entries durations', function () {
    $user = User::factory()->create();

    // Create multiple entries with different durations
    TimeEntry::factory()->create([
        'user_id' => $user->id,
        'date' => Carbon::today(),
        'start_time' => Carbon::today()->setTime(9, 0),
        'stop_time' => Carbon::today()->setTime(10, 30),
        'duration' => 5400, // 90 minutes (1 hour 30 minutes)
    ]);

    TimeEntry::factory()->create([
        'user_id' => $user->id,
        'date' => Carbon::today(),
        'start_time' => Carbon::today()->setTime(11, 0),
        'stop_time' => Carbon::today()->setTime(12, 15),
        'duration' => 4500, // 75 minutes (1 hour 15 minutes)
    ]);

    $response = $this->actingAs($user)->getJson('/api/time-entries/summary/me');

    $response->assertOk();
    $response->assertJsonPath('data.total_hours', 2);
    $response->assertJsonPath('data.total_minutes', 45); // 90 + 75 = 165 minutes = 2h 45m
});

test('statistics handle entries with exact hour durations', function () {
    $user = User::factory()->create();

    // Create entry with exactly 2 hours
    TimeEntry::factory()->create([
        'user_id' => $user->id,
        'date' => Carbon::today(),
        'start_time' => Carbon::today()->setTime(9, 0),
        'stop_time' => Carbon::today()->setTime(11, 0),
        'duration' => 7200, // 120 minutes = 2 hours
    ]);

    $response = $this->actingAs($user)->getJson('/api/time-entries/summary/me');

    $response->assertOk();
    $response->assertJsonPath('data.total_hours', 2);
    $response->assertJsonPath('data.total_minutes', 0);
});

test('statistics correctly calculate working days', function () {
    $user = User::factory()->create();

    // Create entries on different days
    TimeEntry::factory()->create([
        'user_id' => $user->id,
        'date' => Carbon::today(),
        'start_time' => Carbon::today()->setTime(9, 0),
        'stop_time' => Carbon::today()->setTime(17, 0),
        'duration' => 28800, // 8 hours
    ]);

    TimeEntry::factory()->create([
        'user_id' => $user->id,
        'date' => Carbon::yesterday(),
        'start_time' => Carbon::yesterday()->setTime(9, 0),
        'stop_time' => Carbon::yesterday()->setTime(17, 0),
        'duration' => 28800, // 8 hours
    ]);

    $response = $this->actingAs($user)->getJson('/api/time-entries/summary/me');

    $response->assertOk();
    $response->assertJsonPath('data.working_days', 2);
    $response->assertJsonPath('data.total_hours', 16);
});
