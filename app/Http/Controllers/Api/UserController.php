<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function __construct(protected UserService $userService)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        $data = $this->userService->getAllPaginated();

        return UserResource::collection($data['users']);
    }

    public function show(User $user): UserResource
    {
        $data = $this->userService->getById($user);

        return new UserResource($data['user']);
    }
}
