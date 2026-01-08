<?php

namespace App\Models;

use App\Support\RoleHelpers;
use App\Support\TicketStatusSync;
use App\Support\WorkflowStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string|null $uuid
 * @property string|null $task_no
 * @property int|null $ticket_id
 * @property int|null $project_id
 * @property int|null $assignee_id
 * @property string $title
 * @property string|null $description
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property int|null $created_by
 * @property string|null $planning
 * @property string|null $priority
 * @property string|null $assigned_to
 * @property \Illuminate\Support\Carbon|null $due_at
 * @property string|null $completed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $assignee
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Attachment> $attachments
 * @property-read int|null $attachments_count
 * @property-read string $status_label
 * @property-read \App\Domains\Project\Models\Project|null $project
 * @property-read \App\Models\User|null $requester
 * @property-read \App\Models\Ticket|null $ticket
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereAssignedTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereAssigneeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereDueAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task wherePlanning($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereTaskNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereTicketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereUuid($value)
 *
 * @mixin \Eloquent
 */
class Task extends Model
{
    protected $table = 'tasks';

    protected $guarded = ['id'];

    protected $fillable = [
        'uuid',
        'ticket_id',
        'project_id',
        'task_no',
        'title',
        'description',
        'status',
        'start_date',
        'end_date',
        'created_by',
        'planning',
        'priority',
        'assignee_id',
        'assigned_to',
        'due_at',
        'completed_at',
    ];

    protected $casts = [
        'status' => 'string',
        'start_date' => 'date',
        'end_date' => 'date',
        'due_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Task $t) {
            if (empty($t->uuid)) {
                $t->uuid = (string) Str::uuid();
            }
            if (empty($t->task_no)) {
                $t->task_no = static::nextNumber();
            }
            if (empty($t->created_by) && Auth::check()) {
                $t->created_by = Auth::id();
            }
        });
        static::saving(function (Task $t) {
            $val = $t->status instanceof \BackedEnum ? $t->status->value : $t->status;
            if (in_array($val, ['completed', 'done'], true)) {
                if (! $t->completed_at) {
                    $t->completed_at = now();
                }
            } elseif ($t->isDirty('status')) {
                $t->completed_at = null;
            }

            if ($t->isDirty('title') || empty($t->public_slug)) {
                $t->public_slug = static::generateUniquePublicSlug($t->title, $t->id);
            }
        });

        static::saved(function (Task $task) {
            TicketStatusSync::handleTaskSaved($task);
        });
    }

    public static function nextNumber(): string
    {
        $prefix = 'TSK'.now()->format('Y');
        $last = static::where('task_no', 'like', $prefix.'%')
            ->orderByDesc('task_no')
            ->value('task_no');
        $seq = $last ? (int) substr($last, -4) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    // ===== Relasi =====
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Ticket::class, 'ticket_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Project\Models\Project::class, 'project_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'assignee_id');
    }

    // =================== PATCH DITAMBAHKAN ===================
    /**
     * Mendapatkan user yang membuat task ini (sebagai requester).
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
    // ===================== AKHIR PATCH =====================

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function getStatusLabelAttribute(): string
    {
        $status = (string) $this->status;
        // Translate to label via enum map when possible
        $map = [
            'new' => 'New',
            'in_progress' => 'In Progress',
            'confirmation' => 'Confirmation',
            'revision' => 'Revision',
            'done' => 'Done',
            'on_hold' => 'On Hold',
            'cancelled' => 'Cancelled',
        ];

        return $map[strtolower($status)] ?? ucfirst(str_replace('_', ' ', $status));
    }

    protected function planning(): Attribute
    {
        return Attribute::make(
            get: function () {
                $raw = $this->attributes['plannning'] ?? ($this->attributes['planning'] ?? null);
                if (is_string($raw)) {
                    $decoded = json_decode($raw, true);

                    return json_last_error() === JSON_ERROR_NONE ? $decoded : $raw;
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
                $useTypo = array_key_exists('plannning', $this->attributes)
                    || ! array_key_exists('planning', $this->attributes);

                return $useTypo ? ['plannning' => $stored] : ['planning' => $stored];
            }
        );
    }

    public function isRequester(\App\Models\User $user): bool
    {
        $userId = (int) $user->id;

        if ($this->created_by && (int) $this->created_by === $userId) {
            return true;
        }

        $ticket = $this->relationLoaded('ticket') ? $this->ticket : $this->ticket()->select(['id', 'requester_id'])->first();
        if ($ticket && (int) ($ticket->requester_id ?? 0) === $userId) {
            return true;
        }

        return false;
    }

    public function isAssignee(\App\Models\User $user): bool
    {
        $userId = (int) $user->id;

        if ($this->assignee_id && (int) $this->assignee_id === $userId) {
            return true;
        }

        $assignedRaw = $this->assigned_to;
        if (is_string($assignedRaw) && $assignedRaw !== '') {
            $decoded = json_decode($assignedRaw, true);
            if (is_array($decoded)) {
                foreach ($decoded as $value) {
                    if ((int) $value === $userId) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function canUserSetStatus(\App\Models\User $user, string $status): bool
    {
        $status = strtolower($status);
        if (! in_array($status, WorkflowStatus::all(), true)) {
            return false;
        }

        if (RoleHelpers::userIsSuperAdmin($user)) {
            return true;
        }

        if (in_array($status, WorkflowStatus::agentAllowed(), true)) {
            return $this->isAssignee($user);
        }

        if (in_array($status, WorkflowStatus::requesterAllowed(), true)) {
            return $this->isRequester($user);
        }

        if ($status === WorkflowStatus::NEW) {
            return false;
        }

        return false;
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
            $base = 'task';
        }

        $slug = $base;
        $suffix = 2;

        while (static::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('public_slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
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
}
