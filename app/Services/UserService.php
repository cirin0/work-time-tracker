<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserService
{
    public function __construct(protected UserRepository $repository)
    {
    }

    public function getAllPaginated(): array
    {
        $users = $this->repository->getPaginated();

        return ['users' => $users];
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

    public function delete(User $user): array
    {
        $deleted = $this->repository->delete($user);

        return ['deleted' => $deleted];
    }

    public function update(User $user, array $data): array
    {
        $authUser = Auth::user();

        if (!$authUser->isAdmin()) {
            unset($data['role']);
        }
        unset($data['password']);
        $user->update($data);

        return ['user' => $user];
    }

    public function updateProfile(User $user, array $data): array
    {
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

    public function changePassword(User $user, array $data): array
    {
        if (! Hash::check($data['current_password'], $user->password)) {
            return ['message' => 'The current password is incorrect.'];
        }

        $user->update(['password' => Hash::make($data['new_password'])]);

        return ['user' => $user];
    }
}
