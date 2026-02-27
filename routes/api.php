<?php

use App\Http\Controllers\Api\AdminCompanyController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\LeaveRequestController;
use App\Http\Controllers\Api\ManagerLeaveRequestController;
use App\Http\Controllers\Api\ManagerUserController;
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
    Route::post('/me/change-password', [ProfileController::class, 'changePassword']);
    Route::post('/me/pin-code', [ProfileController::class, 'setupPinCode']);
    Route::patch('/me/pin-code', [ProfileController::class, 'changePinCode']);
    Route::get('/me/work-schedule', [ProfileController::class, 'getWorkSchedule']);
});

Route::middleware('auth:api')->prefix('/users')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::get('/{user}', [UserController::class, 'show']);
});

Route::middleware('auth:api')->group(function () {
    Route::get('/leave-requests', [LeaveRequestController::class, 'index']);
    Route::get('/leave-requests/{leaveRequest}', [LeaveRequestController::class, 'show']);
    Route::post('/leave-requests', [LeaveRequestController::class, 'store']);
});

Route::middleware('auth:api')->prefix('managers')->middleware('role:manager,admin')->group(function () {
    Route::get('/leave-requests', [ManagerLeaveRequestController::class, 'index']);
    Route::get('/leave-requests/pending', [ManagerLeaveRequestController::class, 'getPendingLeaveRequests']);
    Route::post('/leave-requests/{leaveRequest}/approve', [ManagerLeaveRequestController::class, 'approve']);
    Route::post('/leave-requests/{leaveRequest}/reject', [ManagerLeaveRequestController::class, 'reject']);

    Route::get('/users', [ManagerUserController::class, 'getCompanyUsers']);
    Route::get('/users/{user}', [ManagerUserController::class, 'getUser']);
    Route::get('/statistics', [ManagerUserController::class, 'getCompanyStatistics']);
    Route::get('/users/{user}/time-entries', [ManagerUserController::class, 'getUserTimeEntries']);
    Route::get('/users/{user}/time-summary', [ManagerUserController::class, 'getUserTimeSummary']);
    Route::get('/users/{user}/work-schedule', [ManagerUserController::class, 'getUserWorkSchedule']);
    Route::patch('/users/{user}/work-schedule', [ManagerUserController::class, 'updateUserWorkSchedule']);
});

Route::middleware('auth:api')->prefix('companies')->group(function () {
    Route::get('/{company}', [CompanyController::class, 'show']);
    Route::get('/name/{company}', [CompanyController::class, 'showByName']);
});

Route::middleware('role:admin')->prefix('admin')->group(function () {
    Route::post('/companies', [AdminCompanyController::class, 'store']);
    Route::patch('/companies/{company}', [AdminCompanyController::class, 'update']);
    Route::post('/companies/{company}/logo', [AdminCompanyController::class, 'updateLogo']);
    Route::delete('/companies/{company}', [AdminCompanyController::class, 'destroy']);
    Route::post('/companies/{company}/assign-manager', [AdminCompanyController::class, 'assignManager']);
    Route::post('/companies/{company}/add-employee', [AdminCompanyController::class, 'addEmployee']);
    Route::delete('/companies/{company}/remove-employee', [AdminCompanyController::class, 'removeEmployee']);

    Route::get('/users', [AdminUserController::class, 'getAllUsers']);
    Route::get('/users/{user}', [AdminUserController::class, 'getUser']);
    Route::get('/companies/{companyId}/users', [AdminUserController::class, 'getUsersByCompany']);
    Route::patch('/users/{user}', [AdminUserController::class, 'updateUser']);
    Route::patch('/users/{user}/role', [AdminUserController::class, 'updateUserRole']);
    Route::patch('/users/{user}/work-mode', [AdminUserController::class, 'updateWorkMode']);
    Route::post('/users/{user}/reset-password', [AdminUserController::class, 'resetPassword']);
    Route::delete('/users/{user}', [AdminUserController::class, 'deleteUser']);
});

Route::middleware('auth:api')->group(function () {
    Route::get('/time-entries', [TimeEntryController::class, 'index']);
    Route::get('/time-entries/active', [TimeEntryController::class, 'active']);
    Route::get('/time-entries/summary/me', [TimeEntryController::class, 'summary']);
    Route::post('/time-entries', [TimeEntryController::class, 'store']);
    Route::patch('/time-entries/active/stop', [TimeEntryController::class, 'stopActive']);
    Route::get('/time-entries/{timeEntry}', [TimeEntryController::class, 'show']);
    Route::delete('/time-entries/{timeEntry}', [TimeEntryController::class, 'destroy']);
    Route::get('/qr-code/daily', [TimeEntryController::class, 'getDailyQrCode']);
});

Route::middleware(['auth:api'])->group(function () {
    Route::apiResource('work-schedules', WorkScheduleController::class);
});

Route::middleware('auth:api')->prefix('audit-logs')->group(function () {
    Route::get('/', [AuditLogController::class, 'index']);
    Route::middleware('role:admin,manager')->group(function () {
        Route::get('/all', [AuditLogController::class, 'all']);
    });
});

Route::get('/login', function () {
    return response()->json(['message' => 'Please authenticate'], 401);
})->name('login');

Route::get('/test', function () {
    return response()->json(['message' => 'API is working']);
})->name('test');
