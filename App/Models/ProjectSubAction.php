<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property string $id
 * @property string $action_id
 * @property string $title
 * @property string|null $description
 * @property string $status_id
 * @property int $progress
 * @property string|null $pic_id
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \App\Models\ProjectAction $action
 * @property-read \App\Models\ProjectPic|null $pic
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectSubAction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectSubAction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectSubAction query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectSubAction whereActionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectSubAction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectSubAction whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectSubAction whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectSubAction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectSubAction wherePicId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectSubAction whereProgress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectSubAction whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectSubAction whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectSubAction whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectSubAction whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ProjectSubAction extends Model
{
    protected $table = 'project_subactions';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'action_id',
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
        static::creating(function (ProjectSubAction $sub) {
            if (empty($sub->id)) {
                $sub->id = (string) Str::uuid();
            }
        });
    }

    public function action(): BelongsTo
    {
        return $this->belongsTo(ProjectAction::class, 'action_id');
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(ProjectPic::class, 'pic_id');
    }
}
