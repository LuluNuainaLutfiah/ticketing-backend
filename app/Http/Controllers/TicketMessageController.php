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
    protected function findTicketForUser(Request $req, $id): ?Ticket
    {
        $u = $req->user();
        $q = Ticket::where('id_ticket', $id);
        if ($u->role !== 'admin') {
            $q->where('created_by', $u->id);
        }
        return $q->first();
    }

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

    public function store(Request $req, $id_ticket)
    {
        $ticket = $this->findTicketForUser($req, $id_ticket);
        if (!$ticket) return response()->json(['message' => 'Ticket not found'], 404);

        $user = $req->user();
        $isAdmin = $user->role === 'admin';

        if ($ticket->status === 'OPEN' && !$isAdmin) {
            return response()->json(['message' => 'Menunggu admin membuka ticket'], 403);
        }

        $validated = $req->validate([
            'message_body' => 'nullable|string|max:2000',
            'files.*'      => 'nullable|file|mimes:jpg,jpeg,png,pdf,docx,xlsx,zip|max:10240',
        ]);

        return DB::transaction(function () use ($req, $ticket, $user, $isAdmin, $validated) {
            if ($ticket->status === 'OPEN' && $isAdmin) {
                $ticket->update(['status' => 'IN_REVIEW']);
                ActivityLog::create([
                    'action' => 'STATUS_CHANGED',
                    'details' => 'Status otomatis berubah ke IN_REVIEW (Admin membalas)',
                    'action_time' => now(),
                    'performed_by' => $user->id,
                    'id_ticket' => $ticket->id_ticket,
                ]);
            }

            $msg = TicketMessage::create([
                'message_body' => $validated['message_body'] ?? null,
                'sent_at' => now(),
                'read_status' => false,
                'id_ticket' => $ticket->id_ticket,
                'id_sender' => $user->id,
            ]);

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
                        'id_message'  => $msg->id_message, // Penanda file ini milik chat
                    ]);
                }
            }

            $msg->load(['sender', 'attachments']);
            return response()->json(['message' => 'Message sent', 'data' => $msg], 201);
        });
    }
}
