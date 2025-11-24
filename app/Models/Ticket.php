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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
