<?php

namespace App\Domains\Project\Models;

use App\Support\WorkflowStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

/**
 * @property string $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Domains\Project\Models\Project> $projects
 * @property-read int|null $projects_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Status newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Status newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Status query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Status whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Status whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Status whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Status whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Status extends Model
{
    /* tabel yang dipakai */
    protected $table = 'statuses';

    /* kolom yang boleh di-fill */
    protected $fillable = ['id', 'name'];

    /* primary-key bertipe string & TIDAK auto-increment */
    public $incrementing = false;

    protected $keyType = 'string';

    /* relasi ke Project */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public static function ensureDefaults(): void
    {
        $model = new static;
        if (! Schema::hasTable($model->getTable())) {
            return;
        }

        foreach (WorkflowStatus::labels() as $status => $label) {
            $code = WorkflowStatus::code($status);
            static::query()->updateOrCreate(['id' => $code], ['name' => $label]);
        }
    }
}
