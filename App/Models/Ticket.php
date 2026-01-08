<?php

namespace App\Models;

use App\Domains\Project\Models\Status;
use App\Support\RoleHelpers;
use App\Support\TicketStatusSync;
use App\Support\WorkflowStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string|null $uuid
 * @property string|null $ticket_no
 * @property string $title
 * @property string|null $description
 * @property string|null $reason
 * @property string|null $letter_no
 * @property string $priority
 * @property string $type
 * @property string $status
 * @property string|null $status_id
 * @property int|null $requester_id
 * @property int|null $agent_id
 * @property int|null $assigned_id
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property \Illuminate\Support\Carbon|null $finish_date
 * @property string|null $sla
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $due_at
 * @property \Illuminate\Support\Carbon|null $finish_at
 * @property-read \App\Models\User|null $agent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $assignedUsers
 * @property-read int|null $assigned_users_count
 * @property-read \App\Models\User|null $assignee
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Attachment> $attachments
 * @property-read int|null $attachments_count
 * @property-read string $assignees_label
 * @property-read Priority|null $priorityRelation
 * @property-read \App\Models\Project|null $project
 * @property-read \App\Models\User|null $requester
 * @property-read Status|null $statusRelation
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereAgentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereAssignedId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereDueAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereFinishAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereFinishDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereLetterNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereRequesterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereSla($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereTicketNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereUuid($value)
 *
 * @mixin \Eloquent
 */
class Ticket extends Model
{
    use HasFactory;

    protected $table = 'tickets';

    protected $fillable = [
        'uuid',
        'ticket_no',
        'title',
        'description',
        'reason',
        'letter_no',
        'priority',
        'type',
        'status_id',
        'status',
        'priority_id',
        'requester_id',
        'agent_id',
        'assigned_id',
        'due_date',
        'finish_date',
        'sla',
        'due_at',
        'finish_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'finish_date' => 'date',
        'due_at' => 'datetime',
        'finish_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Ticket $t) {
            if (empty($t->ticket_no)) {
                $t->ticket_no = static::nextTicketNo();
            }
            if (empty($t->uuid)) {
                $t->uuid = (string) Str::uuid();
            }
            if (empty($t->requester_id) && Auth::check()) {
                $t->requester_id = Auth::id();
            }
        });

        static::saved(function (Ticket $ticket) {
            TicketStatusSync::handleTicketSaved($ticket);
        });
    }

    public static function nextTicketNo(): string
    {
        $prefix = 'TCK-'.now()->format('Ymd').'-';
        $last = static::whereDate('created_at', now()->toDateString())
            ->where('ticket_no', 'like', $prefix.'%')
            ->max('ticket_no');

        $n = 1;
        if ($last && preg_match('/-(\d{4})$/', $last, $m)) {
            $n = (int) $m[1] + 1;
        }

        return $prefix.str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }

    // ========= Relasi =========
    public function project(): HasOne
    {
        return $this->hasOne(\App\Models\Project::class, 'ticket_id')->latestOfMany();
    }

    public function projects(): HasMany
    {
        return $this->hasMany(\App\Models\Project::class, 'ticket_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'ticket_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'ticket_assignees', 'ticket_id', 'user_id')
            ->withTimestamps();
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function statusRelation(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    /**
     * Mendapatkan prioritas dari tiket.
     * Nama fungsi diubah untuk menghindari konflik dengan kolom 'priority'.
     */
    public function priorityRelation(): BelongsTo
    {
        return $this->belongsTo(Priority::class, 'priority_id');
    }

    public function getAssigneesLabelAttribute(): string
    {
        $names = $this->relationLoaded('assignedUsers')
            ? $this->assignedUsers->map(fn ($u) => $u->name ?? $u->email ?? ('User #'.$u->id))->all()
            : [];

        if (empty($names) && ($this->relationLoaded('assignee') ? $this->assignee : $this->assignee()->exists())) {
            $u = $this->assignee;
            if ($u) {
                $names = [$u->name ?? $u->email ?? ('User #'.$u->id)];
            }
        }

        return implode(', ', $names);
    }

    public function isRequester(User $user): bool
    {
        $requesterId = $this->requester_id ?? ($this->attributes['requester_id'] ?? null);
        if (! $requesterId && array_key_exists('created_by', $this->attributes)) {
            $requesterId = $this->attributes['created_by'];
        }

        return $requesterId && (int) $requesterId === (int) $user->id;
    }

    public function isAgent(User $user): bool
    {
        $userId = (int) $user->id;
        if ($this->agent_id && (int) $this->agent_id === $userId) {
            return true;
        }
        if ($this->assigned_id && (int) $this->assigned_id === $userId) {
            return true;
        }

        if ($this->relationLoaded('assignedUsers')) {
            return $this->assignedUsers->contains(fn ($u) => (int) $u->id === $userId);
        }

        return $this->assignedUsers()->where('user_id', $userId)->exists();
    }

    public function canUserSetStatus(User $user, string $status): bool
    {
        $status = WorkflowStatus::normalize($status);

        if ($this->userHasStatusOverride($user)) {
            return true;
        }

        if (in_array($status, WorkflowStatus::agentAllowed(), true)) {
            return $this->isAgent($user);
        }

        if (in_array($status, WorkflowStatus::requesterAllowed(), true)) {
            return $this->isRequester($user);
        }

        if ($status === WorkflowStatus::NEW) {
            return false;
        }

        return false;
    }

    private function userHasStatusOverride(User $user): bool
    {
        return RoleHelpers::userIsSuperAdmin($user);
    }

    protected function status(): Attribute
    {
        return Attribute::make(
            get: fn () => WorkflowStatus::normalize($this->attributes['status'] ?? null),
            set: function ($value) {
                $normalized = WorkflowStatus::normalize($value);
                $this->attributes['status_id'] = WorkflowStatus::code($normalized);

                return ['status' => $normalized];
            }
        );
    }
}
