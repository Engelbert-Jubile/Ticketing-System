<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property string $id
 * @property int $project_id
 * @property string $title
 * @property string|null $description
 * @property string $status_id
 * @property int $progress
 * @property string|null $pic_id
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \App\Models\ProjectPic|null $pic
 * @property-read \App\Models\Project $project
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectSubAction> $subactions
 * @property-read int|null $subactions_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectAction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectAction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectAction query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectAction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectAction whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectAction whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectAction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectAction wherePicId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectAction whereProgress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectAction whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectAction whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectAction whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectAction whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectAction whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ProjectAction extends Model
{
    protected $table = 'project_actions';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'project_id',
        'title',
        'description',
        'status_id',
        'progress',
        'pic_id',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ProjectAction $action) {
            if (empty($action->id)) {
                $action->id = (string) Str::uuid();
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(ProjectPic::class, 'pic_id');
    }

    public function subactions(): HasMany
    {
        return $this->hasMany(ProjectSubAction::class, 'action_id');
    }
}
