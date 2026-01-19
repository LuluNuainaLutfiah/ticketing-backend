<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\Paginator;

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
     * ===============================
     * LIST SEMUA TIKET (ADMIN)
     * 10 per page, MAX 5 PAGE (50 tiket terbaru)
     * GET /api/admin/tickets?page=1
     * ===============================
     */
    public function index(Request $request)
    {
        $admin = $request->user();
        $this->ensureAdmin($admin);

        $perPage = 10;

        // 🔒 Batasi page maksimal 5 (50 data)
        $page = (int) $request->query('page', 1);
        if ($page < 1) {
            $page = 1;
        }
        if ($page > 5) {
            $page = 5;
        }

        // Paksa paginator pakai page di atas
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        $tickets = Ticket::with(['creator', 'attachments'])
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'message' => 'Admin tickets fetched',
            'data'    => $tickets,
        ]);
    }

    /**
     * ===============================
     * DETAIL 1 TIKET (ADMIN)
     * GET /api/admin/tickets/{id}
     * ===============================
     */
    public function show(Request $request, $ticketId)
    {
        $admin = $request->user();
        $this->ensureAdmin($admin);

        $ticket = Ticket::with(['creator', 'attachments', 'messages.sender'])
            ->findOrFail($ticketId);

        return response()->json([
            'message' => 'Ticket detail fetched',
            'data'    => $ticket,
        ]);
    }

    /**
     * ===============================
     * OPEN → IN_REVIEW
     * PATCH /api/admin/tickets/{id}/open
     * ===============================
     */
    public function open(Request $request, $ticketId)
    {
        $admin = $request->user();
        $this->ensureAdmin($admin);

        $ticket = Ticket::findOrFail($ticketId);

        if ($ticket->status !== 'OPEN') {
            return response()->json([
                'message' => 'Ticket bukan dalam status OPEN.'
            ], 422);
        }

        return DB::transaction(function () use ($request, $ticket, $admin) {
            $validated = $request->validate([
                'message' => ['nullable', 'string', 'max:1000'],
            ]);

            $text = $validated['message']
                ?? 'Halo, tiket kamu sedang ditinjau oleh admin.';

            // 1. Update status
            $ticket->status = 'IN_REVIEW';
            $ticket->updated_at = now();
            $ticket->save();

            // 2. Kirim pesan otomatis
            $msg = TicketMessage::create([
                'message_body' => $text,
                'sent_at'      => now(),
                'read_status'  => false,
                'id_ticket'    => $ticket->id_ticket,
                'id_sender'    => $admin->id,
            ]);

            // 3. Log aktivitas
            ActivityLog::create([
                'action'       => 'OPEN_TO_IN_REVIEW',
                'details'      => 'Admin mulai meninjau tiket: ' . $ticket->code_ticket,
                'action_time'  => now(),
                'performed_by' => $admin->id,
                'id_ticket'    => $ticket->id_ticket,
            ]);

            $ticket->load(['creator', 'attachments']);

            return response()->json([
                'message' => 'Ticket masuk tahap IN_REVIEW.',
                'ticket'  => $ticket,
                'chat'    => $msg,
            ]);
        });
    }

    /**
     * ===============================
     * IN_REVIEW → IN_PROGRESS
     * PATCH /api/admin/tickets/{id}/start
     * ===============================
     */
    public function startWork(Request $request, $ticketId)
    {
        $admin = $request->user();
        $this->ensureAdmin($admin);

        $ticket = Ticket::findOrFail($ticketId);

        if ($ticket->status !== 'IN_REVIEW') {
            return response()->json([
                'message' => 'Status harus IN_REVIEW sebelum diproses.'
            ], 422);
        }

        return DB::transaction(function () use ($ticket, $admin) {
            $ticket->status = 'IN_PROGRESS';
            $ticket->updated_at = now();
            $ticket->save();

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
     * ===============================
     * IN_PROGRESS → RESOLVED
     * PATCH /api/admin/tickets/{id}/resolve
     * ===============================
     */
    public function resolve(Request $request, $ticketId)
    {
        $admin = $request->user();
        $this->ensureAdmin($admin);

        $ticket = Ticket::findOrFail($ticketId);

        if ($ticket->status !== 'IN_PROGRESS') {
            return response()->json([
                'message' => 'Hanya tiket dalam proses yang bisa diselesaikan.'
            ], 422);
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
