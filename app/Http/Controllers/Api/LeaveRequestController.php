<?php

namespace App\Http\Controllers\Api;

use App\Enums\LeaveRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeaveRequestRequest;
use App\Http\Resources\LeaveRequestResource;
use App\Models\LeaveRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

class LeaveRequestController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $requests = Auth::user()->leaveRequests()->latest()->paginate();

        return LeaveRequestResource::collection($requests);
    }

    public function store(StoreLeaveRequestRequest $request): JsonResponse
    {
        $leaveRequest = Auth::user()->leaveRequests()->create([
            ...$request->validated(),
            'status' => LeaveRequestStatus::PENDING,
        ]);

        return response()->json([
            'message' => 'Leave request created successfully.',
            'data' => new LeaveRequestResource($leaveRequest),
        ], 201);
    }

    public function show(LeaveRequest $leaveRequest): JsonResponse
    {
        if ($leaveRequest->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        return response()->json([
            'data' => new LeaveRequestResource($leaveRequest),
        ]);
    }
}
