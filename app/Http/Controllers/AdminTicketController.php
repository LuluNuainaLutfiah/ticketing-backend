<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class AdminTicketController extends Controller
{
    protected function ensureAdmin($user): void
    {
        // sesuaikan dengan kolom role di tabel users
        if ($user->role !== 'admin') {
            abort(403, 'Hanya admin yang boleh melakukan aksi ini.');
        }
    }

    // ========== OPEN -> IN_REVIEW ==========
    // dipakai saat admin klik tombol "Open" di popup
    public function open(Request $request, $ticketId)
    {
        $admin = $request->user();
        $this->ensureAdmin($admin);

        $ticket = Ticket::findOrFail($ticketId);

        if ($ticket->status !== 'OPEN') {
            return response()->json([
                'message' => 'Ticket bukan dalam status OPEN.',
            ], 422);
        }

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $text = $validated['message']
            ?? 'Halo, tiket kamu sudah kami buka dan sedang ditinjau oleh admin. Kami akan menghubungi kamu kembali jika ada update.';

        // 1. update status
        $ticket->status     = 'IN_REVIEW';
        $ticket->updated_at = now();
        $ticket->save();

        // 2. kirim chat dari admin ke user
        $msg = TicketMessage::create([
            'message_body' => $text,
            'sent_at'      => now(),
            'read_status'  => false,
            'id_ticket'    => $ticket->id_ticket,
            'id_sender'    => $admin->id,
        ]);

        // 3. activity log
        ActivityLog::create([
            'action'       => 'OPEN_TO_IN_REVIEW',
            'details'      => 'Admin membuka tiket dan menandai sebagai IN_REVIEW.',
            'action_time'  => now(),
            'performed_by' => $admin->id,
            'id_ticket'    => $ticket->id_ticket,
        ]);

        return response()->json([
            'message' => 'Ticket masuk tahap IN_REVIEW.',
            'ticket'  => $ticket,
            'chat'    => $msg,
        ]);
    }

    // ========== IN_REVIEW -> IN_PROGRESS ==========
    // dipakai saat admin klik "Mulai kerjakan"
    public function startWork(Request $request, $ticketId)
    {
        $admin = $request->user();
        $this->ensureAdmin($admin);

        $ticket = Ticket::findOrFail($ticketId);

        if ($ticket->status !== 'IN_REVIEW') {
            return response()->json([
                'message' => 'Ticket bukan dalam status IN_REVIEW.',
            ], 422);
        }

        $ticket->status     = 'IN_PROGRESS';
        $ticket->updated_at = now();
        $ticket->save();

        $msg = TicketMessage::create([
            'message_body' => 'Ticket sedang dikerjakan oleh admin.',
            'sent_at'      => now(),
            'read_status'  => false,
            'id_ticket'    => $ticket->id_ticket,
            'id_sender'    => $admin->id,
        ]);

        ActivityLog::create([
            'action'       => 'IN_REVIEW_TO_IN_PROGRESS',
            'details'      => 'Admin memulai pengerjaan ticket.',
            'action_time'  => now(),
            'performed_by' => $admin->id,
            'id_ticket'    => $ticket->id_ticket,
        ]);

        return response()->json([
            'message' => 'Ticket masuk tahap IN_PROGRESS.',
            'ticket'  => $ticket,
            'chat'    => $msg,
        ]);
    }

    // ========== RESOLVED ==========
    // untuk menyelesaikan tiket secara manual
    public function resolve(Request $request, $ticketId)
    {
        $admin = $request->user();
        $this->ensureAdmin($admin);

        $ticket = Ticket::findOrFail($ticketId);

        if ($ticket->status === 'RESOLVED') {
            return response()->json([
                'message' => 'Ticket sudah dalam status RESOLVED.',
            ], 422);
        }

        $ticket->status      = 'RESOLVED';
        $ticket->resolved_at = now();
        $ticket->updated_at  = now();
        $ticket->save();

        $msg = TicketMessage::create([
            'message_body' => 'Ticket ditandai selesai oleh admin.',
            'sent_at'      => now(),
            'read_status'  => false,
            'id_ticket'    => $ticket->id_ticket,
            'id_sender'    => $admin->id,
        ]);

        ActivityLog::create([
            'action'       => 'RESOLVED',
            'details'      => 'Ticket diselesaikan oleh admin.',
            'action_time'  => now(),
            'performed_by' => $admin->id,
            'id_ticket'    => $ticket->id_ticket,
        ]);

        return response()->json([
            'message' => 'Ticket berhasil di-RESOLVED.',
            'ticket'  => $ticket,
            'chat'    => $msg,
        ]);
    }
}
