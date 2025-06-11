<?php

namespace App\Services;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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

    public function update(User $user, array $data): UserResource
    {
        if (!auth()->user()->isAdmin()) {
            unset($data['role']);
        }
        $user->update($data);
        return new UserResource($user);
    }

    public function delete(User $user): JsonResponse
    {
        $user->delete();
        return response()->json([
            'message' => 'User deleted successfully',
        ], 204);
    }
}
