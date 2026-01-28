<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;

class AdminDashboardController extends Controller
{ 
    public function summary()
    {
        return response()->json([
            'message' => 'Dashboard summary fetched',
            'data' => [
                'tickets' => [
                    'total'       => Ticket::count(),
                    'open'        => Ticket::where('status', 'OPEN')->count(),
                    'in_review'   => Ticket::where('status', 'IN_REVIEW')->count(),
                    'in_progress' => Ticket::where('status', 'IN_PROGRESS')->count(),
                    'resolved'    => Ticket::where('status', 'RESOLVED')->count(),
                ],
            ],
        ]);
    }

    // ✅ Tiket terbaru: 10 SAJA (tetap)
    public function recentTickets()
    {
        $tickets = Ticket::with(['creator','attachments'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return response()->json([
            'message' => 'Recent tickets fetched',
            'data' => $tickets
        ]);
    }

    /**
     * ✅ Aktivitas: paginate 10 per page, MAX 5 page (50 aktivitas terbaru)
     * GET /api/admin/dashboard/recent-activities?page=1&per_page=10
     */
    public function recentActivities(Request $request)
    {
        $perPage = (int) $request->query('per_page', 10);
        if ($perPage <= 0) $perPage = 10;

        // 🔒 Batasi maksimal 5 page
        $page = (int) $request->query('page', 1);
        if ($page < 1) $page = 1;
        if ($page > 5) $page = 5;

        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        $activities = ActivityLog::with(['user', 'ticket'])
            ->orderByDesc('action_time')
            ->paginate($perPage);

        return response()->json([
            'message' => 'Recent activities fetched',
            'data'    => $activities
        ]);
    }
}
