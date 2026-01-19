<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    /**
     * Mengambil ringkasan statistik untuk kotak informasi di dashboard.
     */
    public function summary(Request $request)
    {
        // Menghitung total berdasarkan status untuk ditampilkan di widget dashboard
        $data = [
            'tickets' => [
                'total'              => Ticket::count(),
                'open'               => Ticket::where('status', 'OPEN')->count(),
                'in_review'          => Ticket::where('status', 'IN_REVIEW')->count(),
                'in_progress'        => Ticket::where('status', 'IN_PROGRESS')->count(),
                'resolved'           => Ticket::where('status', 'RESOLVED')->count(),
                'high_priority_open' => Ticket::where('status', 'OPEN')->where('priority', 'HIGH')->count(),
                'today_created'      => Ticket::whereDate('created_at', today())->count(),
            ],
            'users' => [
                'total_users'  => User::where('role', 'user')->count(),
                'total_admins' => User::where('role', 'admin')->count(),
                'mahasiswa'    => User::where('role', 'user')->where('user_type', 'mahasiswa')->count(),
                'dosen'        => User::where('role', 'user')->where('user_type', 'dosen')->count(),
            ],
        ];

        return response()->json([
            'message' => 'Dashboard summary fetched',
            'data'    => $data,
        ]);
    }

    /**
     * Mengambil daftar tiket terbaru (Dibatasi 10 data saja untuk tampilan).
     * Data lama tetap tersimpan di database.
     */
    public function recentTickets(Request $request)
    {
        // Mengambil hanya 10 tiket terbaru dengan relasi creator dan lampiran (attachments)
        // Penambahan 'attachments' memastikan admin bisa melihat file lampiran utama
        $items = Ticket::with(['creator', 'attachments'])
            ->orderByDesc('created_at')
            ->limit(10) // PEMBATASAN 10 DATA UNTUK TAMPILAN
            ->get();

        return response()->json([
            'message' => 'Recent 10 tickets fetched',
            'data'    => [
                'data'  => $items,
                'total' => $items->count(),
            ],
        ]);
    }

    /**
     * Mengambil aktivitas sistem terbaru (Dibatasi 15 data saja untuk tampilan).
     * Data lama tetap tersimpan di database.
     */
    public function recentActivities(Request $request)
    {
        // Mengambil hanya 15 log aktivitas terbaru
        $items = ActivityLog::with(['user', 'ticket'])
            ->orderByDesc('action_time')
            ->limit(15) // PEMBATASAN 15 DATA UNTUK TAMPILAN (Foto Pertama)
            ->get();

        return response()->json([
            'message' => 'Recent 15 activities fetched',
            'data'    => [
                'data'  => $items,
                'total' => $items->count(),
            ],
        ]);
    }
}
