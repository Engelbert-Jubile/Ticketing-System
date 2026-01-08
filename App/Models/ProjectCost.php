<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property string $id
 * @property int $project_id
 * @property string $cost_item
 * @property string $category
 * @property float $estimated_cost
 * @property float|null $actual_cost
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \App\Models\Project $project
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectCost newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectCost newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectCost query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectCost whereActualCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectCost whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectCost whereCostItem($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectCost whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectCost whereEstimatedCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectCost whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectCost whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectCost whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectCost whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ProjectCost extends Model
{
    protected $table = 'project_costs';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'project_id',
        'cost_item',
        'category',
        'estimated_cost',
        'actual_cost',
        'notes',
    ];

    protected $casts = [
        'estimated_cost' => 'float',
        'actual_cost' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ProjectCost $cost) {
            if (empty($cost->id)) {
                $cost->id = (string) Str::uuid();
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
