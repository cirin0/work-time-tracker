<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\LeaveRequestController;
use App\Http\Controllers\Api\Manager\LeaveRequestController as ManagerLeaveRequestController;
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

Route::middleware('auth:api')->group(function () {
    Route::get('/leave-requests', [LeaveRequestController::class, 'index']);
    Route::post('/leave-requests', [LeaveRequestController::class, 'store']);

    Route::prefix('manager')->middleware('role:manager')->group(function () {
        Route::get('/leave-requests', [ManagerLeaveRequestController::class, 'index']);
        Route::post('/leave-requests/{leaveRequest}/approve', [ManagerLeaveRequestController::class, 'approve']);
        Route::post('/leave-requests/{leaveRequest}/reject', [ManagerLeaveRequestController::class, 'reject']);
    });

});

Route::prefix('companies')->group(function () {
    Route::get('/{company}', [CompanyController::class, 'showById']);
    Route::get('/name/{company}', [CompanyController::class, 'showByName']);
    Route::post('/', [CompanyController::class, 'store']);
    Route::put('/{company}', [CompanyController::class, 'update']);
    Route::delete('/{company}', [CompanyController::class, 'destroy']);
});


Route::get('/login', function () {
    return response()->json(['message' => 'Please authenticate'], 401);
})->name('login');
