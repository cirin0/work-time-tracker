<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    public function __construct(protected UserService $userService)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return $this->userService->getAllPaginated();
    }

    public function show(User $user): UserResource
    {
        Gate::any('manage-profile', $user);
        return $this->userService->getById($user);
    }

    public function updateRole(Request $request, User $user): JsonResponse
    {
        Gate::authorize('update-role', $user);
        $validated = $request->validate([
            'role' => 'required|in:employee,admin,manager',
        ]);
        return $this->userService->updateRole($user, $validated['role']);
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        Gate::authorize('manage-profile', $user);
        return $this->userService->update($user, $request->validated());
    }

    public function destroy(User $user): JsonResponse
    {
        Gate::any('manage-profile', $user);
        return $this->userService->delete($user);
    }

    public function uploadAvatar(Request $request, User $user): JsonResponse
    {
        Gate::authorize('manage-profile', $user);
        $validated = $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        return $this->userService->updateAvatar($user, $validated['avatar']);
    }

    public function getWorkSchedule(User $user): array
    {
        Gate::authorize('manage-profile', $user);
        return $this->userService->getWorkSchedule($user);
    }

    public function updateWorkSchedule(Request $request, User $user): array
    {
        $validated = $request->validate([
            'work_schedule_id' => 'required'
        ]);
        return $this->userService->updateUserWorkSchedule($user, $validated['work_schedule_id']);
    }
}
