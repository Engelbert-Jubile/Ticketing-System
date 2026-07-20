<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Workflow extends Model
{
    use SoftDeletes;

    protected $fillable = ['uuid', 'name', 'code', 'entity_type', 'description', 'trigger_conditions', 'is_active', 'version', 'created_by', 'updated_by'];

    protected $casts = ['trigger_conditions' => 'array', 'is_active' => 'boolean', 'version' => 'integer'];

    protected static function booted(): void
    {
        static::creating(function (Workflow $workflow) {
            $workflow->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function stages(): HasMany
    {
        return $this->hasMany(WorkflowStage::class)->orderBy('position');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(WorkflowHistory::class)->latest();
    }

    public function instances(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class);
    }

    public function instanceHistories(): HasMany
    {
        return $this->hasMany(WorkflowInstanceHistory::class)->latest();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
