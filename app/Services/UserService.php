<?php

namespace App\Services;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UserService
{
    public function __construct(protected UserRepository $repository)
    {
    }

    public function getAllPaginated(): AnonymousResourceCollection
    {
        return UserResource::collection($this->repository->getPaginated());
    }

    public function getById(User $user): UserResource
    {
        return new UserResource($user);
    }

    public function updateRole(User $user, string $role): JsonResponse
    {
        $user->role = $role;
        $user->save();

        return response()->json([
            'message' => 'User role updated successfully',
            'user' => new UserResource($user),
        ]);
    }

    public function updateAvatar(User $user, UploadedFile $avatar): JsonResponse
    {
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $avatar->store('avatars', 'public');
        $user->update(['avatar' => $path]);

        return response()->json([
            'message' => 'Avatar updated successfully',
            'user' => new UserResource($user),
        ]);
    }

    public function delete(User $user): JsonResponse
    {
        $user->delete();
        return response()->json([
            'message' => 'User deleted successfully',
        ], 204);
    }

    public function update(User $user, array $data): UserResource
    {
        if (!auth()->user()->isAdmin()) {
            unset($data['role']);
        }
        $user->update($data);
        return new UserResource($user);
    }

    public function getWorkSchedule(User $user): array
    {
        $workSchedule = $user->workSchedule;
        if (!$workSchedule) {
            return [
                'message' => 'User has no work schedule assigned',
                'user' => new UserResource($user)
            ];
        }

        return [
            'work_schedule' => $workSchedule->load('dailySchedules'),
            'user' => new UserResource($user)
        ];
    }

    public function updateUserWorkSchedule(User $user, int $workScheduleId): array
    {
        $user->update(['work_schedule_id' => $workScheduleId]);

        return [
            'message' => 'Work schedule updated successfully',
            'user' => new UserResource($user)
        ];
    }
}
