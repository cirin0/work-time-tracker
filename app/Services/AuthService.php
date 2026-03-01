<?php

namespace App\Services;

use App\Models\EmailVerificationCode;
use App\Notifications\VerificationCodeNotification;
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

        $code = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        $verificationCode = EmailVerificationCode::query()->create([
            'user_id' => $user->id,
            'code' => $code,
            'type' => 'registration',
            'expires_at' => now()->addMinutes(15),
        ]);

        if (config('app.env') === 'local') {
            $verificationCode->update(['verified_at' => now()]);
            $user->markEmailAsVerified();
            return ['user' => $user, 'message' => 'Auto-verified email in local environment'];
        }
        $user->notify(new VerificationCodeNotification($code, 'підтвердження пошти'));

        return ['user' => $user, 'message' => 'Verification code sent to your email'];
    }

    public function login(array $credentials): array
    {
        if (!$token = JWTAuth::attempt($credentials)) {
            throw new UnauthorizedHttpException('', 'Invalid credentials');
        }

        $user = auth()->user();

        if (!$user->hasVerifiedEmail()) {
            JWTAuth::setToken($token)->invalidate();
            throw new UnauthorizedHttpException('', 'Please verify your email before logging in');
        }

        return [
            'user' => $user,
            'token' => $token,
            'expires_in' => config('jwt.ttl', 60) * 60,
        ];
    }

    public function verifyEmail(int $userId, string $code): array
    {
        $verificationCode = EmailVerificationCode::query()
            ->where('user_id', $userId)
            ->where('code', $code)
            ->where('type', 'registration')
            ->whereNull('verified_at')
            ->first();

        if (!$verificationCode) {
            return ['error' => true, 'message' => 'Invalid verification code'];
        }

        if ($verificationCode->isExpired()) {
            return ['error' => true, 'message' => 'Verification code has expired'];
        }

        $verificationCode->update(['verified_at' => now()]);

        $user = $verificationCode->user;
        $user->markEmailAsVerified();

        return ['message' => 'Email verified successfully'];
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
