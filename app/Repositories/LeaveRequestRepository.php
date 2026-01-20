<?php

namespace App\Repositories;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class LeaveRequestRepository
{
    public function getPendingForManager(User $manager): Collection
    {
        $employeeIds = $manager->employees()->pluck('id');

        return LeaveRequest::query()
            ->whereIn('user_id', $employeeIds)
            ->where('status', 'pending')
            ->with('user:id,name,email')
            ->latest()
            ->get();
    }
}
