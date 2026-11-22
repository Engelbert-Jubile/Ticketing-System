<?php

namespace App\Models;

use App\Support\RoleHelpers;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ServerRoomTemperatureCheck extends Model
{
    protected $fillable = [
        'recorded_by', 'inspector', 'unit', 'room', 'checked_at', 'temperature_c',
        'needs_follow_up', 'notes', 'evidence_path',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
        'temperature_c' => 'float',
        'needs_follow_up' => 'boolean',
    ];

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if (RoleHelpers::userIsSuperAdmin($user)) {
            return $query;
        }

        $unit = trim((string) $user->unit);

        return $query->where(function (Builder $query) use ($user, $unit) {
            $query->where('recorded_by', $user->id);
            if ($unit !== '') {
                $query->orWhere('unit', $unit);
            }
        });
    }
}
