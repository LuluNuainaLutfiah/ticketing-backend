<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class AdminTicketController extends Controller
{
    /**
     * Middleware internal untuk memastikan pengguna adalah Admin.
     */
    protected function ensureAdmin($user): void
    {
        if ($user->role !== 'admin') {
            abort(403, 'Hanya admin yang boleh melakukan aksi ini.');
        }
    }

    /**
     * Mengubah status tiket menjadi IN_REVIEW (Admin membuka tiket).
     */
    public function open(Request $request, $ticketId)
    {
        $admin = $request->user();
        $this->ensureAdmin($admin);

        $ticket = Ticket::findOrFail($ticketId);

        if ($ticket->status !== 'OPEN') {
            return response()->json(['message' => 'Ticket bukan dalam status OPEN.'], 422);
        }

        $validated = $request->validate(['message' => ['nullable', 'string', 'max:1000']]);
        $text = $validated['message'] ?? 'Halo, tiket kamu sedang ditinjau oleh admin.';

        // Update Status Tiket
        $ticket->status = 'IN_REVIEW';
        $ticket->updated_at = now();
        $ticket->save();

        // Kirim pesan otomatis ke chat
        $msg = TicketMessage::create([
            'message_body' => $text,
            'sent_at'      => now(),
            'read_status'  => false,
            'id_ticket'    => $ticket->id_ticket,
            'id_sender'    => $admin->id,
        ]);

        // Catat Log Aktivitas
        ActivityLog::create([
            'action'       => 'OPEN_TO_IN_REVIEW',
            'details'      => 'Admin membuka tiket.',
            'action_time'  => now(),
            'performed_by' => $admin->id,
            'id_ticket'    => $ticket->id_ticket,
        ]);

        // RE-LOAD LAMPIRAN: Memastikan data lampiran ikut dikirim balik ke Admin
        $ticket->load(['creator', 'attachments']);

        return response()->json([
            'message' => 'Ticket masuk tahap IN_REVIEW.',
            'ticket'  => $ticket,
            'chat'    => $msg,
        ]);
    }

    /**
     * Mengubah status tiket menjadi IN_PROGRESS (Mulai Pengerjaan).
     */
    public function startWork(Request $request, $ticketId)
    {
        $admin = $request->user();
        $this->ensureAdmin($admin);

        $ticket = Ticket::findOrFail($ticketId);

        if ($ticket->status !== 'IN_REVIEW') {
            return response()->json(['message' => 'Status harus IN_REVIEW.'], 422);
        }

        $ticket->status = 'IN_PROGRESS';
        $ticket->updated_at = now();
        $ticket->save();

        // Catat Log
        ActivityLog::create([
            'action'       => 'START_WORK',
            'details'      => 'Admin mulai mengerjakan tiket.',
            'action_time'  => now(),
            'performed_by' => $admin->id,
            'id_ticket'    => $ticket->id_ticket,
        ]);

        // RE-LOAD LAMPIRAN: Penting agar lampiran tetap tampil di UI Admin
        $ticket->load(['creator', 'attachments']);

        return response()->json([
            'message' => 'Ticket masuk tahap IN_PROGRESS.',
            'ticket'  => $ticket,
        ]);
    }

    /**
     * Menyelesaikan tiket (RESOLVED).
     */
    public function resolve(Request $request, $ticketId)
    {
        $admin = $request->user();
        $this->ensureAdmin($admin);

        $ticket = Ticket::findOrFail($ticketId);

        $ticket->status      = 'RESOLVED';
        $ticket->resolved_at = now();
        $ticket->save();

        ActivityLog::create([
            'action'       => 'RESOLVED',
            'details'      => 'Tiket diselesaikan admin.',
            'action_time'  => now(),
            'performed_by' => $admin->id,
            'id_ticket'    => $ticket->id_ticket,
        ]);

        // Load lampiran untuk response final
        $ticket->load(['creator', 'attachments']);

        return response()->json([
            'message' => 'Ticket berhasil diselesaikan.',
            'ticket'  => $ticket,
        ]);
    }
}
