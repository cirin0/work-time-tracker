<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $manager = Auth::user();
        $employeeIds = $manager->employees()->pluck('id');
        $requests = LeaveRequest::query()->whereIn('user_id', $employeeIds)
            ->where('status', 'pending')
            ->with('user:id,name,email')
            ->latest()
            ->get();
        return response()->json($requests);
    }

    public function approve(LeaveRequest $leaveRequest)
    {
        abort_if($leaveRequest->user->manager_id !== Auth::id(), 403, 'You are not authorized to approve this leave request.');
        $leaveRequest->update([
            'status' => 'approved',
            'processed_by_manager_id' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'Leave request approved successfully.',
            'leave_request' => $leaveRequest,
        ]);
    }

    public function reject(Request $request, LeaveRequest $leaveRequest)
    {
        abort_if($leaveRequest->user->manager_id !== Auth::id(), 403, 'You are not authorized to reject this leave request.');
        $validated = $request->validate([
            'manager_comments' => 'required|string|max:1000'
        ]);

        $leaveRequest->update([
            'status' => 'rejected',
            'processed_by_manager_id' => Auth::id(),
            'manager_comments' => $validated['manager_comments'],
        ]);

        return response()->json([
            'message' => 'Leave request rejected successfully.',
            'leave_request' => $leaveRequest,
        ]);
    }
}
