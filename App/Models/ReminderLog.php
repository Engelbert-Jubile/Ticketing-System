<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReminderLog extends Model
{
    use HasFactory;

    protected $table = 'reminder_logs';

    protected $fillable = [
        'user_id',
        'item_type',
        'item_id',
        'event',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];
}
