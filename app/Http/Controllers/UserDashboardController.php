<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\ActivityLog;

class UserDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $baseQuery = Ticket::where('created_by', $user->id);

        $totalTickets      = (clone $baseQuery)->count();
        $openTickets       = (clone $baseQuery)->where('status', 'OPEN')->count();
        $inReviewTickets   = (clone $baseQuery)->where('status', 'IN_REVIEW')->count();
        $inProgressTickets = (clone $baseQuery)->where('status', 'IN_PROGRESS')->count();
        $resolvedTickets   = (clone $baseQuery)->where('status', 'RESOLVED')->count();

        // 10 tiket terbaru milik user
        $recentTickets = (clone $baseQuery)
            ->with(['attachments'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // 10 aktivitas terbaru user (opsional, kalau kamu tampilkan di dashboard)
        $recentActivities = ActivityLog::with(['ticket'])
            ->where('performed_by', $user->id)
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
                    'avatar'    => $user->avatar ? asset('storage/' . $user->avatar) : null,
                ],
                'tickets_summary' => [
                    'total'       => $totalTickets,
                    'open'        => $openTickets,
                    'in_review'   => $inReviewTickets,
                    'in_progress' => $inProgressTickets,
                    'resolved'    => $resolvedTickets,
                ],
                'recent_tickets'     => $recentTickets,
                'recent_activities'  => $recentActivities,
            ],
        ]);
    }
}
