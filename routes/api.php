<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketMessageController;
use App\Http\Controllers\UserDashboardController;

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

// ====== ROUTE USER & ADMIN UNTUK TICKET ======
// User login (role user/admin) boleh buat tiket & lihat tiket miliknya
Route::middleware('auth:sanctum')->group(function () {
      // Ticket basic
    Route::post('/tickets',      [TicketController::class, 'store']);      // buat tiket
    Route::get('/tickets/my',    [TicketController::class, 'myTickets']);  // tiket milik user

     // Pesan di dalam tiket
    Route::get('/tickets/{id_ticket}/messages', [TicketMessageController::class, 'index']);
    Route::post('/tickets/{id_ticket}/messages', [TicketMessageController::class, 'store']);
});

// Admin saja yang boleh lihat semua tiket + ubah status
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/tickets',                    [TicketController::class, 'adminIndex']);
    Route::patch('/tickets/{id_ticket}/status', [TicketController::class, 'updateStatus']);

    // dashboard admin yg tadi:
    Route::get('/dashboard/summary',           [AdminDashboardController::class, 'summary']);
    Route::get('/dashboard/recent-tickets',    [AdminDashboardController::class, 'recentTickets']);
    Route::get('/dashboard/recent-activities', [AdminDashboardController::class, 'recentActivities']);
});


// ===== ROUTE USER DASHBOARD =====
Route::middleware('auth:sanctum')->group(function () {
    // dashboard user (mahasiswa / dosen)
    Route::get('/user/dashboard', [UserDashboardController::class, 'index']);
});
