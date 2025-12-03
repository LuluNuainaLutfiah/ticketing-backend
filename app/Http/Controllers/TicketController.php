<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    /** ========== HELPER BUAT KODE TICKET ========== */
    protected function generateTicketCode(): string
    {
        // contoh: TCK-202512-AB12
        return 'TCK-' . now()->format('Ym') . '-' . Str::upper(Str::random(4));
    }

    /** ========== LIST TIKET UNTUK USER LOGIN ========== */
    public function userIndex(Request $request)
    {
        $user = $request->user();

        $query = Ticket::where('created_by', $user->id)
            ->orderByDesc('created_at');

        // optional filter status ?status=OPEN
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $tickets = $query->get();

        return response()->json([
            'message' => 'Tickets fetched',
            'data'    => $tickets,
        ]);
    }

    /** ========== CREATE TICKET OLEH USER ========== */
    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'category'    => ['required', 'string', 'max:100'],
            'priority'    => ['required', 'in:LOW,MEDIUM,HIGH'],
        ]);

        $ticket = Ticket::create([
            'code_ticket' => $this->generateTicketCode(),
            'title'       => $data['title'],
            'description' => $data['description'],
            'category'    => $data['category'],
            'priority'    => $data['priority'],
            'status'      => 'OPEN',          // default
            'created_by'  => $user->id,
        ]);

        // catat ke activity_log
        ActivityLog::create([
            'action'      => 'CREATE_TICKET',
            'details'     => 'User membuat tiket baru',
            'action_time' => now(),
            'performed_by'=> $user->id,
            'id_ticket'   => $ticket->id_ticket,
        ]);

        return response()->json([
            'message' => 'Ticket created',
            'data'    => $ticket,
        ], 201);
    }

    /** ========== DETAIL TICKET (HANYA PEMILIK) ========== */
    public function show(Request $request, $id_ticket)
    {
        $user = $request->user();

        $ticket = Ticket::where('id_ticket', $id_ticket)
            ->where('created_by', $user->id)
            ->first();

        if (!$ticket) {
            return response()->json([
                'message' => 'Ticket not found or not yours',
            ], 404);
        }

        return response()->json([
            'message' => 'Ticket fetched',
            'data'    => $ticket,
        ]);
    }

    /** ========== UPDATE TICKET (HANYA PEMILIK & BELUM RESOLVED) ========== */
    public function update(Request $request, $id_ticket)
    {
        $user = $request->user();

        $ticket = Ticket::where('id_ticket', $id_ticket)
            ->where('created_by', $user->id)
            ->first();

        if (!$ticket) {
            return response()->json([
                'message' => 'Ticket not found or not yours',
            ], 404);
        }

        if ($ticket->status === 'RESOLVED') {
            return response()->json([
                'message' => 'Resolved ticket cannot be updated',
            ], 400);
        }

        $data = $request->validate([
            'title'       => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'category'    => ['sometimes', 'string', 'max:100'],
            'priority'    => ['sometimes', 'in:LOW,MEDIUM,HIGH'],
        ]);

        $ticket->fill($data);
        $ticket->save();

        ActivityLog::create([
            'action'      => 'UPDATE_TICKET',
            'details'     => 'User mengubah tiket',
            'action_time' => now(),
            'performed_by'=> $user->id,
            'id_ticket'   => $ticket->id_ticket,
        ]);

        return response()->json([
            'message' => 'Ticket updated',
            'data'    => $ticket,
        ]);
    }

    /** ========== DELETE TICKET (HANYA PEMILIK) ========== */
    public function destroy(Request $request, $id_ticket)
    {
        $user = $request->user();

        $ticket = Ticket::where('id_ticket', $id_ticket)
            ->where('created_by', $user->id)
            ->first();

        if (!$ticket) {
            return response()->json([
                'message' => 'Ticket not found or not yours',
            ], 404);
        }

        ActivityLog::create([
            'action'      => 'DELETE_TICKET',
            'details'     => 'User menghapus tiket',
            'action_time' => now(),
            'performed_by'=> $user->id,
            'id_ticket'   => $ticket->id_ticket,
        ]);

        $ticket->delete();

        return response()->json([
            'message' => 'Ticket deleted',
        ]);
    }

    /** ========== (OPSIONAL) LIST UNTUK ADMIN ========== */
    public function adminIndex(Request $request)
    {
        $query = Ticket::with('creator')->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $tickets = $query->get();

        return response()->json([
            'message' => 'Admin tickets fetched',
            'data'    => $tickets,
        ]);
    }

    /** ========== (OPSIONAL) ADMIN UPDATE STATUS ========== */
   public function updateStatus(Request $request, $id_ticket)
{
    $admin = $request->user();

    $ticket = Ticket::where('id_ticket', $id_ticket)->firstOrFail();

    $data = $request->validate([
        'status' => ['required', 'in:OPEN,IN_PROGRESS,RESOLVED'],
    ]);

    $oldStatus = $ticket->status;
    $newStatus = $data['status'];

    // kalau status sama, ga usah ngapa-ngapain
    if ($oldStatus === $newStatus) {
        return response()->json([
            'message' => 'Status tidak berubah',
            'data'    => $ticket,
        ]);
    }

    // update status
    $ticket->status = $newStatus;

    // atur resolved_at
    if ($newStatus === 'RESOLVED') {
        // tiket selesai → isi timestamp
        $ticket->resolved_at = now();
    } else {
        // kalau dibalikin ke OPEN / IN_PROGRESS → kosongkan lagi
        $ticket->resolved_at = null;
    }

    $ticket->save();

    // catat di activity_log
    ActivityLog::create([
        'action'      => 'UPDATE_STATUS',
        'details'     => "Admin mengubah status dari {$oldStatus} ke {$newStatus}",
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
