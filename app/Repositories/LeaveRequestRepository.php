<?php

namespace App\Repositories;

use App\Enums\LeaveRequestStatus;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class LeaveRequestRepository
{
    public function getAllForManager(User $manager, int $perPage = 10): LengthAwarePaginator
    {
        return LeaveRequest::query()
            ->whereHas('user', fn($query) => $query->where('manager_id', $manager->id))
            ->with('user:id,name,email')
            ->latest()
            ->paginate($perPage);
    }

    public function getPendingForManager(User $manager): Collection
    {
        return LeaveRequest::query()
            ->whereHas('user', fn($query) => $query->where('manager_id', $manager->id))
            ->where('status', LeaveRequestStatus::PENDING)
            ->with('user:id,name,email')
            ->latest()
            ->get();
    }

    public function getAllForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $user->leaveRequests()
            ->latest()
            ->paginate($perPage);
    }

    public function getByIdWithRelations(int $id): ?LeaveRequest
    {
        return LeaveRequest::query()
            ->with(['user', 'processor'])
            ->find($id);
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
