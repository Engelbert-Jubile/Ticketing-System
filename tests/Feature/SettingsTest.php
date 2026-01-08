<?php

use App\Models\AppSetting;
use App\Models\SettingsAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function seedSetting(string $group, string $key, $value, ?string $type = null): void
{
    AppSetting::updateOrCreate(
        ['group' => $group, 'key' => $key],
        [
            'value' => $value,
            'type' => $type,
            'cast_type' => $type,
            'is_secret' => false,
            'updated_by' => null,
        ]
    );

    Cache::forget('settings.group.'.$group);
}

test('non superadmin cannot access settings page', function () {
    $user = User::factory()->create();
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $user->assignRole('user');

    $this->actingAs($user)
        ->get('/dashboard/settings')
        ->assertForbidden();
});

test('superadmin can update general settings and audit log', function () {
    $user = User::factory()->create();
    Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
    $user->assignRole('superadmin');

    $payload = [
        'app_name' => 'Tickora Test',
        'timezone' => 'UTC',
        'date_format' => 'Y-m-d',
        'locale' => 'en',
        'default_page_size' => 25,
        'maintenance_enabled' => false,
        'maintenance_message' => null,
        'announcement_enabled' => false,
        'announcement_title' => null,
        'announcement_body' => null,
        'announcement_starts_at' => null,
        'announcement_ends_at' => null,
    ];

    $this->actingAs($user)
        ->post('/dashboard/settings/general', $payload)
        ->assertRedirect();

    $setting = AppSetting::query()
        ->where('group', 'general')
        ->where('key', 'app_name')
        ->first();

    expect($setting)->not->toBeNull();
    expect($setting->value)->toBe('Tickora Test');

    $audit = SettingsAuditLog::query()
        ->where('group', 'general')
        ->where('key', 'app_name')
        ->first();

    expect($audit)->not->toBeNull();
});

test('ip restrictions block non superadmin dashboard access', function () {
    config(['features.ip_restrictions' => true]);

    seedSetting('security', 'enforce_ip_restrictions', true, 'boolean');
    seedSetting('security', 'allow_superadmin_ip_bypass', false, 'boolean');
    seedSetting('security', 'ip_allowlist', ['10.0.0.1'], 'array');

    $user = User::factory()->create();
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $user->assignRole('user');

    $this->actingAs($user)
        ->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])
        ->get('/dashboard')
        ->assertForbidden();
});

test('superadmin can bypass ip restrictions when enabled', function () {
    config(['features.ip_restrictions' => true]);

    seedSetting('security', 'enforce_ip_restrictions', true, 'boolean');
    seedSetting('security', 'allow_superadmin_ip_bypass', true, 'boolean');
    seedSetting('security', 'ip_allowlist', ['10.0.0.1'], 'array');

    $user = User::factory()->create();
    Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
    $user->assignRole('superadmin');

    $this->actingAs($user)
        ->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])
        ->get('/dashboard')
        ->assertOk();
});

test('ip restrictions block login for non superadmin', function () {
    config(['features.ip_restrictions' => true]);

    seedSetting('security', 'enforce_ip_restrictions', true, 'boolean');
    seedSetting('security', 'ip_blocklist', ['10.0.0.2'], 'array');

    $user = User::factory()->create([
        'email' => 'user@kftd.co.id',
        'password' => 'password',
    ]);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $user->assignRole('user');

    $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])
        ->post('/login', [
            'email' => 'user@kftd.co.id',
            'password' => 'password',
        ])
        ->assertSessionHasErrors('email');
});

test('superadmin can update role permissions via rbac endpoint', function () {
    $user = User::factory()->create();
    Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
    $user->assignRole('superadmin');

    $role = Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);
    $permission = Permission::firstOrCreate(['name' => 'manage tickets', 'guard_name' => 'web']);

    $this->actingAs($user)
        ->put('/dashboard/settings/rbac/roles/'.$role->id, ['permissions' => [$permission->name]])
        ->assertOk();

    expect($role->fresh()->hasPermissionTo($permission->name))->toBeTrue();

    $audit = SettingsAuditLog::query()
        ->where('group', 'roles')
        ->where('key', $role->name)
        ->first();

    expect($audit)->not->toBeNull();
});

test('impersonation creates audit log entry', function () {
    config(['features.impersonation' => true]);

    seedSetting('security', 'allow_impersonation', true, 'boolean');

    $superadmin = User::factory()->create();
    Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
    $superadmin->assignRole('superadmin');

    $target = User::factory()->create();

    $this->actingAs($superadmin)
        ->post('/dashboard/settings/impersonate/'.$target->id)
        ->assertRedirect('/dashboard');

    $audit = SettingsAuditLog::query()
        ->where('action', 'impersonation.start')
        ->where('key', (string) $target->id)
        ->first();

    expect($audit)->not->toBeNull();
});

test('login still works when app_settings is empty', function () {
    if (Schema::hasTable('app_settings')) {
        DB::table('app_settings')->truncate();
    }

    $user = User::factory()->create([
        'email' => 'user@kftd.co.id',
        'password' => 'password',
    ]);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $user->assignRole('user');

    $response = $this->post('/login', [
        'email' => 'user@kftd.co.id',
        'password' => 'password',
    ]);

    $response->assertRedirect('/dashboard');
});
