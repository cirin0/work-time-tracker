<?php

namespace App\Repositories;

use App\Enums\LeaveRequestStatus;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class LeaveRequestRepository
{
    public function find(int $id): ?LeaveRequest
    {
        return LeaveRequest::query()->find($id);
    }

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

    public function getAllForUser(User $user): LengthAwarePaginator
    {
        return $user->leaveRequests()
            ->latest()
            ->paginate();
    }

    public function create(array $data): LeaveRequest
    {
        return LeaveRequest::query()->create($data);
    }

    public function update(LeaveRequest $leaveRequest, array $data): bool
    {
        return $leaveRequest->update($data);
    }

    public function delete(LeaveRequest $leaveRequest): ?bool
    {
        return $leaveRequest->delete();
    }
}
