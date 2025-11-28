<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class TicketMessageController extends Controller
{
    // GET: semua pesan dalam satu tiket
    public function index(Request $request, $id_ticket)
    {
        $user   = $request->user();
        $ticket = Ticket::findOrFail($id_ticket);

        // user hanya boleh lihat tiket miliknya
        if ($user->role === 'user' && $ticket->created_by !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $messages = TicketMessage::with('sender')
            ->where('id_ticket', $ticket->id_ticket)
            ->orderBy('sent_at', 'asc')
            ->get();

        return response()->json([
            'message' => 'Daftar pesan',
            'data' => $messages,
        ]);
    }

    // POST: Kirim pesan
    public function store(Request $request, $id_ticket)
    {
        $user   = $request->user();
        $ticket = Ticket::findOrFail($id_ticket);

        // user hanya boleh chat di tiketnya sendiri
        if ($user->role === 'user' && $ticket->created_by !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'message_body' => ['required', 'string'],
        ]);

        $msg = TicketMessage::create([
            'message_body' => $data['message_body'],
            'sent_at'      => now(),
            'read_status'  => 0,       // sesuai DB kamu (0 unread)
            'id_ticket'    => $ticket->id_ticket,
            'id_sender'    => $user->id,
        ]);

        // Tambahkan ke activity_log
        ActivityLog::create([
            'action'       => 'ADD_MESSAGE',
            'details'      => 'Pesan baru oleh ' . $user->name,
            'action_time'  => now(),
            'performed_by' => $user->id,
            'id_ticket'    => $ticket->id_ticket,
        ]);

        return response()->json([
            'message' => 'Pesan terkirim',
            'data'    => $msg,
        ], 201);
    }
}
