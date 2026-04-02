<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveLeaveRequestRequest;
use App\Http\Requests\RejectLeaveRequestRequest;
use App\Http\Resources\LeaveRequestResource;
use App\Models\LeaveRequest;
use App\Services\LeaveRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

class ManagerLeaveRequestController extends Controller
{
    public function __construct(protected LeaveRequestService $leaveRequestService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->input('per_page', 15);
        $result = $this->leaveRequestService->getAllForManager(Auth::user(), $perPage);

        return LeaveRequestResource::collection($result['requests']);
    }

    public function getPendingLeaveRequests(): AnonymousResourceCollection
    {
        $result = $this->leaveRequestService->getPendingForManager(Auth::user());

        return LeaveRequestResource::collection($result['requests']);
    }

    public function show(LeaveRequest $leaveRequest): JsonResponse
    {
        $user = Auth::user();

        if ($user->company_id !== $leaveRequest->user->company_id) {
            return response()->json([
                'message' => 'You are not authorized to view this leave request.',
            ], 403);
        }

        $leaveRequest->load(['user', 'processor']);

        return response()->json([
            'data' => new LeaveRequestResource($leaveRequest),
        ]);
    }

    public function approve(ApproveLeaveRequestRequest $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $result = $this->leaveRequestService->approve(
            $leaveRequest,
            $request->validated('manager_comment')
        );

        if (isset($result['message'])) {
            return response()->json(['message' => $result['message']], 403);
        }

        $leaveRequest = $result['leave_request']->load(['user', 'processor']);

        return response()->json([
            'message' => 'Leave request approved successfully.',
            'data' => new LeaveRequestResource($leaveRequest),
        ]);
    }

    public function reject(RejectLeaveRequestRequest $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $result = $this->leaveRequestService->reject(
            $leaveRequest,
            $request->validated('manager_comment')
        );

        if (isset($result['message'])) {
            return response()->json(['message' => $result['message']], 403);
        }

        $leaveRequest = $result['leave_request']->load(['user', 'processor']);

        return response()->json([
            'message' => 'Leave request rejected successfully.',
            'data' => new LeaveRequestResource($leaveRequest),
        ]);
    }
}
