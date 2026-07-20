<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowInstanceHistory extends Model
{
    protected $fillable = [
        'workflow_instance_id', 'workflow_id', 'user_id', 'event', 'from_status', 'to_status',
        'from_stage_name', 'to_stage_name', 'metadata',
    ];

    protected $casts = ['metadata' => 'array'];

    public function instance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'workflow_instance_id');
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
