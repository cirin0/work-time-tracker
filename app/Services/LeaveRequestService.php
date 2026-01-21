<?php

namespace App\Services;

use App\Enums\LeaveRequestStatus;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Repositories\LeaveRequestRepository;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class LeaveRequestService
{
    public function __construct(protected LeaveRequestRepository $leaveRequestRepository)
    {
    }

    public function getPendingForManager(User $manager): array
    {
        $requests = $this->leaveRequestRepository->getPendingForManager($manager);

        return ['requests' => $requests];
    }

    public function getUserLeaveRequests(User $user): array
    {
        $requests = $this->leaveRequestRepository->getUserLeaveRequests($user);

        return ['requests' => $requests];
    }

    public function createLeaveRequest(User $user, array $data): array
    {
        $data['status'] = LeaveRequestStatus::PENDING;

        $leaveRequest = $this->leaveRequestRepository->create($user, $data);

        return ['leave_request' => $leaveRequest];
    }

    public function getLeaveRequestById(LeaveRequest $leaveRequest): array
    {
        $user = Auth::user();

        if ($leaveRequest->user_id !== $user->id && $user->manager_id !== $leaveRequest->user->id) {
            throw new UnauthorizedHttpException('', 'You are not authorized to view this leave request.');
        }
        return ['leave_request' => $leaveRequest];
    }

    public function approve(LeaveRequest $leaveRequest): array
    {
        if ($leaveRequest->user->manager_id !== Auth::id()) {
            throw new UnauthorizedHttpException('', 'You are not authorized to approve this leave request.');
        }

        $leaveRequest->update([
            'status' => LeaveRequestStatus::APPROVED,
            'processed_by_manager_id' => Auth::id(),
        ]);

        return ['leave_request' => $leaveRequest];
    }

    public function reject(LeaveRequest $leaveRequest, string $managerComments): array
    {
        if ($leaveRequest->user->manager_id !== Auth::id()) {
            throw new UnauthorizedHttpException('', 'You are not authorized to reject this leave request.');
        }

        $leaveRequest->update([
            'status' => LeaveRequestStatus::REJECTED,
            'processed_by_manager_id' => Auth::id(),
            'manager_comments' => $managerComments,
        ]);

        return ['leave_request' => $leaveRequest];
    }
}
