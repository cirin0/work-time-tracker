<?php

namespace App\Repositories;

use App\Enums\LeaveRequestStatus;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class LeaveRequestRepository
{
    public function getPendingForManager(User $manager): Collection
    {
        $employeeIds = $manager->employees()->pluck('id');

        return LeaveRequest::query()
            ->whereIn('user_id', $employeeIds)
            ->where('status', LeaveRequestStatus::PENDING)
            ->with('user:id,name,email')
            ->latest()
            ->get();
    }

    public function getUserLeaveRequests(User $user): LengthAwarePaginator
    {
        return $user->leaveRequests()->latest()->paginate();
    }

    public function create(User $user, array $data): LeaveRequest
    {
        return $user->leaveRequests()->create($data);
    }

    public function findById(LeaveRequest $leaveRequest): ?LeaveRequest
    {
        return LeaveRequest::find($leaveRequest->id);
    }
}
