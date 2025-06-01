<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;


//Route::get('/user', function (Request $request) {
//    return $request->user();
//})->middleware('auth:sanctum');

Route::apiResource('/posts', PostController::class);

//Route::post('/auth/register', [AuthController::class, 'register']);
//Route::post('/auth/login', [AuthController::class, 'login']);
//Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
//Route::post('/auth/delete-all-tokens', [AuthController::class, 'deleteAllTokens'])->middleware('auth:sanctum');

Route::group([

    'middleware' => 'api',
    'prefix' => 'auth'

], function ($router) {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('me', [AuthController::class, 'me']);
});
