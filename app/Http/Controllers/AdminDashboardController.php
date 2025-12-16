<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    // Ringkasan angka di dashboard admin
    public function summary(Request $request)
    {
        $totalTickets      = Ticket::count();
        $openTickets       = Ticket::where('status', 'OPEN')->count();
        $inReviewTickets   = Ticket::where('status', 'IN_REVIEW')->count();
        $inProgressTickets = Ticket::where('status', 'IN_PROGRESS')->count();
        $resolvedTickets   = Ticket::where('status', 'RESOLVED')->count();


        $highPriorityOpen  = Ticket::where('status', 'OPEN')
            ->where('priority', 'HIGH')
            ->count();

        $totalUsers        = User::where('role', 'user')->count();
        $totalAdmins       = User::where('role', 'admin')->count();
        $studentCount      = User::where('role', 'user')
            ->where('user_type', 'mahasiswa')
            ->count();

        $lecturerCount     = User::where('role', 'user')
            ->where('user_type', 'dosen')
            ->count();



        $todayTickets      = Ticket::whereDate('created_at', today())->count();

        return response()->json([
            'message' => 'Dashboard summary fetched',
            'data' => [
                'tickets' => [
                    'total'        => $totalTickets,
                    'open'         => $openTickets,
                    'in_review'    => $inReviewTickets,
                    'in_progress'  => $inProgressTickets,
                    'resolved'     => $resolvedTickets,
                    'high_priority_open' => $highPriorityOpen,
                    'today_created'      => $todayTickets,
                ],
                'users' => [
                    'total_users'   => $totalUsers,
                    'total_admins'  => $totalAdmins,
                    'mahasiswa'     => $studentCount,
                    'dosen'         => $lecturerCount,
                ],

            ],
        ]);
    }

    // Tiket terbaru untuk list di dashboard
    public function recentTickets(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);
        $perPage = max(1, min($perPage, 50)); // aman

        $page = (int) $request->get('page', 1);
        $page = max(1, $page);

    // Ambil hanya 50 ticket terbaru
        $baseQuery = Ticket::with('creator')
            ->orderByDesc('created_at')
            ->limit(50);

    // Total ticket yang ditampilkan UI (maks 50)
        $total = (clone $baseQuery)->count();

        $lastPage = max(1, (int) ceil($total / $perPage));

        if ($page > $lastPage) $page = $lastPage;

        $items = (clone $baseQuery)
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return response()->json([
            'message' => 'Recent tickets fetched',
            'data' => [
                'current_page' => $page,
                'data'         => $items,
                'last_page'    => $lastPage, // max 5 (jika perPage=10)
                'per_page'     => $perPage,
                'total'        => $total,    // max 50
                ],
         ]);
    }

    // Aktivitas terbaru (activity_log)
    public function recentActivities(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);
        $perPage = max(1, min($perPage, 50)); // 1..50

        $page = (int) $request->get('page', 1);
        $page = max(1, $page);

    // Base query: hanya 50 activity terbaru
        $baseQuery = ActivityLog::with(['user', 'ticket'])
            ->orderByDesc('action_time')
            ->limit(50);

    // Total REAL yang ditampilkan UI (maks 50, bisa < 50)
        $total = (clone $baseQuery)->count();

        $lastPage = max(1, (int) ceil($total / $perPage));

        if ($page > $lastPage) $page = $lastPage;

        $items = (clone $baseQuery)
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return response()->json([
            'message' => 'Recent activities fetched',
            'data' => [
                'current_page' => $page,
                'data'         => $items,
                'last_page'    => $lastPage, // max 5 kalau perPage=10
                'per_page'     => $perPage,
                'total'        => $total,    // max 50
                ],
         ]);
    }
}
