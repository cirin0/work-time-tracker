<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('/auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:api')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);

    Route::post('user/{user}/role', [UserController::class, 'updateRole'])
        ->middleware('role:admin');

    Route::apiResource('users', UserController::class)
        ->only(['index', 'show'])
        ->middleware('role:admin');

    Route::put('users/{user}', [UserController::class, 'update']);
    Route::delete('users/{user}', [UserController::class, 'destroy']);
});
