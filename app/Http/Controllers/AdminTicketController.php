<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    /**
     * Mengambil 10 Tiket per halaman (Halaman 1-5 / Max 50 data).
     * Jika ada tiket ke-51, tiket terlama hilang dari list dashboard.
     */
    public function recentTickets(Request $request)
    {
        $perPage = 10; // Mengatur tampilan 10 data per halaman
        $page = (int) $request->input('page', 1);

        // Membatasi maksimal akses hanya sampai halaman 5
        if ($page > 5) $page = 5;

        // Query mengambil 50 tiket terbaru agar data lama otomatis tergeser
        $allRecent = Ticket::with(['creator', 'attachments'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        // Memotong data manual: Halaman 1 ambil 10 data pertama, dsb
        $slicedItems = $allRecent->forPage($page, $perPage)->values();

        return response()->json([
            'data' => [
                'data'         => $slicedItems,
                'current_page' => $page,
                'last_page'    => 5,
                'total'        => 50
            ],
        ]);
    }

    /**
     * Mengambil 10 Aktivitas Terbaru saja.
     */
    public function recentActivities(Request $request)
    {
        // Dibatasi tepat 10 data agar seimbang dengan tabel tiket
        $items = ActivityLog::with(['user', 'ticket'])
            ->orderByDesc('action_time')
            ->limit(10)
            ->get();

        return response()->json(['data' => $items]);
    }

    /**
     * Mengambil ringkasan angka statistik untuk widget dashboard.
     */
    public function summary(Request $request)
    {
        $data = [
            'tickets' => [
                'total'       => Ticket::count(),
                'open'        => Ticket::where('status', 'OPEN')->count(),
                'in_review'   => Ticket::where('status', 'IN_REVIEW')->count(),
                'in_progress' => Ticket::where('status', 'IN_PROGRESS')->count(),
                'resolved'    => Ticket::where('status', 'RESOLVED')->count(),
            ],
            'users' => [
                'total_users' => User::where('role', 'user')->count(),
            ],
        ];
        return response()->json(['data' => $data]);
    }
}
