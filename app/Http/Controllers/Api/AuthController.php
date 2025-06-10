<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserLogin;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use OpenApi\Attributes as OA;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    #[OA\Post(
        path: '/api/auth/register',
        operationId: 'registerUser',
        description: 'Register a new user.',
        summary: 'Register a new user',
        requestBody: new OA\RequestBody(
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    ref: '#/components/schemas/UserRequest'
                )
            )
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: '201',
                description: 'User registered successfully',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        ref: '#/components/schemas/UserResource'
                    )
                )
            ),
            new OA\Response(
                response: '422',
                description: 'Validation error'
            )
        ]
    )]
    public function register(UserRequest $request)
    {
        $data = $request->validated();
        $user = User::create($data);
        return response()->json([
            'message' => 'User registered successfully',
            'user' => new UserResource($user),
        ], 201);
    }

    #[OA\Post(
        path: '/api/auth/login',
        operationId: 'loginUser',
        description: 'Login a user and return a JWT token.',
        summary: 'Login a user',
        requestBody: new OA\RequestBody(
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    ref: '#/components/schemas/UserLogin'
                )
            )
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: '200',
                description: 'User logged in successfully',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: 'access_token', type: 'string'),
                            new OA\Property(property: 'expires_in', type: 'integer'),
                            new OA\Property(property: 'user', ref: '#/components/schemas/UserResource'),
                        ],
                        type: 'object'
                    )
                )
            ),
            new OA\Response(
                response: '401',
                description: 'Invalid credentials'
            )
        ]

    )]
    public function login(UserLogin $request)
    {
        $credentials = $request->validated();

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['error' => 'Invalid credential'], 401);
        }

        $user = auth()->user();

        return response()->json([
            'access_token' => $token,
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => new UserResource($user),
        ]);
    }

    #[OA\Get(
        path: '/api/me',
        operationId: 'getCurrentUser',
        description: 'Get the currently authenticated user.',
        summary: 'Get current user',
        security: [['bearerAuth']],
        tags: ['Users'],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Current user retrieved successfully',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        ref: '#/components/schemas/UserResource'
                    )
                )
            ),
            new OA\Response(
                response: '401',
                description: 'Unauthorized'
            )
        ]
    )]
    public function me()
    {
        return new UserResource(auth()->user());
    }

    #[OA\Post(
        path: '/api/auth/logout',
        operationId: 'logoutUser',
        description: 'Logout the currently authenticated user.',
        summary: 'Logout user',
        security: [['bearerAuth']],
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: '200',
                description: 'User logged out successfully',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: 'message', type: 'string', example: 'User logged out successfully'),
                        ],
                        type: 'object'
                    )
                )
            ),
            new OA\Response(
                response: '500',
                description: 'Failed to logout'
            )
        ]
    )]
    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json(['message' => 'User logged out successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to logout'], 500);
        }
    }
}
