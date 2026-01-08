<?php

namespace App\Models;

use App\Support\RoleHelpers;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string|null $superadmin_guard
 * @property string $username
 * @property string $first_name
 * @property string|null $last_name
 * @property string|null $position
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property string|null $verification_token
 * @property string|null $phone_wa
 * @property string|null $photo
 * @property string|null $ip_session
 * @property string|null $last_access
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Ticket> $assignedTickets
 * @property-read int|null $assigned_tickets_count
 * @property-read string $display_name
 * @property-read string $label
 * @property-read string $name
 * @property-read string $role_label
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 *
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIpSession($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastAccess($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePhoneWa($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereSuperadminGuard($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUsername($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereVerificationToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutRole($roles, $guard = null)
 *
 * @mixin \Eloquent
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, HasRoles, Notifiable {
        HasRoles::assignRole as protected traitAssignRole;
        HasRoles::syncRoles as protected traitSyncRoles;
    }
    use MustVerifyEmailTrait;

    /** Spatie Permission guard */
    protected $guard_name = 'web';

    /** Mass assignable */
    protected $fillable = [
        'username',
        'first_name',
        'last_name',
        'email',
        'password',
        'unit',
        'locale',
    ];

    /** Hidden */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** Casts */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed', // otomatis hash saat set string
    ];

    /** Display-friendly name for UI: first+last -> username -> email */
    public function getDisplayNameAttribute(): string
    {
        $full = trim(($this->first_name ?? '').' '.($this->last_name ?? ''));
        if ($full !== '') {
            return $full;
        }
        if (! empty($this->username)) {
            return (string) $this->username;
        }

        return (string) $this->email;
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new \App\Notifications\VerifyEmailNotification());
    }

    /** Backward-compat: $user->name */
    public function getNameAttribute(): string
    {
        return $this->display_name;
    }

    /** Convenience: $user->label */
    public function getLabelAttribute(): string
    {
        return $this->display_name;
    }

    /** Label role gabungan (untuk pencarian/tampilan cepat) */
    public function getRoleLabelAttribute(): string
    {
        $names = $this->relationLoaded('roles')
            ? $this->roles->pluck('name')
            : $this->getRoleNames();

        return $names->isNotEmpty() ? $names->implode(', ') : '—';
    }

    /** Relasi many-to-many ke Ticket melalui pivot ticket_assignees. */
    public function assignedTickets()
    {
        return $this->belongsToMany(\App\Models\Ticket::class, 'ticket_assignees')
            ->withTimestamps();
    }

    public function assignRole(...$roles)
    {
        $result = $this->traitAssignRole(...$roles);
        $this->refreshLegacyRoleColumn();

        return $result;
    }

    public function syncRoles(...$roles)
    {
        $result = $this->traitSyncRoles(...$roles);
        $this->refreshLegacyRoleColumn();

        return $result;
    }

    protected function refreshLegacyRoleColumn(): void
    {
        if (! $this->exists) {
            return;
        }

        if (! Schema::hasColumn($this->getTable(), 'role')) {
            return;
        }

        $primary = $this->getRoleNames()->first();
        $canonical = $primary ? (RoleHelpers::canonical($primary) ?? $primary) : null;

        if ($this->role !== $canonical) {
            $this->forceFill(['role' => $canonical])->saveQuietly();
        }
    }
}
