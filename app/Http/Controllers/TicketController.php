<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    // USER / ADMIN: buat tiket baru
    public function store(Request $request)
    {
        $user = $request->user(); // yg lagi login

        $data = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'category'    => ['required', 'string', 'max:100'],
            'priority'    => ['required', 'in:LOW,MEDIUM,HIGH'],
        ]);

        // generate code_ticket sederhana
        $nextId = (Ticket::max('id_ticket') ?? 0) + 1;
        $code   = 'TCK-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);

        $ticket = Ticket::create([
            'code_ticket' => $code,
            'title'       => $data['title'],
            'description' => $data['description'],
            'category'    => $data['category'],
            'priority'    => $data['priority'],
            'status'      => 'OPEN',
            'created_by'  => $user->id,
        ]);

        return response()->json([
            'message' => 'Ticket berhasil dibuat',
            'data'    => $ticket,
        ], 201);
    }

    // USER: lihat tiket yang dia buat sendiri
    public function myTickets(Request $request)
    {
        $user = $request->user();

        $tickets = Ticket::where('created_by', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'message' => 'Daftar tiket milik user',
            'data'    => $tickets,
        ]);
    }

    // ADMIN: lihat semua tiket
    public function adminIndex()
    {
        $tickets = Ticket::with('creator')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'message' => 'Daftar semua tiket',
            'data'    => $tickets,
        ]);
    }

    // ADMIN: update status tiket
    public function updateStatus(Request $request, $id_ticket)
    {
        $data = $request->validate([
            'status' => ['required', 'in:OPEN,IN_PROGRESS,RESOLVED'],
        ]);

        $ticket = Ticket::findOrFail($id_ticket);

        $ticket->status = $data['status'];

        if ($data['status'] === 'RESOLVED') {
            $ticket->resolved_at = now();
        }

        $ticket->save();

        return response()->json([
            'message' => 'Status tiket berhasil diupdate',
            'data'    => $ticket,
        ]);
    }
}
