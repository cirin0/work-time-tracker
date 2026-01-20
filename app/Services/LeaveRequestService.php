<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\User;
use App\Repositories\LeaveRequestRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LeaveRequestService
{
    public function __construct(protected LeaveRequestRepository $leaveRequestRepository)
    {
    }

    public function getPendingForManager(User $manager): Collection
    {
        return $this->leaveRequestRepository->getPendingForManager($manager);
    }

    public function approve(LeaveRequest $leaveRequest): LeaveRequest
    {
        if ($leaveRequest->user->manager_id !== Auth::id()) {
            throw new HttpException(403, 'You are not authorized to approve this leave request.');
        }

        $leaveRequest->update([
            'status' => 'approved',
            'processed_by_manager_id' => Auth::id(),
        ]);

        return $leaveRequest;
    }

    public function reject(LeaveRequest $leaveRequest, string $managerComments): LeaveRequest
    {
        if ($leaveRequest->user->manager_id !== Auth::id()) {
            throw new HttpException(403, 'You are not authorized to reject this leave request.');
        }

        $leaveRequest->update([
            'status' => 'rejected',
            'processed_by_manager_id' => Auth::id(),
            'manager_comments' => $managerComments,
        ]);

        return $leaveRequest;
    }
}
