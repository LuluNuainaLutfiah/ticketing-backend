<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Attachment;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    // Generate kode ticket: TCK-YYYYMMDD-AB12
    protected function generateTicketCode(): string
    {
        return 'TCK-' . now()->format('Ymd') . '-' . Str::upper(Str::random(4));
    }

    // List ticket milik user login
    public function userIndex(Request $request)
    {
        $user = $request->user();

        $tickets = Ticket::where('created_by', $user->id)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'message' => 'Tickets fetched',
            'data'    => $tickets,
        ]);
    }

    // Create ticket + upload multi file
    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'category'    => 'required|string|max:100',
            'priority'    => 'required|in:LOW,MEDIUM,HIGH',
            'files.*'     => 'nullable|file|max:10240', // 10MB per file
        ]);

        // Buat ticket
        $ticket = Ticket::create([
            'code_ticket' => $this->generateTicketCode(),
            'title'       => $validated['title'],
            'description' => $validated['description'],
            'category'    => $validated['category'],
            'priority'    => $validated['priority'],
            'status'      => 'OPEN',
            'created_by'  => $user->id,
        ]);

        // Upload file-file awal (jika ada)
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {

                $path = $file->store("tickets/{$ticket->id_ticket}", 'public');

                Attachment::create([
                    'file_name'   => $file->getClientOriginalName(),
                    'file_type'   => $file->getMimeType(),
                    'file_path'   => $path,
                    'uploaded_at' => now(),
                    'id_ticket'   => $ticket->id_ticket,
                    'uploaded_by' => $user->id,
                    'id_message'  => null, // file dari create ticket
                ]);
            }
        }

        // Log
        ActivityLog::create([
            'action'      => 'CREATE_TICKET',
            'details'     => 'User membuat ticket baru',
            'action_time' => now(),
            'performed_by'=> $user->id,
            'id_ticket'   => $ticket->id_ticket,
        ]);

        return response()->json([
            'message' => 'Ticket created',
            'data'    => $ticket->load('attachments'),
        ], 201);
    }

    // Detail ticket
    public function show(Request $request, $id_ticket)
    {
        $ticket = Ticket::where('id_ticket', $id_ticket)
            ->where('created_by', $request->user()->id)
            ->first();

        if (!$ticket) {
            return response()->json(['message' => 'Ticket not found'], 404);
        }

        return response()->json([
            'message' => 'Ticket fetched',
            'data'    => $ticket->load('attachments'),
        ]);
    }

    // List ticket untuk admin
    public function adminIndex()
    {
        return response()->json([
            'message' => 'Admin tickets fetched',
            'data'    => Ticket::with('creator')->orderByDesc('created_at')->get(),
        ]);
    }

    // Admin update status
    public function updateStatus(Request $request, $id_ticket)
    {
        $admin = $request->user();

        $validated = $request->validate([
            'status' => 'required|in:OPEN,IN_REVIEW,IN_PROGRESS,RESOLVED',
        ]);

        $ticket = Ticket::findOrFail($id_ticket);

        $old = $ticket->status;
        $new = $validated['status'];

        if ($old === $new) {
            return response()->json(['message' => 'Status tidak berubah'], 200);
        }

        $ticket->status = $new;
        $ticket->resolved_at = ($new === 'RESOLVED') ? now() : null;
        $ticket->save();

        ActivityLog::create([
            'action'      => 'UPDATE_STATUS',
            'details'     => "Status dari {$old} ke {$new}",
            'action_time' => now(),
            'performed_by'=> $admin->id,
            'id_ticket'   => $ticket->id_ticket,
        ]);

        return response()->json([
            'message' => 'Ticket status updated',
            'data'    => $ticket,
        ]);
    }
}
 