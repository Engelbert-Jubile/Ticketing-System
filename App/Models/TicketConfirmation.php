<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketConfirmation extends Model
{
    protected $fillable = ['note'];

    public function evidence(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }
}
