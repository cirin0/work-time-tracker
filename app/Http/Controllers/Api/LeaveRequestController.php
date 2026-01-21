<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeaveRequestRequest;
use App\Http\Resources\LeaveRequestResource;
use App\Models\LeaveRequest;
use App\Services\LeaveRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

class LeaveRequestController extends Controller
{
    public function __construct(protected LeaveRequestService $leaveRequestService)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        $result = $this->leaveRequestService->getUserLeaveRequests(Auth::user());

        return LeaveRequestResource::collection($result['requests']);
    }

    public function store(StoreLeaveRequestRequest $request): JsonResponse
    {
        $result = $this->leaveRequestService->createLeaveRequest(
            Auth::user(),
            $request->validated()
        );

        return response()->json([
            'message' => 'Leave request created successfully.',
            'data' => new LeaveRequestResource($result['leave_request']),
        ], 201);
    }

    public function showById(LeaveRequest $leaveRequest): LeaveRequestResource
    {
        $result = $this->leaveRequestService->getLeaveRequestById($leaveRequest);

        return new LeaveRequestResource($result['leave_request']);
    }
}
