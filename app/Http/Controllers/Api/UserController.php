<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    public function index()
    {
        return UserResource::collection(User::latest()->paginate(10));
    }

    public function show(User $user)
    {
        Gate::authorize('manage-user', $user);
        return new UserResource($user);
    }

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

    public function update(UpdateUserRequest $request, User $user)
    {
        Gate::authorize('manage-profile', $user);

        $validatedData = $request->validated();

        // If user is not admin, remove role from validated data to prevent role changes
        if (!auth()->user()->isAdmin()) {
            unset($validatedData['role']);
        }

        $user->update($validatedData);
        return new UserResource($user);
    }

    public function destroy($id)
    {
        $user = User::find($id);

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
