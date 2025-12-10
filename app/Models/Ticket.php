<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $table = 'ticketing';
    protected $primaryKey = 'id_ticket';

    protected $fillable = [
        'code_ticket',
        'title',
        'description',
        'category',
        'priority',
        'status',
        'created_by',
        'resolved_at',
    ];

    // Ticket dibuat oleh user
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Tambahan WAJIB: relasi ticket → semua pesan chat
    public function messages()
    {
        return $this->hasMany(TicketMessage::class, 'id_ticket', 'id_ticket');
    }

    // Tambahan WAJIB: relasi ticket → semua attachment (baik chat atau file awal)
    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'id_ticket', 'id_ticket');
    }
}
