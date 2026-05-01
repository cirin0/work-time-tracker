<?php


use App\Http\Controllers\Api\v2\TestController;

Route::get('/test', [TestController::class, 'index']);
