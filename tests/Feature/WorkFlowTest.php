<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_office_worker_can_start_time_with_valid_gps_and_qr(): void
    {
        $company = Company::factory()->create([
            'latitude' => 50.4501,
            'longitude' => 30.5234,
            'radius_meters' => 100,
            'qr_secret' => 'secret123',
        ]);

        $user = User::factory()->office()->create([
            'company_id' => $company->id,
        ]);

        $qrCode = hash('sha256', 'secret123' . date('Y-m-d'));

        $response = $this->actingAs($user, 'api')->postJson('/api/time-entries', [
            'latitude' => 50.4501,
            'longitude' => 30.5234,
            'qr_code' => $qrCode,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('time_entries', [
            'user_id' => $user->id,
        ]);

        $entry = TimeEntry::where('user_id', $user->id)->first();
        $this->assertEquals(50.4501, $entry->location_data['latitude']);
    }

    public function test_office_worker_cannot_start_time_with_wrong_gps(): void
    {
        $company = Company::factory()->create([
            'latitude' => 50.4501,
            'longitude' => 30.5234,
            'radius_meters' => 100,
            'qr_secret' => 'secret123',
        ]);

        $user = User::factory()->office()->create([
            'company_id' => $company->id,
        ]);

        $qrCode = hash('sha256', 'secret123' . date('Y-m-d'));

        $response = $this->actingAs($user, 'api')->postJson('/api/time-entries', [
            'latitude' => 51.0000,
            'longitude' => 31.0000,
            'qr_code' => $qrCode,
        ]);

        $response->assertStatus(400);
        $response->assertJsonFragment(['message' => 'You are outside the office radius.']);
    }

    public function test_office_worker_cannot_start_time_with_wrong_qr(): void
    {
        $company = Company::factory()->create([
            'latitude' => 50.4501,
            'longitude' => 30.5234,
            'radius_meters' => 100,
            'qr_secret' => 'secret123',
        ]);

        $user = User::factory()->office()->create([
            'company_id' => $company->id,
        ]);

        $response = $this->actingAs($user, 'api')->postJson('/api/time-entries', [
            'latitude' => 50.4501,
            'longitude' => 30.5234,
            'qr_code' => 'wrong-qr',
        ]);

        $response->assertStatus(400);
        $response->assertJsonFragment(['message' => 'Invalid or expired QR code.']);
    }

    public function test_remote_worker_can_start_time_without_gps_and_qr(): void
    {
        $user = User::factory()->remote()->create();

        $response = $this->actingAs($user, 'api')->postJson('/api/time-entries');

        $response->assertCreated();
    }

    public function test_office_worker_validation_fails_without_params(): void
    {
        $user = User::factory()->office()->create();

        $response = $this->actingAs($user, 'api')->postJson('/api/time-entries', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['latitude', 'longitude', 'qr_code']);
    }

    public function test_user_can_stop_time_with_valid_pin(): void
    {
        $user = User::factory()->create(['pin_code' => '4321']);
        TimeEntry::factory()->create([
            'user_id' => $user->id,
            'stop_time' => null,
        ]);

        $response = $this->actingAs($user, 'api')->patchJson('/api/time-entries/active/stop', [
            'pin_code' => '4321',
        ]);

        $response->assertOk();
        $this->assertDatabaseMissing('time_entries', [
            'user_id' => $user->id,
            'stop_time' => null,
        ]);
    }

    public function test_user_cannot_stop_time_with_invalid_pin(): void
    {
        $user = User::factory()->create(['pin_code' => '4321']);
        TimeEntry::factory()->create([
            'user_id' => $user->id,
            'stop_time' => null,
        ]);

        $response = $this->actingAs($user, 'api')->patchJson('/api/time-entries/active/stop', [
            'pin_code' => '1111',
        ]);

        $response->assertStatus(400);
        $response->assertJsonFragment(['message' => 'Invalid pin code.']);
        $this->assertDatabaseHas('time_entries', [
            'user_id' => $user->id,
            'stop_time' => null,
        ]);
    }
}
