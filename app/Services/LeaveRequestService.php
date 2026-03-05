<?php

namespace App\Services;

use App\Enums\LeaveRequestStatus;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Notifications\LeaveRequestStatusNotification;
use App\Repositories\LeaveRequestRepository;
use Illuminate\Support\Facades\Auth;

class LeaveRequestService
{
    public function __construct(protected LeaveRequestRepository $leaveRequestRepository)
    {
    }

    public function getAllForManager(User $manager, $perPage): array
    {
        $requests = $this->leaveRequestRepository->getAllForManager($manager, $perPage);

        return ['requests' => $requests];
    }

    public function getPendingForManager(User $manager): array
    {
        $requests = $this->leaveRequestRepository->getPendingForManager($manager);

        return ['requests' => $requests];
    }

    public function getUserLeaveRequests(User $user, int $perPage = 15): array
    {
        $requests = $this->leaveRequestRepository->getAllForUser($user, $perPage);

        return ['requests' => $requests];
    }

    public function createLeaveRequest(User $user, array $data): array
    {
        $data['status'] = LeaveRequestStatus::PENDING;
        $data['user_id'] = $user->id;

        $leaveRequest = $this->leaveRequestRepository->create($data);

        return ['leave_request' => $leaveRequest];
    }

    public function getLeaveRequestById(LeaveRequest $leaveRequest): array
    {
        $user = Auth::user();

        if ($leaveRequest->user_id !== $user->id && $user->manager_id !== $leaveRequest->user->id) {
            return ['message' => 'You are not authorized to view this leave request.'];
        }

        $leaveRequestWithRelations = $this->leaveRequestRepository->getByIdWithRelations($leaveRequest->id);

        return ['leave_request' => $leaveRequestWithRelations];
    }

    public function approve(LeaveRequest $leaveRequest): array
    {
        if ($leaveRequest->user->manager_id !== Auth::id()) {
            return ['message' => 'You are not authorized to approve this leave request.'];
        }

        $leaveRequest->update([
            'status' => LeaveRequestStatus::APPROVED,
            'processed_by' => Auth::id(),
        ]);

        $leaveRequest->user->notify(new LeaveRequestStatusNotification($leaveRequest->fresh()));

        return ['leave_request' => $leaveRequest];
    }

    public function reject(LeaveRequest $leaveRequest, string $managerComment): array
    {
        if ($leaveRequest->user->manager_id !== Auth::id()) {
            return ['message' => 'You are not authorized to reject this leave request.'];
        }

        $leaveRequest->update([
            'status' => LeaveRequestStatus::REJECTED,
            'processed_by' => Auth::id(),
            'manager_comment' => $managerComment,
        ]);

        $leaveRequest->user->notify(new LeaveRequestStatusNotification($leaveRequest->fresh()));

        return ['leave_request' => $leaveRequest];
    }
}
