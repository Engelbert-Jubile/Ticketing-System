<?php

namespace App\Models;

use App\Support\RoleHelpers;
use Illuminate\Database\Eloquent\Model;

class KnowledgeEntry extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['checklist' => 'array', 'published' => 'boolean'];

    public function scopeVisibleTo($query, User $user)
    {
        if (RoleHelpers::userIsSuperAdmin($user)) {
            return $query;
        }

        return $query->where(fn ($q) => $q->whereNull('unit')->orWhere('unit', $user->unit ?: '__unassigned__'))
            ->where(fn ($q) => $q->where('published', true)->orWhere('author_id', $user->id));
    }
}
