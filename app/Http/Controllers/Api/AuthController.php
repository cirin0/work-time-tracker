<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\ResendVerificationCodeRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\VerifyEmailRequest;
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
            'message' => $data['message'],
            'user' => new AuthResource($data['user']),
        ], 201);
    }

    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        $data = $this->authService->verifyEmail(
            $request->validated('email'),
            $request->validated('code')
        );

        if (isset($data['error'])) {
            return response()->json(['message' => $data['message']], 400);
        }

        return response()->json(['message' => $data['message']]);
    }

    public function resendVerificationCode(ResendVerificationCodeRequest $request): JsonResponse
    {
        $data = $this->authService->resendVerificationCode(
            $request->validated('email')
        );

        if (isset($data['error'])) {
            return response()->json(['message' => $data['message']], 400);
        }

        return response()->json(['message' => $data['message']]);
    }

    #[BodyParameter(name: 'email', description: 'The email of the user', example: 'manager@demotech.com')]
    #[BodyParameter(name: 'password', description: 'The password of the user', example: 'password')]
    public function login(LoginUserRequest $request): JsonResponse
    {
        $data = $this->authService->login($request->validated());

        if (isset($data['email_not_verified'])) {
            return response()->json([
                'message' => 'Please verify your email before logging in',
                'email_not_verified' => true,
                'email' => $data['email'],
            ], 403);
        }

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
            'message' => 'Logged out successfully',
        ]);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $data = $this->authService->forgotPassword(
            $request->validated('email')
        );

        if (isset($data['error'])) {
            return response()->json(['message' => $data['message']], 400);
        }

        return response()->json(['message' => $data['message']]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $data = $this->authService->resetPassword(
            $request->validated('email'),
            $request->validated('code'),
            $request->validated('password')
        );

        if (isset($data['error'])) {
            return response()->json(['message' => $data['message']], 400);
        }

        return response()->json(['message' => $data['message']]);
    }
}
