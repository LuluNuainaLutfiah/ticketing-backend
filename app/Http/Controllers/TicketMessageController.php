<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\Attachment;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 

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

        // Tandai pesan dari lawan bicara sebagai sudah dibaca
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

        // --- VALIDASI ATURAN STATUS ---
        if ($ticket->status === 'OPEN') {
            if (!$isAdmin) return response()->json(['message' => 'Menunggu admin membuka ticket'], 403);
        }

        if ($ticket->status === 'IN_REVIEW' && !$isAdmin) {
            return response()->json(['message' => 'Ticket sedang ditinjau admin'], 403);
        }

        if (!$isAdmin && $ticket->status !== 'IN_PROGRESS') {
            return response()->json(['message' => 'User hanya bisa chat saat IN_PROGRESS'], 403);
        }

        if ($ticket->status === 'RESOLVED' && !$isAdmin) {
            return response()->json(['message' => 'Ticket sudah selesai'], 403);
        }

        // --- VALIDASI INPUT ---
        $validated = $req->validate([
            'message_body' => 'nullable|string|max:2000',
            'files.*'      => 'nullable|file|mimes:jpg,jpeg,png,pdf,docx,xlsx,zip|max:10240', // 10MB & Secure Mimes
        ]);

        if (!$req->hasFile('files') && empty($validated['message_body'])) {
            return response()->json(['message' => 'Pesan atau file harus diisi'], 422);
        }

        // --- PROSES SIMPAN (TRANSACTION) ---
        return DB::transaction(function () use ($req, $ticket, $user, $isAdmin, $validated) {

            // 1. Update status otomatis jika Admin membalas di status OPEN
            if ($ticket->status === 'OPEN' && $isAdmin) {
                $ticket->update(['status' => 'IN_REVIEW']);

                ActivityLog::create([
                    'action'       => 'STATUS_CHANGED',
                    'details'      => 'Status otomatis berubah ke IN_REVIEW (Admin membalas)',
                    'action_time'  => now(),
                    'performed_by'=> $user->id,
                    'id_ticket'   => $ticket->id_ticket,
                ]);
            }

            // 2. Simpan pesan chat
            $msg = TicketMessage::create([
                'message_body' => $validated['message_body'] ?? null,
                'sent_at'      => now(),
                'read_status'  => false,
                'id_ticket'    => $ticket->id_ticket,
                'id_sender'    => $user->id,
            ]);

            // 3. Upload multi file jika ada
            if ($req->hasFile('files')) {
                foreach ($req->file('files') as $file) {
                    $path = $file->store("tickets/{$ticket->id_ticket}", 'public');

                    Attachment::create([
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

            // 4. LOG aktivitas kirim pesan
            ActivityLog::create([
                'action'       => 'SEND_MESSAGE',
                'details'      => 'Mengirim pesan chat ticket',
                'action_time'  => now(),
                'performed_by'=> $user->id,
                'id_ticket'   => $ticket->id_ticket,
            ]);

            $msg->load(['sender', 'attachments']);

            return response()->json([
                'message' => 'Message sent',
                'data'    => $msg,
            ], 201);
        });
    }

    // Delete chat dimatikan
    public function destroy()
    {
        return response()->json(['message' => 'Delete message tidak diizinkan'], 403);
    }
}
