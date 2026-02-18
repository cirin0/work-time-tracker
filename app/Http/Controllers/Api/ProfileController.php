<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\ChangePinCodeRequest;
use App\Http\Requests\SetupPinCodeRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UploadAvatarRequest;
use App\Http\Resources\ProfileResource;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    public function __construct(protected UserService $userService)
    {
    }

    public function me(): ProfileResource
    {
        $user = auth()->user()->load(['company', 'manager', 'workSchedule']);
        return new ProfileResource($user);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = auth()->user();
        $data = $this->userService->updateProfile($user, $request->validated());

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => new ProfileResource($data['user']),
        ]);
    }

    public function updateAvatar(UploadAvatarRequest $request): JsonResponse
    {
        $user = auth()->user();
        $data = $this->userService->updateAvatar($user, $request->validated('avatar'));

        return response()->json([
            'message' => 'Avatar updated successfully',
            'user' => new ProfileResource($data['user']),
        ]);
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = auth()->user();
        $data = $this->userService->changePassword($user, $request->validated());

        if (isset($data['message'])) {
            return response()->json(['message' => $data['message']], 403);
        }

        return response()->json([
            'message' => 'Password changed successfully',
        ]);
    }

    public function setupPinCode(SetupPinCodeRequest $request): JsonResponse
    {
        $user = auth()->user();
        $data = $this->userService->setupPinCode(
            $user,
            $request->validated('pin_code')
        );

        if (isset($data['message'])) {
            return response()->json(['message' => $data['message']], 400);
        }

        return response()->json([
            'message' => 'Pin code setup successfully',
        ]);
    }

    public function changePinCode(ChangePinCodeRequest $request): JsonResponse
    {
        $user = auth()->user();
        $data = $this->userService->changePinCode(
            $user,
            $request->validated('current_pin_code'),
            $request->validated('new_pin_code')
        );

        if (isset($data['message'])) {
            return response()->json(['message' => $data['message']], 400);
        }

        return response()->json([
            'message' => 'Pin code changed successfully',
        ]);
    }
}
