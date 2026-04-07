<?php

namespace Tests\Feature;

use App\Enums\EntryType;
use App\Enums\LeaveRequestStatus;
use App\Enums\LeaveRequestType;
use App\Enums\WorkMode;
use App\Models\Company;
use App\Models\LeaveRequest;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveBlockingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_clock_in_during_approved_sick_leave(): void
    {
        $user = User::factory()->create(['pin_code' => bcrypt('1234')]);

        LeaveRequest::factory()->create([
            'user_id' => $user->id,
            'type' => LeaveRequestType::SICK->value,
            'status' => LeaveRequestStatus::APPROVED->value,
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addDays(3),
        ]);

        $this->actingAs($user, 'api')
            ->postJson('/api/time-entries')
            ->assertStatus(400)
            ->assertJson([
                'message' => 'You cannot clock in during approved leave.',
            ]);
    }

    public function test_user_cannot_clock_in_during_approved_vacation(): void
    {
        $user = User::factory()->create(['pin_code' => bcrypt('1234')]);

        LeaveRequest::factory()->create([
            'user_id' => $user->id,
            'type' => LeaveRequestType::VACATION->value,
            'status' => LeaveRequestStatus::APPROVED->value,
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addDays(7),
        ]);

        $this->actingAs($user, 'api')
            ->postJson('/api/time-entries')
            ->assertStatus(400)
            ->assertJson([
                'message' => 'You cannot clock in during approved leave.',
            ]);
    }

    public function test_user_cannot_clock_in_during_approved_unpaid_leave(): void
    {
        $user = User::factory()->create(['pin_code' => bcrypt('1234')]);

        LeaveRequest::factory()->create([
            'user_id' => $user->id,
            'type' => LeaveRequestType::UNPAID->value,
            'status' => LeaveRequestStatus::APPROVED->value,
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addDays(5),
        ]);

        $this->actingAs($user, 'api')
            ->postJson('/api/time-entries')
            ->assertStatus(400)
            ->assertJson([
                'message' => 'You cannot clock in during approved leave.',
            ]);
    }

    public function test_user_cannot_clock_in_during_approved_personal_leave(): void
    {
        $user = User::factory()->create(['pin_code' => bcrypt('1234')]);

        LeaveRequest::factory()->create([
            'user_id' => $user->id,
            'type' => LeaveRequestType::PERSONAL->value,
            'status' => LeaveRequestStatus::APPROVED->value,
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addDays(2),
        ]);

        $this->actingAs($user, 'api')
            ->postJson('/api/time-entries')
            ->assertStatus(400)
            ->assertJson([
                'message' => 'You cannot clock in during approved leave.',
            ]);
    }

    public function test_user_can_clock_in_during_pending_leave(): void
    {
        $user = User::factory()->create(['pin_code' => bcrypt('1234')]);

        LeaveRequest::factory()->create([
            'user_id' => $user->id,
            'type' => LeaveRequestType::VACATION->value,
            'status' => LeaveRequestStatus::PENDING->value,
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addDays(5),
        ]);

        $this->actingAs($user, 'api')
            ->postJson('/api/time-entries')
            ->assertStatus(201);

        $this->assertDatabaseHas('time_entries', [
            'user_id' => $user->id,
            'stop_time' => null,
        ]);
    }

    public function test_user_can_clock_in_during_rejected_leave(): void
    {
        $user = User::factory()->create(['pin_code' => bcrypt('1234')]);

        LeaveRequest::factory()->create([
            'user_id' => $user->id,
            'type' => LeaveRequestType::VACATION->value,
            'status' => LeaveRequestStatus::REJECTED->value,
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addDays(5),
        ]);

        $this->actingAs($user, 'api')
            ->postJson('/api/time-entries')
            ->assertStatus(201);

        $this->assertDatabaseHas('time_entries', [
            'user_id' => $user->id,
            'stop_time' => null,
        ]);
    }

    public function test_user_can_clock_in_during_approved_business_trip(): void
    {
        $user = User::factory()->create([
            'pin_code' => bcrypt('1234'),
            'work_mode' => WorkMode::REMOTE->value,
        ]);

        LeaveRequest::factory()->create([
            'user_id' => $user->id,
            'type' => LeaveRequestType::BUSINESS_TRIP->value,
            'status' => LeaveRequestStatus::APPROVED->value,
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addDays(3),
        ]);

        $this->actingAs($user, 'api')
            ->postJson('/api/time-entries')
            ->assertStatus(201);

        $this->assertDatabaseHas('time_entries', [
            'user_id' => $user->id,
            'stop_time' => null,
        ]);
    }

    public function test_office_worker_on_business_trip_skips_gps_qr_validation(): void
    {
        $company = Company::factory()->create([
            'latitude' => 50.4501,
            'longitude' => 30.5234,
            'radius_meters' => 100,
        ]);

        $user = User::factory()->create([
            'pin_code' => bcrypt('1234'),
            'work_mode' => WorkMode::OFFICE->value,
            'company_id' => $company->id,
        ]);

        LeaveRequest::factory()->create([
            'user_id' => $user->id,
            'type' => LeaveRequestType::BUSINESS_TRIP->value,
            'status' => LeaveRequestStatus::APPROVED->value,
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addDays(5),
        ]);

        // Clock in without GPS/QR data (should succeed because of business trip)
        $this->actingAs($user, 'api')
            ->postJson('/api/time-entries')
            ->assertStatus(201);

        $entry = TimeEntry::where('user_id', $user->id)->first();
        $this->assertEquals(EntryType::MANUAL->value, $entry->entry_type->value);
        $this->assertNull($entry->location_data);
    }

    public function test_office_worker_on_business_trip_with_gps_sets_remote_entry_type(): void
    {
        $company = Company::factory()->create([
            'latitude' => 50.4501,
            'longitude' => 30.5234,
            'radius_meters' => 100,
        ]);

        $user = User::factory()->create([
            'pin_code' => bcrypt('1234'),
            'work_mode' => WorkMode::OFFICE->value,
            'company_id' => $company->id,
        ]);

        LeaveRequest::factory()->create([
            'user_id' => $user->id,
            'type' => LeaveRequestType::BUSINESS_TRIP->value,
            'status' => LeaveRequestStatus::APPROVED->value,
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addDays(5),
        ]);

        // Clock in with GPS data from different location
        $this->actingAs($user, 'api')
            ->postJson('/api/time-entries', [
                'latitude' => 49.8397,
                'longitude' => 24.0297,
            ])
            ->assertStatus(201);

        $entry = TimeEntry::where('user_id', $user->id)->first();
        $this->assertEquals(EntryType::REMOTE->value, $entry->entry_type->value);
        $this->assertNotNull($entry->location_data);
        $this->assertEquals(49.8397, $entry->location_data['latitude']);
        $this->assertEquals(24.0297, $entry->location_data['longitude']);
    }

    public function test_office_worker_without_business_trip_requires_gps_and_qr(): void
    {
        $company = Company::factory()->create([
            'latitude' => 50.4501,
            'longitude' => 30.5234,
            'radius_meters' => 100,
        ]);

        $user = User::factory()->create([
            'pin_code' => bcrypt('1234'),
            'work_mode' => WorkMode::OFFICE->value,
            'company_id' => $company->id,
        ]);

        // Try to clock in without GPS/QR (should fail with validation error)
        $this->actingAs($user, 'api')
            ->postJson('/api/time-entries')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['latitude', 'longitude', 'qr_code']);
    }

    public function test_user_can_clock_in_when_leave_is_in_future(): void
    {
        $user = User::factory()->create(['pin_code' => bcrypt('1234')]);

        LeaveRequest::factory()->create([
            'user_id' => $user->id,
            'type' => LeaveRequestType::VACATION->value,
            'status' => LeaveRequestStatus::APPROVED->value,
            'start_date' => Carbon::today()->addDays(5),
            'end_date' => Carbon::today()->addDays(10),
        ]);

        $this->actingAs($user, 'api')
            ->postJson('/api/time-entries')
            ->assertStatus(201);

        $this->assertDatabaseHas('time_entries', [
            'user_id' => $user->id,
            'stop_time' => null,
        ]);
    }

    public function test_user_can_clock_in_when_leave_is_in_past(): void
    {
        $user = User::factory()->create(['pin_code' => bcrypt('1234')]);

        LeaveRequest::factory()->create([
            'user_id' => $user->id,
            'type' => LeaveRequestType::VACATION->value,
            'status' => LeaveRequestStatus::APPROVED->value,
            'start_date' => Carbon::today()->subDays(10),
            'end_date' => Carbon::today()->subDays(5),
        ]);

        $this->actingAs($user, 'api')
            ->postJson('/api/time-entries')
            ->assertStatus(201);

        $this->assertDatabaseHas('time_entries', [
            'user_id' => $user->id,
            'stop_time' => null,
        ]);
    }

    public function test_remote_worker_on_business_trip_can_clock_in(): void
    {
        $user = User::factory()->create([
            'pin_code' => bcrypt('1234'),
            'work_mode' => WorkMode::REMOTE->value,
        ]);

        LeaveRequest::factory()->create([
            'user_id' => $user->id,
            'type' => LeaveRequestType::BUSINESS_TRIP->value,
            'status' => LeaveRequestStatus::APPROVED->value,
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addDays(3),
        ]);

        $this->actingAs($user, 'api')
            ->postJson('/api/time-entries')
            ->assertStatus(201);

        $entry = TimeEntry::where('user_id', $user->id)->first();
        $this->assertEquals(EntryType::MANUAL->value, $entry->entry_type->value);
    }

    public function test_hybrid_worker_on_business_trip_can_clock_in(): void
    {
        $user = User::factory()->create([
            'pin_code' => bcrypt('1234'),
            'work_mode' => WorkMode::HYBRID->value,
        ]);

        LeaveRequest::factory()->create([
            'user_id' => $user->id,
            'type' => LeaveRequestType::BUSINESS_TRIP->value,
            'status' => LeaveRequestStatus::APPROVED->value,
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addDays(3),
        ]);

        $this->actingAs($user, 'api')
            ->postJson('/api/time-entries')
            ->assertStatus(201);

        $entry = TimeEntry::where('user_id', $user->id)->first();
        $this->assertEquals(EntryType::MANUAL->value, $entry->entry_type->value);
    }
}
