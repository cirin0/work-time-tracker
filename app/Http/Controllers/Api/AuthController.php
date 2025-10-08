<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserLogin;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(protected AuthService $authService)
    {
    }

    public function register(UserRequest $request): JsonResponse
    {
        $user = $this->authService->register($request->validated());
        return response()->json([
            'message' => 'User registered successfully',
            'user' => new UserResource($user),
        ], 201);
    }


    public function login(UserLogin $request): JsonResponse
    {
        $response = $this->authService->login($request->validated());

        if (!$response) {
            return response()->json(['error' => 'Invalid credential'], 401);
        }

        return response()->json($response);
    }


    public function me(): UserResource
    {
        return new UserResource(auth()->user());
    }

    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return response()->json(['message' => 'User logged out successfully']);
    }
}
