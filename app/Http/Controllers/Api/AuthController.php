<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserLogin;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;

class AuthController extends Controller
{
    public function __construct(protected AuthService $authService)
    {
    }

    public function register(UserRequest $request): JsonResponse
    {
        $user = $this->authService->register($request->validated());

        return response()->json($user, 201);
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

    public function refresh()
    {
        try {
            return response()->json($this->authService->refresh());
        } catch (TokenExpiredException $e) {
            return response()->json([
                'error' => 'Token has expired and can no longer be refreshed'
            ], 401);

        } catch (TokenInvalidException $e) {
            return response()->json([
                'error' => 'Token is invalid'
            ], 401);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Could not refresh token',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function logout(): JsonResponse
    {
        $response = $this->authService->logout();

        return response()->json($response);
    }
}
