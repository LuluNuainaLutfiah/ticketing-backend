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
        $inProgressTickets = Ticket::where('status', 'IN_PROGRESS')->count();
        $resolvedTickets   = Ticket::where('status', 'RESOLVED')->count();

        $highPriorityOpen  = Ticket::where('status', 'OPEN')
            ->where('priority', 'HIGH')
            ->count();

        $totalUsers        = User::where('role', 'user')->count();
        $totalAdmins       = User::where('role', 'admin')->count();

        $todayTickets      = Ticket::whereDate('created_at', today())->count();

        return response()->json([
            'message' => 'Dashboard summary fetched',
            'data' => [
                'tickets' => [
                    'total'        => $totalTickets,
                    'open'         => $openTickets,
                    'in_progress'  => $inProgressTickets,
                    'resolved'     => $resolvedTickets,
                    'high_priority_open' => $highPriorityOpen,
                    'today_created'      => $todayTickets,
                ],
                'users' => [
                    'total_users'  => $totalUsers,
                    'total_admins' => $totalAdmins,
                ],
            ],
        ]);
    }

    // Tiket terbaru untuk list di dashboard
    public function recentTickets(Request $request)
    {
        $tickets = Ticket::with('creator')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return response()->json([
            'message' => 'Recent tickets fetched',
            'data' => $tickets,
        ]);
    }

    // Aktivitas terbaru (activity_log)
    public function recentActivities(Request $request)
    {
        $logs = ActivityLog::with(['user', 'ticket'])
            ->orderByDesc('action_time')
            ->limit(10)
            ->get();

        return response()->json([
            'message' => 'Recent activities fetched',
            'data' => $logs,
        ]);
    }
}

