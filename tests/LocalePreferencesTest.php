<?php

use App\Models\AppSetting;
use App\Models\User;
use App\Support\UserLocale;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
        throw new RuntimeException('Locale tests require isolated SQLite memory.');
    }
    Artisan::call('migrate:fresh', ['--path' => [
        'database/migrations/0001_01_01_000000_create_users_table.php',
        'database/migrations/2025_07_23_023014_create_permission_tables.php',
        'database/migrations/2025_11_19_000001_add_unit_to_users_table.php',
        'database/migrations/2026_10_12_000001_add_preferred_locale_to_users_table.php',
    ], '--force' => true]);
    Schema::create('app_settings', function ($table) {
        $table->id(); $table->string('group'); $table->string('key'); $table->text('value')->nullable();
        $table->string('type')->nullable(); $table->string('cast_type')->nullable();
        $table->boolean('is_secret')->default(false); $table->unsignedBigInteger('updated_by')->nullable(); $table->timestamps();
        $table->unique(['group', 'key']);
    });
    Schema::create('audit_logs', function ($table) {
        $table->id(); $table->unsignedBigInteger('user_id')->nullable(); $table->string('action');
        foreach (['group', 'key', 'old_value', 'new_value', 'ip_address', 'user_agent'] as $column) $table->text($column)->nullable();
        $table->timestamps();
    });
    localeDefault('en');
});

function localeDefault(string $locale): void
{
    AppSetting::updateOrCreate(['group' => 'general', 'key' => 'locale'], ['value' => $locale]);
    Cache::flush();
}

function localeHeaders(): array
{
    return ['X-Inertia' => 'true', 'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json'))];
}

test('public pages follow the global language including form and script labels', function (string $locale) {
    localeDefault($locale);
    $this->withoutVite();
    $english = $locale === 'en';
    foreach (['welcome', 'login', 'register'] as $page) {
        $response = $this->get('/'.$locale.'/'.$page)->assertOk()
            ->assertSee('lang="'.$locale.'"', false);
        $response->assertSee($page === 'register'
            ? ($english ? 'Create a new account' : 'Daftar Akun Baru')
            : ($english ? 'Sign in to your account' : 'Masuk ke akun Anda'));
        if ($page === 'register') {
            $response->assertSee($english ? 'First name' : 'Nama Depan')
                ->assertSee($english ? 'Select unit' : 'Pilih Unit')
                ->assertSee($english ? 'No units found' : 'Unit tidak ditemukan')
                ->assertSee($english ? 'Please select a unit.' : 'Unit wajib dipilih.');
        }
        if ($page !== 'welcome') {
            $response->assertSee($english ? 'Show password' : 'Tampilkan password')
                ->assertSee(route('welcome', ['locale' => $locale]), false);
        }
        if ($page === 'login') {
            $response->assertSee($english ? 'Signing in, please wait...' : 'Sedang masuk, mohon tunggu...')
                ->assertSee($english ? 'We are preparing your Tickora dashboard.' : 'Kami sedang menyiapkan dasbor Tickora Anda.');
        }
    }
})->with(['en', 'id']);

test('language belongs only to the authenticated account and survives a new session', function () {
    $alice = User::factory()->create(); $bob = User::factory()->create();
    $this->actingAs($alice)->patchJson('/en/dashboard/account/locale', ['locale' => 'id', 'user_id' => $bob->id, 'globalLocale' => 'id'])
        ->assertOk()->assertJsonPath('locale', 'id');
    expect($alice->fresh()->preferred_locale)->toBe('id')->and($bob->fresh()->preferred_locale)->toBeNull()
        ->and(UserLocale::globalDefault())->toBe('en');
    $this->flushSession();
    $this->actingAs($alice->fresh())->withHeaders(localeHeaders())->get('/en/dashboard/account/profile')
        ->assertOk()->assertJsonPath('props.locale', 'id')->assertJsonPath('props.auth.user.preferred_locale', 'id');
    $this->actingAs($bob->fresh())->withHeaders(localeHeaders())->get('/id/dashboard/account/profile')
        ->assertOk()->assertJsonPath('props.locale', 'en')->assertJsonPath('props.auth.user.preferred_locale', null);
});

test('global defaults affect only accounts without a personal preference', function () {
    $alice = User::factory()->create(); $bob = User::factory()->create();
    $this->actingAs($alice)->patchJson('/en/dashboard/account/locale', ['locale' => 'en'])->assertOk();
    localeDefault('id');
    expect(UserLocale::resolve($alice->fresh()))->toBe('en')->and(UserLocale::resolve($bob->fresh()))->toBe('id');
    $this->actingAs($alice->fresh())->patchJson('/en/dashboard/account/locale', ['locale' => null])
        ->assertOk()->assertJsonPath('locale', 'id')->assertJsonPath('preferred_locale', null);
    expect($alice->fresh()->preferred_locale)->toBeNull();
});

test('guests and invalid locales cannot write a personal preference', function () {
    $this->patchJson('/en/dashboard/account/locale', ['locale' => 'id'])->assertUnauthorized();
    $user = User::factory()->create();
    foreach ([[], ['locale' => 'fr'], ['locale' => ['en']], ['locale' => 'EN']] as $payload) {
        $this->actingAs($user)->patchJson('/en/dashboard/account/locale', $payload)->assertUnprocessable()->assertJsonValidationErrors('locale');
    }
    expect($user->fresh()->preferred_locale)->toBeNull();
});

test('only superadmin can change the global default and their personal choice is preserved', function () {
    foreach (['user', 'admin', 'superadmin'] as $name) Role::findOrCreate($name, 'web');
    foreach (['user', 'admin'] as $role) {
        $user = User::factory()->create(); $user->assignRole($role);
        $this->actingAs($user)->postJson('/en/dashboard/settings/general', ['locale' => 'id'])->assertForbidden();
    }
    expect(UserLocale::globalDefault())->toBe('en');
    $superadmin = User::factory()->create(); $superadmin->assignRole('superadmin');
    $superadmin->forceFill(['preferred_locale' => 'en'])->save();
    $payload = array_merge(app(\App\Services\SettingsService::class)->getGroup('general'), ['locale' => 'id']);
    $this->actingAs($superadmin)->post('/en/dashboard/settings/general', $payload)->assertSessionHasNoErrors()->assertRedirect('/en/dashboard/settings');
    expect(UserLocale::globalDefault())->toBe('id')->and($superadmin->fresh()->preferred_locale)->toBe('en');
    $this->actingAs($superadmin->fresh())->withHeaders(localeHeaders())->get('/en/dashboard/settings')
        ->assertOk()->assertJsonPath('props.locale', 'en')->assertJsonPath('props.settings.general.locale', 'id');
});

test('workflow system messages and default stages follow the selected locale without rewriting custom content', function () {
    $workflow = new \App\Models\Workflow(['code' => 'TICKET_DEFAULT']);
    app()->setLocale('en');
    expect(__('Workflow sedang nonaktif.'))->toBe('This workflow is inactive.')
        ->and(\App\Support\WorkflowDisplay::text($workflow, 'Revisi'))->toBe('Revision')
        ->and(\App\Support\WorkflowStatus::label('in_progress'))->toBe('In Progress');
    $workflow->code = 'CUSTOM';
    expect(\App\Support\WorkflowDisplay::text($workflow, 'Revisi'))->toBe('Revisi');
    app()->setLocale('id');
    expect(\App\Support\WorkflowStatus::label('confirmation'))->toBe('Konfirmasi');
});

test('existing notifications translate for each viewer while preserving the work title', function () {
    $original = 'Ticket "Belajar Hardware" telah dibuat.';
    app()->setLocale('en');
    expect(\App\Support\NotificationText::display($original))->toBe('Ticket "Belajar Hardware" was created.');
    expect(\App\Support\NotificationText::display('Ticket berisi Task'))->toBe('Ticket contains Task')
        ->and(\App\Support\NotificationText::display('Ticket "Belajar Hardware" memuat Task.'))->toBe('Ticket "Belajar Hardware" contains Task.');
    app()->setLocale('id');
    expect(\App\Support\NotificationText::display($original))->toBe($original)
        ->and(\App\Support\NotificationText::display('Custom message: Belajar Hardware'))->toBe('Custom message: Belajar Hardware');
});
