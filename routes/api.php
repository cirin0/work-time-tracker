<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('login', 'login');
    Route::post('logout', 'logout')->middleware('auth:api');
});

Route::get('me', [AuthController::class, 'me'])->middleware('auth:api');

Route::middleware('auth:api')->prefix('/users')->group(function () {
    Route::middleware('role:admin')->group(function () {
        Route::post('{user}/role', [UserController::class, 'updateRole']);
        Route::get('/', [UserController::class, 'index']);
        Route::get('/{user}', [UserController::class, 'show']);
    });
    Route::put('/{user}', [UserController::class, 'update']);
    Route::delete('/{user}', [UserController::class, 'destroy']);
});

Route::apiResource('departments', DepartmentController::class);


Route::get('/login', function () {
    return response()->json(['message' => 'Please authenticate'], 401);
})->name('login');
