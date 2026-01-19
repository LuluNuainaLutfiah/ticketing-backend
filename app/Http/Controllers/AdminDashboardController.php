<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    /**
     * Mengambil ringkasan statistik asli dari database.
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

    /**
     * Tiket Terbaru: 10 data per halaman, Max 5 Halaman (50 Tiket).
     */
    public function recentTickets(Request $request)
    {
        $perPage = 10;
        $page = (int) $request->input('page', 1);
        if ($page > 5) $page = 5;

        // Ambil 50 tiket terbaru agar data lama otomatis tergeser
        $allRecent = Ticket::with(['creator', 'attachments'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

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
     * Aktivitas Terbaru: Dibatasi 10 data saja agar dashboard rapi.
     */
    public function recentActivities(Request $request)
    {
        $items = ActivityLog::with(['user', 'ticket'])
            ->orderByDesc('action_time')
            ->limit(10)
            ->get();

        return response()->json(['data' => $items]);
    }
}
