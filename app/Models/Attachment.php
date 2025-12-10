<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    protected $table = 'attachments';
    protected $primaryKey = 'id_attachment';

    protected $fillable = [
        'file_name',
        'file_type',
        'file_path',
        'uploaded_at',
        'id_ticket',
        'uploaded_by',
        'id_message',
    ];

    // karena tabel kamu ada created_at & updated_at
    public $timestamps = true;

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'id_ticket', 'id_ticket');
    }

    public function message()
    {
        return $this->belongsTo(TicketMessage::class, 'id_message', 'id_message');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

