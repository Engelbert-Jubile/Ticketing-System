<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowHistory extends Model
{
    protected $fillable = ['workflow_id', 'user_id', 'event', 'changes'];
    protected $casts = ['changes' => 'array'];
    public function workflow(): BelongsTo { return $this->belongsTo(Workflow::class); }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
}
