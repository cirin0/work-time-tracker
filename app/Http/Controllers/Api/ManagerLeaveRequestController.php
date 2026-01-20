<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RejectLeaveRequestRequest;
use App\Http\Resources\LeaveRequestResource;
use App\Models\LeaveRequest;
use App\Services\LeaveRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

class ManagerLeaveRequestController extends Controller
{

    public function __construct(protected LeaveRequestService $leaveRequestService)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        $requests = $this->leaveRequestService->getPendingForManager(Auth::user());

        return LeaveRequestResource::collection($requests);
    }

    public function approve(LeaveRequest $leaveRequest): JsonResponse
    {
        $leaveRequest = $this->leaveRequestService->approve($leaveRequest);

        return response()->json([
            'message' => 'Leave request approved successfully.',
            'data' => new LeaveRequestResource($leaveRequest),
        ]);
    }

    public function reject(RejectLeaveRequestRequest $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $leaveRequest = $this->leaveRequestService->reject(
            $leaveRequest,
            $request->validated('manager_comments')
        );

        return response()->json([
            'message' => 'Leave request rejected successfully.',
            'data' => new LeaveRequestResource($leaveRequest),
        ]);
    }
}
