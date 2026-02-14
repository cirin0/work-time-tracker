<?php

namespace Tests\Feature;

use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_leave_request()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->postJson('/api/leave-requests', [
            'type' => 'vacation',
            'start_date' => Carbon::now()->format('Y-m-d'),
            'end_date' => Carbon::now()->addDays(5)->format('Y-m-d'),
            'reason' => 'Family vacation'
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('leave_requests', ['user_id' => $user->id, 'type' => 'vacation']);
    }

    public function test_leave_request_creation_requires_valid_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->postJson('/api/leave-requests', []);

        $response->assertStatus(422)->assertJsonValidationErrors(['type', 'start_date', 'end_date']);
    }

    public function test_user_can_view_their_own_leave_requests()
    {
        $user = User::factory()->create();
        LeaveRequest::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'api')->getJson('/api/leave-requests');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_manager_can_view_all_leave_requests()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        LeaveRequest::factory()->count(5)->create();

        $response = $this->actingAs($manager, 'api')->getJson('/api/manager/leave-requests');

        $response->assertStatus(200);
    }

    public function test_manager_can_approve_leave_request()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $employee = User::factory()->create(['manager_id' => $manager->id]);
        $request = LeaveRequest::factory()->create(['user_id' => $employee->id, 'status' => 'pending']);

        $response = $this->actingAs($manager, 'api')->postJson("/api/manager/leave-requests/{$request->id}/approve");

        $response->assertStatus(200);
        $this->assertDatabaseHas('leave_requests', ['id' => $request->id, 'status' => 'approved']);
    }

    public function test_manager_can_reject_leave_request()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $employee = User::factory()->create(['manager_id' => $manager->id]);
        $request = LeaveRequest::factory()->create(['user_id' => $employee->id, 'status' => 'pending']);

        $response = $this->actingAs($manager, 'api')->postJson("/api/manager/leave-requests/{$request->id}/reject", ['manager_comment' => 'Not enough details']);

        $response->assertStatus(200);
        $this->assertDatabaseHas('leave_requests', ['id' => $request->id, 'status' => 'rejected']);
    }

    public function test_non_manager_cannot_access_manager_routes()
    {
        $user = User::factory()->create(['role' => 'employee']);
        $request = LeaveRequest::factory()->create();

        $this->actingAs($user, 'api')->getJson('/api/manager/leave-requests')->assertStatus(403);
        $this->actingAs($user, 'api')->postJson("/api/manager/leave-requests/{$request->id}/approve")->assertStatus(403);
        $this->actingAs($user, 'api')->postJson("/api/manager/leave-requests/{$request->id}/reject")->assertStatus(403);
    }
}
