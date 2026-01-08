<?php

namespace App\Models;

use App\Support\RoleHelpers;
use App\Support\TicketStatusSync;
use App\Support\WorkflowStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string|null $uuid
 * @property string|null $project_no
 * @property string $public_slug
 * @property int|null $ticket_id
 * @property string|null $status_id
 * @property string $title
 * @property string|null $description
 * @property string|null $planning
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property int|null $created_by
 * @property int|null $agent_id
 * @property int|null $assigned_id
 * @property int|null $requester_id FK to users.id - requester/pengguna yang meminta proyek
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectAction> $actions
 * @property-read int|null $actions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Attachment> $attachments
 * @property-read int|null $attachments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectCost> $costs
 * @property-read int|null $costs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectDeliverable> $deliverables
 * @property-read int|null $deliverables_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectPic> $pics
 * @property-read int|null $pics_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectRiskAnalysis> $risks
 * @property-read int|null $risks_count
 * @property-read \App\Models\Ticket|null $ticket
 * @property-read \App\Models\User|null $agent
 * @property-read \App\Models\User|null $assignee
 * @property-read \App\Models\User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project wherePlanning($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereProjectNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereRequesterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereTicketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereUuid($value)
 *
 * @mixin \Eloquent
 */
class Project extends Model
{
    protected $table = 'projects';

    protected $fillable = [
        'uuid',
        'ticket_id',
        'project_no',
        'public_slug',
        'status_id',
        'title',
        'description',
        'status',
        'start_date',
        'end_date',
        'created_by',
        'agent_id',
        'assigned_id',
        'requester_id',
        'planning',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Project $p) {
            if (empty($p->uuid)) {
                $p->uuid = (string) Str::uuid();
            }
            if (empty($p->project_no)) {
                $p->project_no = static::nextNumber();
            }
            if (empty($p->created_by) && Auth::check()) {
                $p->created_by = Auth::id();
            }
        });

        static::saving(function (Project $project) {
            if ($project->isDirty('title') || empty($project->public_slug)) {
                $project->public_slug = static::generateUniquePublicSlug($project->title, $project->id);
            }
        });

        static::saved(function (Project $project) {
            TicketStatusSync::handleProjectSaved($project);
        });
    }

    public static function nextNumber(): string
    {
        $prefix = 'PRJ'.now()->format('Y');
        $last = static::where('project_no', 'like', $prefix.'%')
            ->orderByDesc('project_no')
            ->value('project_no');
        $seq = $last ? (int) substr($last, -4) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    // ================= Relasi =================

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_id');
    }

    // =================== PATCH DITAMBAHKAN ===================
    /**
     * Mendapatkan user yang membuat proyek ini.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    // ===================== AKHIR PATCH =====================

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function pics(): HasMany
    {
        return $this->hasMany(ProjectPic::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(ProjectAction::class);
    }

    public function costs(): HasMany
    {
        return $this->hasMany(ProjectCost::class);
    }

    public function risks(): HasMany
    {
        return $this->hasMany(ProjectRiskAnalysis::class);
    }

    public function deliverables(): HasMany
    {
        return $this->hasMany(ProjectDeliverable::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'project_id');
    }

    protected function planning(): Attribute
    {
        return Attribute::make(
            get: function () {
                $raw = $this->attributes['plannning'] ?? ($this->attributes['planning'] ?? null);
                if (is_string($raw)) {
                    $decoded = json_decode($raw, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        return $decoded;
                    }
                }

                return $raw;
            },
            set: function ($value) {
                if (is_array($value)) {
                    $stored = json_encode($value, JSON_UNESCAPED_UNICODE);
                } elseif (is_null($value) || $value === '') {
                    $stored = null;
                } else {
                    $stored = (string) $value;
                }

                $table = $this->getTable();
                $column = Schema::hasColumn($table, 'planning') ? 'planning' : 'plannning';

                return [$column => $stored];
            }
        );
    }

    protected function status(): Attribute
    {
        return Attribute::make(
            get: function () {
                $raw = $this->attributes['status'] ?? null;

                return WorkflowStatus::normalize($raw);
            },
            set: function ($value) {
                return ['status' => WorkflowStatus::normalize($value)];
            }
        );
    }

    public function isRequester(User $user): bool
    {
        $ticket = $this->relationLoaded('ticket') ? $this->ticket : $this->ticket()->first();
        if ($ticket) {
            if ($ticket->isRequester($user)) {
                return true;
            }
        }

        if ($this->requester_id && (int) $this->requester_id === (int) $user->id) {
            return true;
        }

        return $this->created_by && (int) $this->created_by === (int) $user->id;
    }

    public function isAgent(User $user): bool
    {
        $userId = (int) $user->id;

        $ticket = $this->relationLoaded('ticket') ? $this->ticket : $this->ticket()->first();
        if ($ticket && $ticket->isAgent($user)) {
            return true;
        }

        if ($this->agent_id && (int) $this->agent_id === $userId) {
            return true;
        }

        if ($this->assigned_id && (int) $this->assigned_id === $userId) {
            return true;
        }

        return $this->pics()->where('user_id', $userId)->exists();
    }

    public function canUserSetStatus(User $user, string $status): bool
    {
        $status = WorkflowStatus::normalize($status);

        if ($this->userHasElevatedRole($user)) {
            return true;
        }

        if ($status === WorkflowStatus::NEW) {
            return false;
        }

        if (in_array($status, WorkflowStatus::agentAllowed(), true)) {
            return $this->isAgent($user);
        }

        if (in_array($status, WorkflowStatus::requesterAllowed(), true)) {
            return $this->isRequester($user);
        }

        return false;
    }

    private function userHasElevatedRole(User $user): bool
    {
        return RoleHelpers::userIsSuperAdmin($user);
    }

    public function getPublicSlugAttribute(?string $value): string
    {
        if ($value) {
            return $value;
        }

        if ($this->exists) {
            $fresh = static::query()->whereKey($this->getKey())->value('public_slug');
            if ($fresh) {
                $this->attributes['public_slug'] = $fresh;

                return $fresh;
            }
        }

        $slug = static::generateUniquePublicSlug($this->title, $this->id);

        if ($this->exists) {
            $this->forceFill(['public_slug' => $slug])->saveQuietly();
        } else {
            $this->attributes['public_slug'] = $slug;
        }

        return $slug;
    }

    public static function findByPublicSlug(string $slug): ?self
    {
        return static::where('public_slug', $slug)->first();
    }

    public static function generateUniquePublicSlug(?string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug((string) $title);
        if ($base === '') {
            $base = 'project';
        }

        $slug = $base;
        $suffix = 2;

        while (static::query()
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('public_slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
