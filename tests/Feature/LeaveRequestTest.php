<?php

namespace Tests\Feature;

use App\Http\Resources\LeaveRequestResource;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Notifications\NewLeaveRequestNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Notification;
use Tests\TestCase;

class LeaveRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_leave_request()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $user = User::factory()->create(['manager_id' => $manager->id]);

        Notification::fake();

        $response = $this->actingAs($user, 'api')->postJson('/api/leave-requests', [
            'type' => 'vacation',
            'start_date' => Carbon::now()->format('Y-m-d'),
            'end_date' => Carbon::now()->addDays(5)->format('Y-m-d'),
            'reason' => 'Family vacation'
        ]);

        $response->assertStatus(201);
        $leaveRequest = LeaveRequest::with('user')->first();

        $response->assertExactJson([
            'message' => 'Leave request created successfully.',
            'data' => (new LeaveRequestResource($leaveRequest))->resolve(),
        ]);

        Notification::assertSentTo($manager, NewLeaveRequestNotification::class);
    }

    public function test_user_cannot_create_overlapping_leave_request_with_pending()
    {
        $user = User::factory()->create();

        LeaveRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'start_date' => Carbon::now()->addDays(10),
            'end_date' => Carbon::now()->addDays(15),
        ]);

        $response = $this->actingAs($user, 'api')->postJson('/api/leave-requests', [
            'type' => 'vacation',
            'start_date' => Carbon::now()->addDays(12)->format('Y-m-d'),
            'end_date' => Carbon::now()->addDays(17)->format('Y-m-d'),
            'reason' => 'Overlapping vacation'
        ]);

        $response->assertStatus(400)
            ->assertJson(['message' => 'You already have a pending or approved leave request for these dates.']);
    }

    public function test_user_cannot_create_overlapping_leave_request_with_approved()
    {
        $user = User::factory()->create();

        LeaveRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'start_date' => Carbon::now()->addDays(10),
            'end_date' => Carbon::now()->addDays(15),
        ]);

        $response = $this->actingAs($user, 'api')->postJson('/api/leave-requests', [
            'type' => 'vacation',
            'start_date' => Carbon::now()->addDays(8)->format('Y-m-d'),
            'end_date' => Carbon::now()->addDays(12)->format('Y-m-d'),
            'reason' => 'Overlapping vacation'
        ]);

        $response->assertStatus(400)
            ->assertJson(['message' => 'You already have a pending or approved leave request for these dates.']);
    }

    public function test_user_can_create_leave_request_after_rejected()
    {
        $user = User::factory()->create();

        LeaveRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'rejected',
            'start_date' => Carbon::now()->addDays(10),
            'end_date' => Carbon::now()->addDays(15),
        ]);

        $response = $this->actingAs($user, 'api')->postJson('/api/leave-requests', [
            'type' => 'vacation',
            'start_date' => Carbon::now()->addDays(10)->format('Y-m-d'),
            'end_date' => Carbon::now()->addDays(15)->format('Y-m-d'),
            'reason' => 'Resubmitting after rejection'
        ]);

        $response->assertStatus(201);
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

        $response->assertStatus(200);

        $requests = LeaveRequest::query()->where('user_id', $user->id)->latest()->get();
        $expectedData = LeaveRequestResource::collection($requests)
            ->response()
            ->getData(true)['data'];

        $response->assertJson([
            'data' => $expectedData,
        ]);

        $response->assertJsonPath('meta.current_page', 1);
        $response->assertJsonPath('meta.from', 1);
        $response->assertJsonPath('meta.last_page', 1);
        $response->assertJsonPath('meta.per_page', 15);
        $response->assertJsonPath('meta.to', 3);
        $response->assertJsonPath('meta.total', 3);
        $response->assertJsonPath('links.prev', null);
        $response->assertJsonPath('links.next', null);

        $this->assertStringContainsString('/api/leave-requests?page=1', (string)$response->json('links.first'));
        $this->assertStringContainsString('/api/leave-requests?page=1', (string)$response->json('links.last'));
        $this->assertStringContainsString('/api/leave-requests', (string)$response->json('meta.path'));
    }

    public function test_user_can_view_single_leave_request_with_full_details()
    {
        $user = User::factory()->create();
        $leaveRequest = LeaveRequest::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'api')->getJson("/api/leave-requests/{$leaveRequest->id}");

        $response->assertStatus(200);

        // Show endpoint returns full data with relationships loaded
        $leaveRequestWithRelations = LeaveRequest::with(['user', 'processor'])->find($leaveRequest->id);

        $response->assertExactJson((new LeaveRequestResource($leaveRequestWithRelations))->resolve());
    }

    public function test_manager_can_view_all_leave_requests()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        LeaveRequest::factory()->count(5)->create();

        $response = $this->actingAs($manager, 'api')->getJson('/api/managers/leave-requests');

        $response->assertStatus(200);
    }

    public function test_manager_can_approve_leave_request()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $employee = User::factory()->create(['manager_id' => $manager->id]);
        $request = LeaveRequest::factory()->create(['user_id' => $employee->id, 'status' => 'pending']);

        $response = $this->actingAs($manager, 'api')->postJson("/api/managers/leave-requests/{$request->id}/approve");

        $response->assertStatus(200);
        $request->refresh()->load(['user', 'processor']);

        $response->assertExactJson([
            'message' => 'Leave request approved successfully.',
            'data' => (new LeaveRequestResource($request))->resolve(),
        ]);
    }

    public function test_manager_can_reject_leave_request()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $employee = User::factory()->create(['manager_id' => $manager->id]);
        $request = LeaveRequest::factory()->create(['user_id' => $employee->id, 'status' => 'pending']);

        $response = $this->actingAs($manager, 'api')->postJson("/api/managers/leave-requests/{$request->id}/reject", ['manager_comment' => 'Not enough details']);

        $response->assertStatus(200);
        $request->refresh()->load(['user', 'processor']);

        $response->assertExactJson([
            'message' => 'Leave request rejected successfully.',
            'data' => (new LeaveRequestResource($request))->resolve(),
        ]);
    }

    public function test_non_manager_cannot_access_manager_routes()
    {
        $user = User::factory()->create(['role' => 'employee']);
        $request = LeaveRequest::factory()->create();

        $this->actingAs($user, 'api')->getJson('/api/managers/leave-requests')->assertStatus(403);
        $this->actingAs($user, 'api')->postJson("/api/managers/leave-requests/{$request->id}/approve")->assertStatus(403);
        $this->actingAs($user, 'api')->postJson("/api/managers/leave-requests/{$request->id}/reject")->assertStatus(403);
    }
}
