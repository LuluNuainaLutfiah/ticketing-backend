<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketMessage extends Model
{
    protected $table = 'ticketing_messages';
    protected $primaryKey = 'id_message';

    protected $fillable = [
        'message_body',
        'sent_at',
        'read_status',
        'id_ticket',
        'id_sender',
    ];

    public $timestamps = true; // karena ada created_at & updated_at

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'id_ticket', 'id_ticket');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'id_sender');
    }
}
