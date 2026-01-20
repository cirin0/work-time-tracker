<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Services\LeaveRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ManagerLeaveRequestController extends Controller
{

    public function __construct(protected LeaveRequestService $leaveRequestService)
    {
    }


    public function index()
    {
        $requests = $this->leaveRequestService->getPendingForManager(Auth::user());
        return response()->json($requests);
    }

    public function approve(LeaveRequest $leaveRequest)
    {
        $leaveRequest = $this->leaveRequestService->approve($leaveRequest);

        return response()->json([
            'message' => 'Leave request approved successfully.',
            'leave_request' => $leaveRequest,
        ]);
    }

    public function reject(Request $request, LeaveRequest $leaveRequest)
    {
        $validated = $request->validate([
            'manager_comments' => 'required|string|max:1000'
        ]);

        $leaveRequest = $this->leaveRequestService->reject($leaveRequest, $validated['manager_comments']);

        return response()->json([
            'message' => 'Leave request rejected successfully.',
            'leave_request' => $leaveRequest,
        ]);
    }
}
