<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request; 
use App\Models\Ticket;
use App\Models\ActivityLog;

class UserDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user(); // user yang lagi login

        // semua tiket yang dibuat user ini
        $baseQuery = Ticket::where('created_by', $user->id);

        $totalTickets     = (clone $baseQuery)->count();
        $openTickets      = (clone $baseQuery)->where('status', 'OPEN')->count();
        $inProgressTickets= (clone $baseQuery)->where('status', 'IN_PROGRESS')->count();
        $resolvedTickets  = (clone $baseQuery)->where('status', 'RESOLVED')->count();

        // 5 tiket terbaru milik user
        $recentTickets = (clone $baseQuery)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // 10 aktivitas terbaru yang dilakukan user ini (opsional, bisa buat tab "Aktivitas Saya")
        $recentActivities = ActivityLog::where('performed_by', $user->id)
            ->orderByDesc('action_time')
            ->limit(10)
            ->get();

        return response()->json([
            'message' => 'Dashboard user fetched',
            'data' => [
                'user' => [
                    'id'        => $user->id,
                    'name'      => $user->name,
                    'email'     => $user->email,
                    'role'      => $user->role,
                    'user_type' => $user->user_type,
                    'npm'       => $user->npm,
                    'nik'       => $user->nik,
                    'phone'     => $user->phone,
                ],
                'tickets_summary' => [
                    'total'        => $totalTickets,
                    'open'         => $openTickets,
                    'in_progress'  => $inProgressTickets,
                    'resolved'     => $resolvedTickets,
                ],
                'recent_tickets'    => $recentTickets,
                'recent_activities' => $recentActivities,
            ],
        ]);
    }
}
