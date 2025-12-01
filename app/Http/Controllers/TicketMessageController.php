<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\ActivityLog;

class TicketMessageController extends Controller
{
     public function adminIndex()
    {
        $tickets = Ticket::orderByDesc('created_at')->get();

        return response()->json([
            'message' => 'Daftar semua tiket',
            'data'    => $tickets,
        ]);
    }
    public function index(Request $request, $id_ticket)
    {
        $user = $request->user();
        $ticket = Ticket::findOrFail($id_ticket);

        if ($user->role === 'user' && $ticket->created_by !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $messages = TicketMessage::with('sender')
            ->where('id_ticket', $id_ticket)
            ->orderBy('sent_at', 'asc')
            ->get();

        return response()->json([
            'message' => 'Pesan ditemukan',
            'data' => $messages
        ]);
    }

    public function store(Request $request, $id_ticket)
    {
        $user = $request->user();
        $ticket = Ticket::findOrFail($id_ticket);

        if ($user->role === 'user' && $ticket->created_by !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'message_body' => 'required|string'
        ]);

        $msg = TicketMessage::create([
            'message_body' => $data['message_body'],
            'sent_at'      => now(),
            'read_status'  => 0,
            'id_ticket'    => $id_ticket,
            'id_sender'    => $user->id,
        ]);

        ActivityLog::create([
            'action'       => 'ADD_MESSAGE',
            'details'      => 'Pesan baru ditambahkan oleh ' . $user->name,
            'action_time'  => now(),
            'performed_by' => $user->id,
            'id_ticket'    => $id_ticket,
        ]);

        return response()->json([
            'message' => 'Pesan terkirim',
            'data' => $msg
        ], 201);
    }
}
