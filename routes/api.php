<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminTicketController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketMessageController;
use App\Http\Controllers\UserDashboardController;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']); // daftar
    Route::post('/login',    [AuthController::class, 'login']);    // login

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me',      [AuthController::class, 'me']);     // user login
        Route::post('/logout', [AuthController::class, 'logout']); // logout
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/tickets',             [TicketController::class, 'userIndex']); // tiket user
    Route::post('/tickets',            [TicketController::class, 'store']);     // buat tiket
    Route::get('/tickets/{id_ticket}', [TicketController::class, 'show']);      // detail tiket

    Route::get('/tickets/{id_ticket}/messages',  [TicketMessageController::class, 'index']); // chat
    Route::post('/tickets/{id_ticket}/messages', [TicketMessageController::class, 'store']); // kirim chat

    Route::get('/user/dashboard', [UserDashboardController::class, 'index']); // dashboard user
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar']); // avatar
});

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard/summary',           [AdminDashboardController::class, 'summary']); // statistik
    Route::get('/dashboard/recent-tickets',    [AdminDashboardController::class, 'recentTickets']); // 10 tiket terbaru
    Route::get('/dashboard/recent-activities', [AdminDashboardController::class, 'recentActivities']); // 10 aktivitas terbaru

    Route::get('/tickets',            [AdminTicketController::class, 'index']); // semua tiket (10/page max 5 page)
    Route::get('/tickets/{ticketId}', [AdminTicketController::class, 'show']);  // detail tiket admin

    Route::patch('/tickets/{ticketId}/open',       [AdminTicketController::class, 'open']);      // OPEN -> IN_REVIEW
    Route::patch('/tickets/{ticketId}/start-work', [AdminTicketController::class, 'startWork']); // IN_REVIEW -> IN_PROGRESS
    Route::patch('/tickets/{ticketId}/resolve',    [AdminTicketController::class, 'resolve']);   // IN_PROGRESS -> RESOLVED
});
