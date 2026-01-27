<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Resources\AuthResource;
use App\Services\AuthService;
use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(protected AuthService $authService)
    {
    }

    public function register(StoreUserRequest $request): JsonResponse
    {
        $data = $this->authService->register($request->validated());

        return response()->json([
            'message' => 'User registered successfully',
            'user' => new AuthResource($data['user']),
        ], 201);
    }

    #[BodyParameter(name: 'email', description: 'The email of the user', example: 'manager@demotech.com')]
    #[BodyParameter(name: 'password', description: 'The password of the user', example: 'password')]
    public function login(LoginUserRequest $request): JsonResponse
    {
        $data = $this->authService->login($request->validated());

        return response()->json([
            'access_token' => $data['token'],
            'expires_in' => $data['expires_in'],
            'user' => new AuthResource($data['user']),
        ]);

    }


    public function refresh(): JsonResponse
    {
        $data = $this->authService->refresh();

        return response()->json([
            'message' => 'Token refreshed successfully',
            'access_token' => $data['token'],
            'expires_in' => $data['expires_in'],
            'user' => new AuthResource($data['user']),
        ]);

    }

    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
}
