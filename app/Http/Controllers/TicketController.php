<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    // USER / ADMIN: buat tiket baru
    public function store(Request $request)
    {
        $user = $request->user(); // yg lagi login

        $data = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'category'    => ['required', 'string', 'max:100'],
            'priority'    => ['required', 'in:LOW,MEDIUM,HIGH'],
        ]);

        $nextId = (Ticket::max('id_ticket') ?? 0) + 1;
        $code   = 'TCK-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);

        $ticket = Ticket::create([
            'code_ticket' => $code,
            'title'       => $data['title'],
            'description' => $data['description'],
            'category'    => $data['category'],
            'priority'    => $data['priority'],
            'status'      => 'OPEN',
            'created_by'  => $user->id,
        ]);

        // LOG: tiket dibuat
        ActivityLog::create([
            'action'       => 'CREATE_TICKET',
            'details'      => 'Ticket dibuat oleh ' . $user->name,
            'action_time'  => now(),
            'performed_by' => $user->id,
            'id_ticket'    => $ticket->id_ticket,
        ]);

        return response()->json([
            'message' => 'Ticket berhasil dibuat',
            'data'    => $ticket,
        ], 201);
    }

    // USER: lihat tiket miliknya
    public function myTickets(Request $request)
    {
        $user = $request->user();

        $tickets = Ticket::where('created_by', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'message' => 'Daftar tiket milik user',
            'data'    => $tickets,
        ]);
    }

    // ADMIN: lihat semua tiket
    public function adminIndex()
    {
        $tickets = Ticket::with('creator')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'message' => 'Daftar semua tiket',
            'data'    => $tickets,
        ]);
    }

    // ADMIN: update status tiket
    public function updateStatus(Request $request, $id_ticket)
    {
        $user = $request->user();

        $data = $request->validate([
            'status' => ['required', 'in:OPEN,IN_PROGRESS,RESOLVED'],
        ]);

        $ticket = Ticket::findOrFail($id_ticket);

        $oldStatus = $ticket->status;

        $ticket->status = $data['status'];

        if ($data['status'] === 'RESOLVED') {
            $ticket->resolved_at = now();
        }

        $ticket->save();

        // LOG: status diubah
        ActivityLog::create([
            'action'       => 'UPDATE_STATUS',
            'details'      => "Status diubah dari {$oldStatus} ke {$ticket->status}",
            'action_time'  => now(),
            'performed_by' => $user->id,
            'id_ticket'    => $ticket->id_ticket,
        ]);

        return response()->json([
            'message' => 'Status tiket berhasil diupdate',
            'data'    => $ticket,
        ]);
    }
}
