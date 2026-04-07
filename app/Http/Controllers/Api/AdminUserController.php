<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Enums\WorkMode;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminResetPasswordRequest;
use App\Http\Requests\AdminUpdateUserRequest;
use App\Http\Requests\AdminUpdateUserRoleRequest;
use App\Http\Requests\AdminUpdateWorkModeRequest;
use App\Http\Resources\AdminUserResource;
use App\Models\User;
use App\Services\TimeEntryService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminUserController extends Controller
{
    public function __construct(
        protected UserService      $userService,
        protected TimeEntryService $timeEntryService
    )
    {
    }

    public function getAllUsers(): AnonymousResourceCollection
    {
        $search = request()->query('search');

        $users = User::query()
            ->with(['company', 'workSchedule', 'manager'])
            ->when(!empty($search), function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ilike', "%{$search}%")
                        ->orWhere('email', 'ilike', "%{$search}%");
                });
            })
            ->paginate(15);

        return AdminUserResource::collection($users);
    }

    public function getUser(User $user): JsonResponse
    {
        $user->load(['company', 'workSchedule', 'manager']);

        return response()->json([
            'message' => 'User retrieved successfully.',
            'data' => new AdminUserResource($user),
        ]);
    }

    public function getUsersByCompany(int $companyId): AnonymousResourceCollection
    {
        $search = request()->query('search');

        $users = User::query()
            ->where('company_id', $companyId)
            ->with(['company', 'workSchedule', 'manager'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ilike', "%{$search}%")
                        ->orWhere('email', 'ilike', "%{$search}%");
                });
            })
            ->paginate(15);

        return AdminUserResource::collection($users);
    }

    public function updateUser(AdminUpdateUserRequest $request, User $user): JsonResponse
    {
        $data = $this->userService->update($user, $request->validated());

        return response()->json([
            'message' => 'User updated successfully.',
            'data' => new AdminUserResource($data['user']),
        ]);
    }

    public function updateUserRole(AdminUpdateUserRoleRequest $request, User $user): JsonResponse
    {
        $role = UserRole::from($request->validated('role'));
        $data = $this->userService->updateRole($user, $role);

        return response()->json([
            'message' => 'User role updated successfully.',
            'data' => new AdminUserResource($data['user']),
        ]);
    }

    public function updateWorkMode(AdminUpdateWorkModeRequest $request, User $user): JsonResponse
    {
        $workMode = WorkMode::from($request->validated('work_mode'));
        $data = $this->userService->updateWorkMode($user, $workMode);

        return response()->json([
            'message' => 'User work mode updated successfully.',
            'data' => new AdminUserResource($data['user']),
        ]);
    }

    public function resetPassword(AdminResetPasswordRequest $request, User $user): JsonResponse
    {
        $data = $this->userService->resetPassword($user, $request->validated('password'));

        return response()->json([
            'message' => 'User password reset successfully.',
            'data' => new AdminUserResource($data['user']),
        ]);
    }

    public function deleteUser(User $user): JsonResponse
    {
        $this->userService->delete($user);

        return response()->json([
            'message' => 'User deleted successfully.',
        ], 204);
    }
}
