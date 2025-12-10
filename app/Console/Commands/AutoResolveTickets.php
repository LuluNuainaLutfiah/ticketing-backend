<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\ActivityLog;

class AutoResolveTickets extends Command
{
    protected $signature = 'tickets:auto-resolve';
    protected $description = 'Auto resolve tickets if user does not respond for 24 hours';

    public function handle()
    {
        $now = now();

        // Ticket yang sudah IN_PROGRESS (dua arah)
        $tickets = Ticket::where('status', 'IN_PROGRESS')->get();

        foreach ($tickets as $ticket) {

            // Pesan terakhir
            $lastMsg = TicketMessage::where('id_ticket', $ticket->id_ticket)
                ->orderBy('sent_at', 'desc')
                ->first();

            if (!$lastMsg) continue;

            // Jika pesan terakhir dari ADMIN → user tidak merespon
            if ($lastMsg->sender->role !== 'admin') continue;

            // Jika lebih dari 24 jam
            if ($lastMsg->sent_at->lte($now->copy()->subHours(24))) {

                // Update status ticket
                $ticket->update([
                    'status'      => 'RESOLVED',
                    'resolved_at' => $now,
                ]);

                // Kirim pesan otomatis
                TicketMessage::create([
                    'message_body' => 'Ticket otomatis diselesaikan karena tidak ada respon dari user selama 24 jam.',
                    'sent_at'      => $now,
                    'read_status'  => false,
                    'id_ticket'    => $ticket->id_ticket,
                    'id_sender'    => 1, // ID Admin Sistem
                ]);

                // Log aktivitas
                ActivityLog::create([
                    'action'       => 'AUTO_RESOLVE',
                    'details'      => 'Ticket auto resolved setelah 24 jam tanpa respon user',
                    'action_time'  => $now,
                    'performed_by' => null,
                    'id_ticket'    => $ticket->id_ticket,
                ]);

                $this->info("Ticket {$ticket->id_ticket} auto-resolved.");
            }
        }

        return Command::SUCCESS;
    }
}
