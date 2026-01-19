<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    /**
     * Ringkasan Statistik Dashboard
     */
    public function summary(Request $request)
    {
        // Menghitung angka statistik untuk widget di atas dashboard
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
        return response()->json(['message' => 'Summary fetched', 'data' => $data]);
    }

    /**
     * Tiket Terbaru: 10 data per halaman, Max 50 data total (5 Halaman).
     * Jika ada data baru, data ke-51 akan tergeser keluar dari list.
     */
    public function recentTickets(Request $request)
    {
        $perPage = 10; // Menampilkan 10 data per halaman
        $page = $request->input('page', 1);

        // KUNCI: limit(50) membatasi list hanya untuk 50 data terbaru saja
        $baseQuery = Ticket::with(['creator', 'attachments'])
            ->orderByDesc('created_at')
            ->limit(50);

        // Mengambil data spesifik untuk halaman yang sedang dibuka
        $items = (clone $baseQuery)
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        return response()->json([
            'message' => 'Recent tickets fetched',
            'data' => [
                'data'         => $items,
                'current_page' => (int)$page,
                'last_page'    => 5, // Dibatasi maksimal 5 halaman saja (50 / 10)
                'total'        => 50
            ],
        ]);
    }

    /**
     * Aktivitas Terbaru: Maksimal 15 data.
     * Mengambil log sistem terbaru untuk Foto Pertama.
     */
    public function recentActivities(Request $request)
    {
        // Mengambil hanya 15 aktivitas terakhir
        $items = ActivityLog::with(['user', 'ticket'])
            ->orderByDesc('action_time')
            ->limit(15)
            ->get();

        return response()->json([
            'message' => 'Recent 15 activities fetched',
            'data'    => ['data' => $items]
        ]);
    }
}
