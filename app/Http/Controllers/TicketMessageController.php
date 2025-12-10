<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\Attachment;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class TicketMessageController extends Controller
{
    // Cek akses: admin boleh semua, user hanya ticket miliknya
    protected function findTicketForUser(Request $req, $id): ?Ticket
    {
        $u = $req->user();

        $q = Ticket::where('id_ticket', $id);
        if ($u->role !== 'admin') {
            $q->where('created_by', $u->id);
        }

        return $q->first();
    }

    // List pesan + tandai sebagai read
    public function index(Request $req, $id_ticket)
    {
        $ticket = $this->findTicketForUser($req, $id_ticket);
        if (!$ticket) return response()->json(['message' => 'Ticket not found'], 404);

        $messages = TicketMessage::with(['sender', 'attachments'])
            ->where('id_ticket', $ticket->id_ticket)
            ->orderBy('sent_at')
            ->get();

        TicketMessage::where('id_ticket', $ticket->id_ticket)
            ->where('id_sender', '!=', $req->user()->id)
            ->update(['read_status' => true]);

        return response()->json([
            'message' => 'Messages fetched',
            'data'    => $messages,
        ]);
    }

    // Kirim chat + multi upload file
    public function store(Request $req, $id_ticket)
    {
        $ticket = $this->findTicketForUser($req, $id_ticket);
        if (!$ticket) return response()->json(['message' => 'Ticket not found'], 404);

        $user    = $req->user();
        $isAdmin = $user->role === 'admin';

        // RULE STATUS CHAT
        if ($ticket->status === 'OPEN') {
            if (!$isAdmin) {
                return response()->json(['message' => 'Menunggu admin membuka ticket'], 403);
            }
            // admin kirim pesan pertama → masuk IN_REVIEW
            $ticket->update(['status' => 'IN_REVIEW']);
        }

        if ($ticket->status === 'IN_REVIEW' && !$isAdmin) {
            return response()->json(['message' => 'Ticket sedang ditinjau admin'], 403);
        }

        if ($ticket->status === 'RESOLVED' && !$isAdmin) {
            return response()->json(['message' => 'Ticket sudah selesai'], 403);
        }

        // Validasi pesan + file
        $validated = $req->validate([
            'message_body' => 'nullable|string|max:2000',
            'files.*'      => 'nullable|file|max:10240', // 10MB
        ]);

        if (!$req->hasFile('files') && empty($validated['message_body'])) {
            return response()->json(['message' => 'Pesan atau file harus diisi'], 422);
        }

        // Simpan pesan chat
        $msg = TicketMessage::create([
            'message_body' => $validated['message_body'] ?? null,
            'sent_at'      => now(),
            'read_status'  => false,
            'id_ticket'    => $ticket->id_ticket,
            'id_sender'    => $user->id,
        ]);

        // Upload multi file
        $attachments = [];
        if ($req->hasFile('files')) {
            foreach ($req->file('files') as $file) {

                $path = $file->store("tickets/{$ticket->id_ticket}", 'public');

                $attachments[] = Attachment::create([
                    'file_name'   => $file->getClientOriginalName(),
                    'file_type'   => $file->getMimeType(),
                    'file_path'   => $path,
                    'uploaded_at' => now(),
                    'id_ticket'   => $ticket->id_ticket,
                    'uploaded_by' => $user->id,
                    'id_message'  => $msg->id_message,
                ]);
            }
        }

        // LOG aktivitas
        ActivityLog::create([
            'action'      => 'SEND_MESSAGE',
            'details'     => 'Mengirim pesan chat ticket',
            'action_time' => now(),
            'performed_by'=> $user->id,
            'id_ticket'   => $ticket->id_ticket,
        ]);

        $msg->load(['sender', 'attachments']);

        return response()->json([
            'message' => 'Message sent',
            'data'    => $msg,
        ], 201);
    }

    // delete chat dimatikan
    public function destroy()
    {
        return response()->json(['message' => 'Delete message tidak diizinkan'], 403);
    }
}
