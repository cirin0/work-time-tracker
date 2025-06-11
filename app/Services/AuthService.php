<?php

namespace App\Services;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Repositories\UserRepository;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthService
{
    public function __construct(protected UserRepository $repository)
    {
    }

    public function register(array $data): User
    {
        return $this->repository->register($data);
    }

    public function login(array $credentials): array|null
    {
        if (!$token = JWTAuth::attempt($credentials)) {
            return null;
        }

        $user = auth()->user();

        return [
            'access_token' => $token,
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => new UserResource($user),
        ];
    }

    public function logout(): bool
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

}
