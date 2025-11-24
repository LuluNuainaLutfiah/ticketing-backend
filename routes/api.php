<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminDashboardController;

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

// ... route auth AdminDashboardController
Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/dashboard/summary',          [AdminDashboardController::class, 'summary']);
        Route::get('/dashboard/recent-tickets',   [AdminDashboardController::class, 'recentTickets']);
        Route::get('/dashboard/recent-activities',[AdminDashboardController::class, 'recentActivities']);
    });
