<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

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

    // ✅ Tiket terbaru: 10 SAJA
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

    // ✅ Aktivitas terbaru: 10 SAJA
    public function recentActivities()
    {
        $activities = ActivityLog::with(['user','ticket'])
            ->orderByDesc('action_time')
            ->limit(10)
            ->get();

        return response()->json([
            'message' => 'Recent activities fetched',
            'data' => $activities
        ]);
    }
}
