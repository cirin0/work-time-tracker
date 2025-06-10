<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;

class UserController extends Controller
{
    #[OA\Get(
        path: '/api/users',
        operationId: 'getUsers',
        description: 'Retrieve a paginated list of users with their details.',
        summary: 'Get a list of all users',
        tags: ['Users'],
        responses: [
            new OA\Response(
                response: '200',
                description: 'A paginated list of users',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        ref: '#/components/schemas/UserResource'
                    )
                )
            ),
            new OA\Response(
                response: '401',
                description: 'Unauthorized'
            ),
            new OA\Response(
                response: '403',
                description: 'Forbidden'
            )
        ]
    )]
    public function index()
    {
        return UserResource::collection(User::latest()->paginate(10));
    }

    #[OA\Get(
        path: '/api/users/{id}',
        operationId: 'getUser',
        description: 'Retrieve a user by ID.',
        summary: 'Get a user by ID',
        tags: ['Users'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'The ID of the user to retrieve',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'User details retrieved successfully',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        ref: '#/components/schemas/UserResource'
                    )
                )
            ),
            new OA\Response(
                response: '404',
                description: 'User not found'
            ),
            new OA\Response(
                response: '403',
                description: 'Forbidden'
            )
        ]
    )]
    public function show(User $user)
    {
        Gate::authorize('manage-user', $user);
        return new UserResource($user);
    }

    #[OA\Put(
        path: '/api/users/{id}/role',
        operationId: 'updateUserRole',
        description: 'Update the role of a user by ID.',
        summary: 'Update user role',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'role', type: 'string', enum: ['user', 'admin', 'manager'], example: 'admin')
                    ]
                )
            )
        ),
        tags: ['Users'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'The ID of the user to update',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'User role updated successfully',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        ref: '#/components/schemas/UserResource'
                    )
                )
            ),
            new OA\Response(
                response: '404',
                description: 'User not found'
            ),
            new OA\Response(
                response: '403',
                description: 'Forbidden'
            )
        ]
    )]
    public function updateRole(Request $request, User $user)
    {
        $validatedData = $request->validate([
            'role' => 'required|in:user,admin,manager',
        ]);
        $user->update([
            'role' => $validatedData['role'],
        ]);

        return response()->json([
            'message' => 'User role updated successfully',
            'user' => new UserResource($user),
        ]);
    }

    #[OA\Put(
        path: '/api/users/{id}',
        operationId: 'updateUser',
        description: 'Update a user by ID.',
        summary: 'Update user details',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    ref: '#/components/schemas/UserRequest'
                )
            )
        ),
        tags: ['Users'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'The ID of the user to update',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'User updated successfully',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        ref: '#/components/schemas/UserResource'
                    )
                )
            ),
            new OA\Response(
                response: '404',
                description: 'User not found'
            ),
            new OA\Response(
                response: '403',
                description: 'Forbidden'
            )
        ]
    )]
    public function update(UpdateUserRequest $request, User $user)
    {
        Gate::authorize('manage-profile', $user);

        $validatedData = $request->validated();

        if (!auth()->user()->isAdmin()) {
            unset($validatedData['role']);
        }

        $user->update($validatedData);
        return new UserResource($user);
    }


    #[OA\Delete(
        path: '/api/users/{id}',
        operationId: 'deleteUser',
        description: 'Delete a user by ID.',
        summary: 'Delete a user',
        tags: ['Users'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'The ID of the user to delete',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: '204',
                description: 'User deleted successfully'
            ),
            new OA\Response(
                response: '404',
                description: 'User not found'
            ),
            new OA\Response(
                response: '403',
                description: 'Forbidden'
            )
        ]
    )]
    public function destroy(User $user)
    {
        $user = User::find($user->id);
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        Gate::authorize('manage-user', $user);
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'You cannot delete your own account.'], 403);
        }
        $user->delete();
        return response()->json(['message' => 'User deleted successfully.'], 204);
    }
}
