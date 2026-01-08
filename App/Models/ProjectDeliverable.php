<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property string $id
 * @property int $project_id
 * @property string $name
 * @property string|null $description
 * @property string $status_id
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $verified_at
 * @property string $verified_by
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \App\Models\Project $project
 * @property-read \App\Domains\Project\Models\Status $status
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectDeliverable newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectDeliverable newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectDeliverable query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectDeliverable whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectDeliverable whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectDeliverable whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectDeliverable whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectDeliverable whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectDeliverable whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectDeliverable whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectDeliverable whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectDeliverable whereVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectDeliverable whereVerifiedBy($value)
 *
 * @mixin \Eloquent
 */
class ProjectDeliverable extends Model
{
    protected $table = 'project_deliverables';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'project_id',
        'name',
        'description',
        'status_id',
        'completed_at',
        'verified_at',
        'verified_by',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ProjectDeliverable $deliverable) {
            if (empty($deliverable->id)) {
                $deliverable->id = (string) Str::uuid();
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Project\Models\Status::class, 'status_id');
    }
}
