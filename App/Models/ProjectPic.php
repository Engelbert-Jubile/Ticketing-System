<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property string $id
 * @property int $project_id
 * @property int $user_id
 * @property string $position
 * @property string $role_type
 * @property bool $is_primary
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectAction> $actions
 * @property-read int|null $actions_count
 * @property-read \App\Models\Project $project
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectSubAction> $subactions
 * @property-read int|null $subactions_count
 * @property-read \App\Models\User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectPic newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectPic newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectPic query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectPic whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectPic whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectPic wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectPic whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectPic whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectPic whereUserId($value)
 *
 * @mixin \Eloquent
 */
class ProjectPic extends Model
{
    protected $table = 'project_pics';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'project_id',
        'user_id',
        'position',
        'role_type',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'bool',
    ];

    protected static function booted(): void
    {
        static::creating(function (ProjectPic $pic) {
            if (empty($pic->id)) {
                $pic->id = (string) Str::uuid();
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(ProjectAction::class, 'pic_id');
    }

    public function subactions(): HasMany
    {
        return $this->hasMany(ProjectSubAction::class, 'pic_id');
    }
}
