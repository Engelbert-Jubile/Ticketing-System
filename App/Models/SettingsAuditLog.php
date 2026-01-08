<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SettingsAuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id',
        'action',
        'group',
        'key',
        'old_value',
        'new_value',
        'ip_address',
        'user_agent',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
