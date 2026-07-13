<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WorkflowInstance extends Model
{
    protected $fillable = ['workflow_id', 'subject_type', 'subject_id', 'current_stage_id', 'status', 'started_at', 'completed_at'];
    protected $casts = ['started_at' => 'datetime', 'completed_at' => 'datetime'];
    public function workflow(): BelongsTo { return $this->belongsTo(Workflow::class); }
    public function currentStage(): BelongsTo { return $this->belongsTo(WorkflowStage::class, 'current_stage_id'); }
    public function subject(): MorphTo { return $this->morphTo(); }
}
