<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\UpdateUserRoleRequest;
use App\Http\Requests\UpdateUserWorkScheduleRequest;
use App\Http\Requests\UploadAvatarRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    public function __construct(protected UserService $userService)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        $data = $this->userService->getAllPaginated();

        return UserResource::collection($data['users']);
    }

    public function show(User $user): UserResource
    {
        $data = $this->userService->getById($user);

        return new UserResource($data['user']);
    }

    public function updateRole(UpdateUserRoleRequest $request, User $user): JsonResponse
    {
        Gate::authorize('update-role', $user);
        $role = UserRole::from($request->validated('role'));
        $data = $this->userService->updateRole($user, $role);

        return response()->json([
            'message' => 'User role updated successfully',
            'user' => new UserResource($data['user']),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        Gate::authorize('manage-profile', $user);
        $data = $this->userService->update($user, $request->validated());

        return response()->json([
            'message' => 'User updated successfully',
            'user' => new UserResource($data['user']),
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        Gate::any('manage-profile', $user);
        $this->userService->delete($user);

        return response()->json([
            'message' => 'User deleted successfully',
        ], 204);
    }

    public function uploadAvatar(UploadAvatarRequest $request, User $user): JsonResponse
    {
        Gate::authorize('manage-profile', $user);
        $data = $this->userService->updateAvatar($user, $request->validated('avatar'));

        return response()->json([
            'message' => 'Avatar updated successfully',
            'user' => new UserResource($data['user']),
        ]);
    }

    public function getWorkSchedule(User $user): JsonResponse
    {
        Gate::authorize('manage-profile', $user);
        $data = $this->userService->getWorkSchedule($user);

        if (!$data['work_schedule']) {
            return response()->json([
                'message' => 'User has no work schedule assigned',
                'user' => new UserResource($data['user']),
            ]);
        }

        return response()->json([
            'work_schedule' => $data['work_schedule'],
            'user' => new UserResource($data['user']),
        ]);
    }

    public function updateWorkSchedule(UpdateUserWorkScheduleRequest $request, User $user): JsonResponse
    {
        Gate::authorize('manage-profile', $user);
        $workScheduleId = $request->validated('work_schedule_id');

        if ($user->work_schedule_id === $workScheduleId) {
            return response()->json([
                'message' => 'User already has this work schedule assigned',
                'user' => new UserResource($user),
            ]);
        }

        $data = $this->userService->updateUserWorkSchedule($user, $workScheduleId);

        return response()->json([
            'message' => 'Work schedule updated successfully',
            'user' => new UserResource($data['user']),
        ]);
    }
}
