<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// semua route di file ini otomatis diprefix "api"
// jadi URL akhirnya: /api/auth/register, /api/auth/login

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me',     [AuthController::class, 'me']);
        Route::post('/logout',[AuthController::class, 'logout']);
    });
});
