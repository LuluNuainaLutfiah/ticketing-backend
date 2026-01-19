<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        return DB::transaction(function () use ($request, $ticket, $admin) {
            $validated = $request->validate([
                'message' => ['nullable', 'string', 'max:1000']
            ]);

            $text = $validated['message'] ?? 'Halo, tiket kamu sedang ditinjau oleh admin.';

            // 1. Update Status Tiket
            $ticket->status = 'IN_REVIEW';
            $ticket->updated_at = now();
            $ticket->save();

            // 2. Kirim pesan otomatis ke chat
            $msg = TicketMessage::create([
                'message_body' => $text,
                'sent_at'      => now(),
                'read_status'  => false,
                'id_ticket'    => $ticket->id_ticket,
                'id_sender'    => $admin->id,
            ]);

            // 3. Catat Log Aktivitas (Untuk Dashboard Aktivitas Terbaru)
            ActivityLog::create([
                'action'       => 'OPEN_TO_IN_REVIEW',
                'details'      => 'Admin mulai meninjau tiket: ' . $ticket->code_ticket,
                'action_time'  => now(),
                'performed_by' => $admin->id,
                'id_ticket'    => $ticket->id_ticket,
            ]);

            // Load creator dan attachments agar UI Admin tetap menampilkan data lengkap
            $ticket->load(['creator', 'attachments']);

            return response()->json([
                'message' => 'Ticket masuk tahap IN_REVIEW.',
                'ticket'  => $ticket,
                'chat'    => $msg,
            ]);
        });
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
            return response()->json(['message' => 'Status harus IN_REVIEW sebelum diproses.'], 422);
        }

        return DB::transaction(function () use ($ticket, $admin) {
            $ticket->status = 'IN_PROGRESS';
            $ticket->updated_at = now();
            $ticket->save();

            // Catat Log
            ActivityLog::create([
                'action'       => 'START_WORK',
                'details'      => 'Admin mulai mengerjakan tiket: ' . $ticket->code_ticket,
                'action_time'  => now(),
                'performed_by' => $admin->id,
                'id_ticket'    => $ticket->id_ticket,
            ]);

            $ticket->load(['creator', 'attachments']);

            return response()->json([
                'message' => 'Ticket masuk tahap IN_PROGRESS.',
                'ticket'  => $ticket,
            ]);
        });
    }

    /**
     * Menyelesaikan tiket (RESOLVED).
     */
    public function resolve(Request $request, $ticketId)
    {
        $admin = $request->user();
        $this->ensureAdmin($admin);

        $ticket = Ticket::findOrFail($ticketId);

        if ($ticket->status !== 'IN_PROGRESS') {
            return response()->json(['message' => 'Hanya tiket dalam proses yang bisa diselesaikan.'], 422);
        }

        return DB::transaction(function () use ($ticket, $admin) {
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

            $ticket->load(['creator', 'attachments']);

            return response()->json([
                'message' => 'Ticket berhasil diselesaikan.',
                'ticket'  => $ticket,
            ]);
        });
    }
}
