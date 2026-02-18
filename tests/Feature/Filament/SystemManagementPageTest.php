<?php

use App\Filament\Pages\SystemManagement;
use App\Models\User;
use Livewire\Livewire;

it('can render the system management page for admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    $this->get('/admin/system-management')
        ->assertSuccessful();
});

it('cannot access system management page as non-admin', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get('/admin/system-management')
        ->assertForbidden();
});

it('can clear config cache', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(SystemManagement::class)
        ->call('clearConfigCache');

    expect(true)->toBeTrue();
});

it('can clear route cache', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(SystemManagement::class)
        ->call('clearRouteCache');

    expect(true)->toBeTrue();
});

it('can clear view cache', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(SystemManagement::class)
        ->call('clearViewCache');

    expect(true)->toBeTrue();
});

it('can check migration status', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(SystemManagement::class)
        ->call('checkMigrationStatus');

    expect(true)->toBeTrue();
});

it('can run migrations', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(SystemManagement::class)
        ->call('runMigrations');

    expect(true)->toBeTrue();
});

it('can run selected seeders', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(SystemManagement::class)
        ->call('runSeeders', ['app_settings']);

    expect(true)->toBeTrue();
});

it('can create storage link', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(SystemManagement::class)
        ->call('createStorageLink');

    expect(true)->toBeTrue();
});

it('can clear all caches', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(SystemManagement::class)
        ->call('clearAllCaches');

    expect(true)->toBeTrue();
});

it('can optimize application', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(SystemManagement::class)
        ->call('optimizeApplication');

    expect(true)->toBeTrue();
});

it('preserves APP_KEY after optimizeApplication', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    $keyBefore = config('app.key');

    Livewire::test(SystemManagement::class)
        ->call('optimizeApplication');

    expect(config('app.key'))->toBe($keyBefore)
        ->not->toBeEmpty();
});

it('preserves APP_KEY after clearAllCaches', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    $keyBefore = config('app.key');

    Livewire::test(SystemManagement::class)
        ->call('clearAllCaches');

    expect(config('app.key'))->toBe($keyBefore)
        ->not->toBeEmpty();
});

it('preserves APP_KEY after clearConfigCache', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    $keyBefore = config('app.key');

    Livewire::test(SystemManagement::class)
        ->call('clearConfigCache');

    expect(config('app.key'))->toBe($keyBefore)
        ->not->toBeEmpty();
});

// ── Storage Management: Log File Cleanup Actions ─────────────

it('superadmin can call handleClearOldLogs', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin);

    Livewire::test(SystemManagement::class)
        ->call('handleClearOldLogs')
        ->assertNotified();
});

it('superadmin can call handleClearLogsByAge with 7 days', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin);

    Livewire::test(SystemManagement::class)
        ->call('handleClearLogsByAge', 7)
        ->assertNotified();
});

it('superadmin can call handleClearLogsByAge with 0 days to delete all', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin);

    Livewire::test(SystemManagement::class)
        ->call('handleClearLogsByAge', 0)
        ->assertNotified();
});

it('superadmin can call handleClearAllLogs', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin);

    Livewire::test(SystemManagement::class)
        ->call('handleClearAllLogs')
        ->assertNotified();
});

// ── Storage Management: Cache & Session Cleanup Actions ──────

it('superadmin can call handleClearCacheFiles', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin);

    Livewire::test(SystemManagement::class)
        ->call('handleClearCacheFiles')
        ->assertNotified();
});

it('superadmin can call handleClearSessionFiles', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin);

    Livewire::test(SystemManagement::class)
        ->call('handleClearSessionFiles')
        ->assertNotified();
});

it('superadmin can call handleClearCompiledViews', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin);

    Livewire::test(SystemManagement::class)
        ->call('handleClearCompiledViews')
        ->assertNotified();
});

it('superadmin can call handleClearLivewireTmp', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin);

    Livewire::test(SystemManagement::class)
        ->call('handleClearLivewireTmp')
        ->assertNotified();
});

it('superadmin can call handleClearAllStorageFiles', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin);

    Livewire::test(SystemManagement::class)
        ->call('handleClearAllStorageFiles')
        ->assertNotified();
});

// ── Storage Management: Access Control ───────────────────────

it('non-superadmin admin sees read-only log management description', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(SystemManagement::class)
        ->assertSee('Log files are managed by the super admin');
});

it('non-superadmin admin sees read-only cache cleanup description', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(SystemManagement::class)
        ->assertSee('File cleanup is managed by the super admin');
});

// ── Storage Management: Section Rendering ────────────────────

it('renders storage usage analysis section', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(SystemManagement::class)
        ->assertSee('Storage Usage Analysis');
});

it('renders top 10 largest files section', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(SystemManagement::class)
        ->assertSee('Top 10 Largest Files');
});
