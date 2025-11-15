<?php

namespace App\Services;

use App\Http\Resources\AuthResource;
use App\Http\Resources\UserResource;
use App\Repositories\UserRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthService
{
    public function __construct(protected UserRepository $repository)
    {
    }

    public function register(array $data): array
    {
        $user = $this->repository->register($data);

        return [
            'message' => 'User registered successfully',
            'user' => new AuthResource($user),
        ];
    }

    public function login(array $credentials): ?array
    {
        if (!$token = JWTAuth::attempt($credentials)) {
            return null;
        }

        $user = auth()->user();

        return [
            'access_token' => $token,
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => new AuthResource($user),
        ];
    }

    public function logout(): array
    {
        JWTAuth::invalidate(JWTAuth::getToken());
        return [
            'message' => 'Logged out successfully'
        ];
    }

    public function refresh(): JsonResponse|array
    {
        try {
            $token = JWTAuth::getToken();

            if (!$token) {
                return response()->json(['error' => 'Token not provided'], 401);
            }
            $newToken = JWTAuth::refresh($token);
            JWTAuth::setToken($newToken);
            $user = JWTAuth::toUser($newToken);

            return [
                'access_token' => $newToken,
                'expires_in' => auth()->factory()->getTTL() * 60,
                'user' => new UserResource($user),
            ];
        } catch (TokenExpiredException|TokenInvalidException|Exception $e) {
            throw $e;
        }
    }
}
