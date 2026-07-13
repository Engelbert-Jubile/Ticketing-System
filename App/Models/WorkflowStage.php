<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowStage extends Model
{
    protected $fillable = ['workflow_id', 'position', 'name', 'status_key', 'responsible_role', 'responsible_user_id', 'action_label', 'instructions'];
    public function workflow(): BelongsTo { return $this->belongsTo(Workflow::class); }
    public function responsibleUser(): BelongsTo { return $this->belongsTo(User::class, 'responsible_user_id'); }
}
