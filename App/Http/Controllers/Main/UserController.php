<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\RoleHelpers;
use App\Support\SecurityPolicy;
use App\Support\UserUnitOptions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /** Tampilkan daftar user. */
    public function report(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $viewer = $request->user();

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'roles' => $this->normalizeArrayFilter($request->query('roles', [])),
            'per_page' => $this->resolvePerPage((int) $request->query('per_page', 15)),
        ];

        $users = User::query()
            ->with('roles:id,name')
            ->when($filters['q'], function (Builder $query) use ($filters) {
                $search = $filters['q'];
                $query->where(function (Builder $builder) use ($search) {
                    $builder->where('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereRaw("CONCAT(IFNULL(first_name,''),' ',IFNULL(last_name,'')) like ?", ["%{$search}%"]);
                });
            })
            ->when($filters['roles'], function (Builder $query) use ($filters) {
                $query->whereHas('roles', fn (Builder $roles) => $roles->whereIn('name', $filters['roles']));
            })
            ->orderBy('username')
            ->paginate($filters['per_page'])
            ->withQueryString();

        $users = $users->through(fn (User $user) => $this->transformUserSummary($user, $viewer));

        $roleOptions = Role::query()
            ->select(['name'])
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role) => [
                'value' => $role->name,
                'label' => strtoupper($role->name),
            ])->values();

        return Inertia::render('Users/Report', [
            'users' => $users,
            'filters' => [
                'q' => $filters['q'],
                'roles' => $filters['roles'],
                'per_page' => $filters['per_page'],
            ],
            'roles' => $roleOptions,
            'can' => [
                'create' => $viewer->can('create', User::class),
            ],
        ]);
    }

    /** Detail user. */
    public function show(Request $request, User $user): Response
    {
        $this->authorize('view', $user);
        $user->load('roles');

        return Inertia::render('Users/Show', [
            'user' => $this->transformUserDetail($user),
            'can' => [
                'update' => $request->user()->can('update', $user),
                'delete' => $request->user()->can('delete', $user),
            ],
            'meta' => [
                'backUrl' => $request->query('from'),
            ],
        ]);
    }

    /** Form create. */
    public function create(Request $request): Response
    {
        $this->authorize('create', User::class);

        $viewer = $request->user();
        $viewerIsSuper = RoleHelpers::userIsSuperAdmin($viewer);
        $allowed = $this->allowedRoleValues($viewer);

        return Inertia::render('Users/Create', [
            'roles' => $this->formatRoleOptions($allowed),
            'units' => UserUnitOptions::options(),
            'meta' => [
                'canAssignSuperadmin' => in_array('superadmin', $allowed, true),
                'unitRequired' => ! $viewerIsSuper,
            ],
        ]);
    }

    /** Simpan user baru. */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        if (! $request->filled('unit') || trim((string) $request->input('unit')) === '') {
            $request->merge(['unit' => null]);
        }

        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', SecurityPolicy::passwordRule()],
            'role' => ['required', 'string', Rule::in(['user', 'admin', 'superadmin'])],
            'unit' => ['nullable', 'string', 'max:120', Rule::in(UserUnitOptions::values())],
        ]);

        // Batas kewenangan pemilih role
        $actor = $request->user();
        $roleValue = RoleHelpers::canonical($validated['role']);
        $validated['role'] = $roleValue ?? $validated['role'];

        $actorIsSuper = RoleHelpers::userIsSuperAdmin($actor);
        if ($actorIsSuper) {
            $allowed = ['user', 'admin', 'superadmin'];
        } else {
            $allowed = ['user'];
        }
        if (! in_array($roleValue, $allowed, true)) {
            return back()->withErrors(['role' => 'Anda tidak berwenang memilih role tersebut.'])->withInput();
        }

        if (! $actorIsSuper && empty($validated['unit'])) {
            return back()->withErrors(['unit' => 'Unit wajib diisi untuk akun yang dibuat oleh Admin/User.'])->withInput();
        }

        // (Opsional) Pastikan hanya satu superadmin
        if ($roleValue === 'superadmin' && User::whereHas('roles', fn ($query) => $query->whereIn('name', ['superadmin', 'Super Admin']))->exists()) {
            return back()->withErrors(['role' => 'Hanya boleh ada satu superadmin.'])->withInput();
        }

        // password akan di-hash otomatis oleh cast 'hashed'
        $user = User::create([
            'username' => $validated['username'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'] ?? null,
            'email' => $validated['email'],
            'password' => $validated['password'], // plain; cast yang meng-hash
            'unit' => $validated['unit'] ?? null,
        ]);

        // Pastikan role ada & assign via Spatie
        Role::firstOrCreate(['name' => $validated['role'], 'guard_name' => 'web']);
        $this->authorize('assignRole', $user);
        $user->assignRole($validated['role']);

        return to_route('users.report')->with('success', 'User created successfully.');
    }

    /** Form edit. */
    public function edit(Request $request, User $user): Response
    {
        $this->authorize('update', $user);

        $user->load('roles');
        $viewer = $request->user();
        $viewerIsSuper = RoleHelpers::userIsSuperAdmin($viewer);
        $allowed = $this->allowedRoleValues($viewer, $user);

        \Log::info('users.edit.access', [
            'viewer_id' => $viewer?->id,
            'viewer_roles' => $viewer?->getRoleNames()?->toArray(),
            'target_id' => $user->id,
            'target_roles' => $user->getRoleNames()->toArray(),
            'allowed_roles' => $allowed,
        ]);

        return Inertia::render('Users/Edit', [
            'user' => $this->transformUserDetail($user),
            'roles' => $this->formatRoleOptions($allowed),
            'units' => UserUnitOptions::options(),
            'can' => [
                'delete' => $viewer->can('delete', $user),
            ],
            'meta' => [
                'from' => $request->query('from'),
                'unitRequired' => ! $viewerIsSuper,
            ],
        ]);
    }

    /** Simpan perubahan user. */
    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        if (! $request->filled('unit') || trim((string) $request->input('unit')) === '') {
            $request->merge(['unit' => null]);
        }

        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', SecurityPolicy::passwordRule()],
            'role' => ['required', 'string', Rule::in(['user', 'admin', 'superadmin'])],
            'unit' => ['nullable', 'string', 'max:120', Rule::in(UserUnitOptions::values())],
        ]);

        $actor = $request->user();
        $actorIsSuper = RoleHelpers::userIsSuperAdmin($actor);
        $roleValue = RoleHelpers::canonical($validated['role']);
        $validated['role'] = $roleValue ?? $validated['role'];

        if ($actorIsSuper) {
            $allowed = ['user', 'admin', 'superadmin'];
        } else {
            $allowed = $user->id === $actor->id ? ['admin'] : ['user'];
        }
        if (! in_array($roleValue, $allowed, true)) {
            return back()->withErrors(['role' => 'Anda tidak berwenang memilih role tersebut.'])->withInput();
        }

        if (! $actorIsSuper && empty($validated['unit'])) {
            return back()->withErrors(['unit' => 'Unit wajib diisi untuk akun yang dikelola Admin/User.'])->withInput();
        }

        if ($roleValue === 'superadmin') {
            $existsOther = User::whereHas('roles', fn ($query) => $query->whereIn('name', ['superadmin', 'Super Admin']))
                ->where('id', '!=', $user->id)
                ->exists();
            if ($existsOther) {
                return back()->withErrors(['role' => 'Sudah ada superadmin lain.'])->withInput();
            }
        }

        $user->fill([
            'username' => $validated['username'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'] ?? null,
            'email' => $validated['email'],
            'unit' => $validated['unit'] ?? null,
        ]);
        if (! empty($validated['password'])) {
            $user->password = $validated['password']; // auto-hash by cast
        }
        $user->save();

        // Sinkronisasi role Spatie
        Role::firstOrCreate(['name' => $validated['role'], 'guard_name' => 'web']);
        $this->authorize('assignRole', $user);
        $user->syncRoles([$validated['role']]);

        return to_route('users.report')->with('success', 'User updated successfully.');
    }

    /** Hapus user. */
    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        if (RoleHelpers::userIsSuperAdmin($user)) {
            return back()->withErrors(['delete' => 'Akun superadmin tidak dapat dihapus.']);
        }

        $user->delete();

        return to_route('users.report')->with('success', 'User deleted successfully.');
    }

    /**
     * @param  array<int,string>  $allowed
     * @return array<int,array{value:string,label:string}>
     */
    private function formatRoleOptions(array $allowed): array
    {
        $roles = RoleHelpers::canonicalizeList($allowed);

        return collect($roles)
            ->map(fn (string $role) => [
                'value' => $role,
                'label' => RoleHelpers::displayLabel($role),
            ])->values()->all();
    }

    private function allowedRoleValues(User $actor, ?User $subject = null): array
    {
        if (RoleHelpers::userIsSuperAdmin($actor)) {
            return ['superadmin', 'admin', 'user'];
        }

        if ($subject && $actor->is($subject)) {
            return ['admin'];
        }

        return ['user'];
    }

    private function normalizeArrayFilter($value): array
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }

        if ($value instanceof \Traversable) {
            $value = iterator_to_array($value);
        }

        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->map(fn ($item) => is_string($item) ? trim($item) : $item)
            ->filter(fn ($item) => is_string($item) && $item !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function resolvePerPage(int $perPage): int
    {
        return max(10, min($perPage ?: 15, 100));
    }

    private function transformUserSummary(User $user, User $viewer): array
    {
        $roles = $user->roles->map(function ($role) {
            $canonical = RoleHelpers::canonical($role->name) ?? $role->name;

            return [
                'name' => $canonical,
                'label' => RoleHelpers::displayLabel($role->name),
                'badge' => $this->roleBadgeClass($role->name),
            ];
        })->values()->all();

        return [
            'id' => $user->id,
            'username' => $user->username,
            'name' => trim($user->first_name.' '.($user->last_name ?? '')) ?: null,
            'email' => $user->email,
            'initials' => strtoupper(substr($user->username ?? $user->email, 0, 1)),
            'roles' => $roles,
            'unit' => $user->unit,
            'links' => [
                'show' => route('users.show', $user),
                'edit' => $viewer->can('update', $user) ? route('users.edit', $user) : null,
            ],
            'can' => [
                'update' => $viewer->can('update', $user),
                'delete' => $viewer->can('delete', $user),
            ],
        ];
    }

    private function transformUserDetail(User $user): array
    {
        $roles = $user->roles->pluck('name')->map(fn ($name) => [
            'name' => RoleHelpers::canonical($name) ?? $name,
            'label' => RoleHelpers::displayLabel($name),
            'badge' => $this->roleBadgeClass($name),
        ])->values();

        $rolesArray = $roles->all();
        $primaryRole = $rolesArray[0]['name'] ?? null;

        return [
            'id' => $user->id,
            'username' => $user->username,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'roles' => $rolesArray,
            'role' => $primaryRole,
            'unit' => $user->unit,
            'name' => trim($user->first_name.' '.($user->last_name ?? '')),
        ];
    }

    private function roleBadgeClass(?string $role): string
    {
        $normalized = RoleHelpers::normalize($role);

        return match ($normalized) {
            'superadmin' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-200',
            'admin' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-200',
            default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
        };
    }
}
