<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminTicketController;
use App\Http\Controllers\ProfileController;
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
     // ==== TICKET USER ====
    Route::get('/tickets', [TicketController::class, 'userIndex']);               // list tiket milik user
    Route::post('/tickets', [TicketController::class, 'store']);                  // buat tiket baru
    Route::get('/tickets/{id_ticket}', [TicketController::class, 'show']);        // detail tiket

     // Pesan di dalam tiket
    Route::get('/tickets/{id_ticket}/messages', [TicketMessageController::class, 'index']);
    Route::post('/tickets/{id_ticket}/messages', [TicketMessageController::class, 'store']);
    //Route::delete('/tickets/{id_ticket}/messages/{id_message}', [TicketMessageController::class, 'destroy']);
});

// Admin saja yang boleh lihat semua tiket + ubah status
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // ===== LIST & UPDATE TICKET =====
    Route::get('/tickets',                    [TicketController::class, 'adminIndex']);
    Route::patch('/tickets/{id_ticket}/status', [TicketController::class, 'updateStatus']);

    // ===== FLOW STATUS BARU ====
    Route::post('/tickets/{ticketId}/open',       [AdminTicketController::class, 'open']);
    Route::post('/tickets/{ticketId}/start-work', [AdminTicketController::class, 'startWork']);
    Route::post('/tickets/{ticketId}/resolve',    [AdminTicketController::class, 'resolve']);

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

// ===== ROUTE PROFILE =====
Route::middleware('auth:sanctum')->group(function () {
    // Route::get('/profile', [ProfileController::class, 'show']);
    // Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar']);
});


