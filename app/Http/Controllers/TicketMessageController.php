<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class TicketMessageController extends Controller
{
    /** ====== CEK AKSES TIKET: admin boleh semua, user hanya tiket miliknya ====== */
    protected function findTicketForUser(Request $request, $id_ticket): ?Ticket
    {
        $user = $request->user();

        $query = Ticket::where('id_ticket', $id_ticket);

        if ($user->role !== 'admin') {
            $query->where('created_by', $user->id);
        }

        return $query->first();
    }

    /** ====== LIST PESAN DALAM SATU TIKET ====== */
    public function index(Request $request, $id_ticket)
    {
        $ticket = $this->findTicketForUser($request, $id_ticket);

        if (!$ticket) {
            return response()->json([
                'message' => 'Ticket not found or not allowed',
            ], 404);
        }

        $messages = TicketMessage::with('sender')
            ->where('id_ticket', $ticket->id_ticket)
            ->orderBy('sent_at', 'asc')
            ->get();

        // opsional: tandai pesan dari lawan bicara sebagai sudah dibaca
        $user = $request->user();
        TicketMessage::where('id_ticket', $ticket->id_ticket)
            ->where('id_sender', '!=', $user->id)
            ->where('read_status', false)
            ->update(['read_status' => true]);

        return response()->json([
            'message' => 'Messages fetched',
            'data'    => $messages,
        ]);
    }

    /** ====== KIRIM PESAN BARU DI TIKET ====== */
    public function store(Request $request, $id_ticket)
    {
        $ticket = $this->findTicketForUser($request, $id_ticket);

        if (!$ticket) {
            return response()->json([
                'message' => 'Ticket not found or not allowed',
            ], 404);
        }

        $data = $request->validate([
            'message_body' => ['required', 'string'],
        ]);

        $user = $request->user();

        $message = TicketMessage::create([
            'message_body' => $data['message_body'],
            'sent_at'      => now(),
            'read_status'  => false,
            'id_ticket'    => $ticket->id_ticket,
            'id_sender'    => $user->id,
        ]);

        // catat ke activity_log
        ActivityLog::create([
            'action'       => 'SEND_MESSAGE',
            'details'      => 'User mengirim pesan di tiket',
            'action_time'  => now(),
            'performed_by' => $user->id,
            'id_ticket'    => $ticket->id_ticket,
        ]);

        // load relasi sender biar langsung keliatan di frontend
        $message->load('sender');

        return response()->json([
            'message' => 'Message sent',
            'data'    => $message,
        ], 201);
    }

    /** ====== HAPUS PESAN (opsional) – hanya pengirim atau admin ====== */
    public function destroy(Request $request, $id_ticket, $id_message)
    {
        $ticket = $this->findTicketForUser($request, $id_ticket);

        if (!$ticket) {
            return response()->json([
                'message' => 'Ticket not found or not allowed',
            ], 404);
        }

        $user = $request->user();

        $message = TicketMessage::where('id_message', $id_message)
            ->where('id_ticket', $ticket->id_ticket)
            ->first();

        if (!$message) {
            return response()->json([
                'message' => 'Message not found',
            ], 404);
        }

        if ($user->role !== 'admin' && $message->id_sender !== $user->id) {
            return response()->json([
                'message' => 'You are not allowed to delete this message',
            ], 403);
        }

        $message->delete();

        ActivityLog::create([
            'action'       => 'DELETE_MESSAGE',
            'details'      => 'Pesan dihapus',
            'action_time'  => now(),
            'performed_by' => $user->id,
            'id_ticket'    => $ticket->id_ticket,
        ]);

        return response()->json([
            'message' => 'Message deleted',
        ]);
    }
}
