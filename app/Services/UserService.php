<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class UserService
{
    public function __construct(protected UserRepository $repository)
    {
    }

    public function getAllPaginated(): LengthAwarePaginator
    {
        return $this->repository->getPaginated();
    }

    public function getById(User $user): array
    {
        return ['user' => $user];
    }

    public function updateRole(User $user, UserRole $role): array
    {
        $user->role = $role;
        $user->save();

        return ['user' => $user];
    }

    public function updateAvatar(User $user, UploadedFile $avatar): array
    {
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $avatar->store('avatars', 'public');
        $user->update(['avatar' => $path]);

        return ['user' => $user];
    }

    public function delete(User $user): void
    {
        $user->delete();
    }

    public function update(User $user, array $data): array
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isAdmin()) {
            unset($data['role']);
        }
        $user->update($data);

        return ['user' => $user];
    }

    public function getWorkSchedule(User $user): array
    {
        $workSchedule = $user->workSchedule?->load('dailySchedules');

        return [
            'user' => $user,
            'work_schedule' => $workSchedule,
        ];
    }

    public function updateUserWorkSchedule(User $user, int $workScheduleId): array
    {
        $user->update(['work_schedule_id' => $workScheduleId]);

        return ['user' => $user];
    }
}
