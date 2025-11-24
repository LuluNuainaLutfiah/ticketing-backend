<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'activity_log';
    protected $primaryKey = 'id_log';

    protected $fillable = [
        'action',
        'details',
        'action_time',
        'performed_by',
        'id_ticket',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'id_ticket', 'id_ticket');
    }
}
