<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UploadAvatarRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    public function __construct(protected UserService $userService)
    {
    }

    public function me(): UserResource
    {
        return new UserResource(auth()->user());
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = auth()->user();
        $data = $this->userService->updateProfile($user, $request->validated());

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => new UserResource($data['user']),
        ]);
    }

    public function updateAvatar(UploadAvatarRequest $request): JsonResponse
    {
        $user = auth()->user();
        $data = $this->userService->updateAvatar($user, $request->validated('avatar'));

        return response()->json([
            'message' => 'Avatar updated successfully',
            'user' => new UserResource($data['user']),
        ]);
    }
}
