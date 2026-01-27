<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\LeaveRequestController;
use App\Http\Controllers\Api\ManagerCompanyController;
use App\Http\Controllers\Api\ManagerLeaveRequestController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\TimeEntryController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WorkScheduleController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('login', 'login');
    Route::post('logout', 'logout')->middleware('auth:api');
    Route::post('refresh', 'refresh');
});

Route::middleware('auth:api')->group(function () {
    Route::get('/messages/{receiverId}', [MessageController::class, 'index']);
    Route::post('/messages', [MessageController::class, 'store'])->middleware('throttle:60,1');
});

Route::middleware('auth:api')->group(function () {
    Route::get('/me', [ProfileController::class, 'me']);
    Route::patch('/me', [ProfileController::class, 'updateProfile']);
    Route::post('/me/avatar', [ProfileController::class, 'updateAvatar']);
});

Route::middleware('auth:api')->prefix('/users')->group(function () {
    Route::middleware('role:admin')->group(function () {
        // uncomment for admin to view all users
        //   Route::get('/', [UserController::class, 'index']);
        //   Route::get('/{user}', [UserController::class, 'show']);
        Route::patch('/{user}', [UserController::class, 'update']);
        Route::post('{user}/role', [UserController::class, 'updateRole']);
    });
    Route::post('/{user}/avatar', [UserController::class, 'uploadAvatar']);
    Route::delete('/{user}', [UserController::class, 'destroy']);
});

// for testing
Route::prefix('/users')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::get('/{user}', [UserController::class, 'show']);
});

// TODO: fix data responses to be consistent
Route::middleware('auth:api')->group(function () {
    Route::get('/leave-requests', [LeaveRequestController::class, 'index']);
    Route::get('/leave-requests/{leaveRequest}', [LeaveRequestController::class, 'showById']);
    Route::post('/leave-requests', [LeaveRequestController::class, 'store']);

    Route::prefix('manager')->middleware('role:manager')->group(function () {
        Route::get('/leave-requests', [ManagerLeaveRequestController::class, 'index']);
        Route::post('/leave-requests/{leaveRequest}/approve', [ManagerLeaveRequestController::class, 'approve']);
        Route::post('/leave-requests/{leaveRequest}/reject', [ManagerLeaveRequestController::class, 'reject']);

        Route::post('/companies/{company}/add-employee', [ManagerCompanyController::class, 'addEmployeeToCompany']);
        Route::post('/companies/{company}/remove-employee', [ManagerCompanyController::class, 'deleteEmployeeFromCompany']);
        Route::post('/companies/{company}/remove-employee/{employee_id}', [ManagerCompanyController::class, 'deleteEmployeeFromCompanyById']);
    });

});

Route::middleware('auth:api')->prefix('companies')->group(function () {
    // TODO: for testing, in future only one company
    Route::get('/', [CompanyController::class, 'index']);
    Route::get('/{company}', [CompanyController::class, 'showById']);
    Route::get('/name/{company}', [CompanyController::class, 'showByName']);
    Route::post('/', [CompanyController::class, 'store']);
    // change to patch in future
    Route::put('/{company}', [CompanyController::class, 'update']);
    Route::delete('/{company}', [CompanyController::class, 'destroy']);
});

Route::middleware('auth:api')->group(function () {
    Route::get('/time-entries', [TimeEntryController::class, 'index']);
    Route::get('/time-entries/active', [TimeEntryController::class, 'active']);
    Route::get('/time-entries/summary/me', [TimeEntryController::class, 'summary']);
    Route::post('/time-entries', [TimeEntryController::class, 'store']);
    Route::get('/time-entries/{timeEntry}', [TimeEntryController::class, 'show']);
    Route::put('/time-entries/{timeEntry}', [TimeEntryController::class, 'update']);
    Route::delete('/time-entries/{timeEntry}', [TimeEntryController::class, 'destroy']);
});

Route::middleware(['auth:api'])->group(function () {
    Route::apiResource('work-schedules', WorkScheduleController::class);
    Route::get('users/{user}/work-schedule', [UserController::class, 'getWorkSchedule']);
    Route::put('users/{user}/work-schedule', [UserController::class, 'updateWorkSchedule']);
});

Route::get('/login', function () {
    return response()->json(['message' => 'Please authenticate'], 401);
})->name('login');

Route::get('/test', function () {
    return response()->json(['message' => 'API is working']);
})->name('test');
