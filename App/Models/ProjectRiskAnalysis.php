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
 * @property string $impact
 * @property string $likelihood
 * @property string|null $mitigation_plan
 * @property string $status_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \App\Models\Project $project
 * @property-read \App\Domains\Project\Models\Status $status
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectRiskAnalysis newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectRiskAnalysis newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectRiskAnalysis query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectRiskAnalysis whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectRiskAnalysis whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectRiskAnalysis whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectRiskAnalysis whereImpact($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectRiskAnalysis whereLikelihood($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectRiskAnalysis whereMitigationPlan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectRiskAnalysis whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectRiskAnalysis whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectRiskAnalysis whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectRiskAnalysis whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ProjectRiskAnalysis extends Model
{
    protected $table = 'project_risk_analyses';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'project_id',
        'name',
        'description',
        'impact',
        'likelihood',
        'mitigation_plan',
        'status_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ProjectRiskAnalysis $risk) {
            if (empty($risk->id)) {
                $risk->id = (string) Str::uuid();
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
