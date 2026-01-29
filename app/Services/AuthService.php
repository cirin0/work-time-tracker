<?php

namespace App\Services;

use App\Repositories\UserRepository;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AuthService
{
    public function __construct(protected UserRepository $repository)
    {
    }

    public function register(array $data): array
    {
        $user = $this->repository->create($data);

        return ['user' => $user];
    }

    public function login(array $credentials): array
    {
        if (!$token = JWTAuth::attempt($credentials)) {
            throw new UnauthorizedHttpException('', 'Invalid credentials');
        }

        return [
            'user' => auth()->user(),
            'token' => $token,
            'expires_in' => config('jwt.ttl', 60) * 60,
        ];
    }

    public function logout(): void
    {
        $token = JWTAuth::getToken();

        if (!$token) {
            throw new UnauthorizedHttpException('', 'Token not provided');
        }

        JWTAuth::setToken($token)->invalidate();
    }

    public function refresh(): array
    {
        $token = JWTAuth::getToken();

        if (!$token) {
            throw new UnauthorizedHttpException('', 'Token not provided');
        }

        $newToken = JWTAuth::setToken($token)->refresh();

        $user = JWTAuth::setToken($newToken)->toUser();

        return [
            'user' => $user,
            'token' => $newToken,
            'expires_in' => config('jwt.ttl', 60) * 60,
        ];
    }
}
