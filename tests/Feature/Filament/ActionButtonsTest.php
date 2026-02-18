<?php

/**
 * Tests that all action buttons/handlers on SystemManagement and
 * EnvironmentSettings pages can be invoked without error.
 *
 * SystemManagement uses section footer actions which are tested via
 * direct Livewire ->call() on the public handler methods.
 *
 * EnvironmentSettings uses page-level header actions which are tested
 * via Filament's ->callAction().
 */

use App\Filament\Pages\EnvironmentSettings;
use App\Filament\Pages\SystemManagement;
use App\Models\User;
use Livewire\Livewire;

// ── SystemManagement: Log File Actions ───────────────────────

it('clear old logs action handler works', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin);

    Livewire::test(SystemManagement::class)
        ->call('handleClearOldLogs')
        ->assertNotified();
});

it('clear logs by age action handler works with 7 days', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin);

    Livewire::test(SystemManagement::class)
        ->call('handleClearLogsByAge', 7)
        ->assertNotified();
});

it('clear all logs action handler works', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin);

    Livewire::test(SystemManagement::class)
        ->call('handleClearAllLogs')
        ->assertNotified();
});

// ── SystemManagement: Cache & Session Cleanup Actions ────────

it('clear cache files action handler works', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin);

    Livewire::test(SystemManagement::class)
        ->call('handleClearCacheFiles')
        ->assertNotified();
});

it('clear session files action handler works', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin);

    Livewire::test(SystemManagement::class)
        ->call('handleClearSessionFiles')
        ->assertNotified();
});

it('clear compiled views action handler works', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin);

    Livewire::test(SystemManagement::class)
        ->call('handleClearCompiledViews')
        ->assertNotified();
});

it('clear livewire temp action handler works', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin);

    Livewire::test(SystemManagement::class)
        ->call('handleClearLivewireTmp')
        ->assertNotified();
});

it('clear all storage files action handler works', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin);

    Livewire::test(SystemManagement::class)
        ->call('handleClearAllStorageFiles')
        ->assertNotified();
});

// ── SystemManagement: Cache Management Actions ───────────────

it('clear application cache action handler works', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin);

    Livewire::test(SystemManagement::class)
        ->call('clearApplicationCache')
        ->assertNotified();
});

it('clear config cache action handler works', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin);

    Livewire::test(SystemManagement::class)
        ->call('clearConfigCache')
        ->assertNotified();
});

it('clear route cache action handler works', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin);

    Livewire::test(SystemManagement::class)
        ->call('clearRouteCache')
        ->assertNotified();
});

it('clear view cache action handler works', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin);

    Livewire::test(SystemManagement::class)
        ->call('clearViewCache')
        ->assertNotified();
});

it('clear all caches action handler works', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin);

    Livewire::test(SystemManagement::class)
        ->call('clearAllCaches')
        ->assertNotified();
});

// ── SystemManagement: Optimize ───────────────────────────────

it('optimize application action handler works', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin);

    Livewire::test(SystemManagement::class)
        ->call('optimizeApplication')
        ->assertNotified();
});

// ── SystemManagement: Migration Actions ──────────────────────

it('check migration status action handler works', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin);

    Livewire::test(SystemManagement::class)
        ->call('checkMigrationStatus')
        ->assertNotified();
});

it('run migrations action handler works', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin);

    Livewire::test(SystemManagement::class)
        ->call('runMigrations')
        ->assertNotified();
});

// ── SystemManagement: Seeder Actions ─────────────────────────

it('run seeders action handler works with app_settings', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin);

    Livewire::test(SystemManagement::class)
        ->call('runSeeders', ['app_settings'])
        ->assertNotified();
});

// ── SystemManagement: Storage Link ───────────────────────────

it('create storage link action handler works', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin);

    Livewire::test(SystemManagement::class)
        ->call('createStorageLink')
        ->assertNotified();
});

// ── EnvironmentSettings: Header Actions (page-level) ────────

it('test_database header action can be triggered by superadmin', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin);

    Livewire::test(EnvironmentSettings::class)
        ->callAction('test_database')
        ->assertNotified();
});

it('migrate_database header action warns on same driver', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin);

    Livewire::test(EnvironmentSettings::class)
        ->callAction('migrate_database')
        ->assertNotified('Same database driver');
});
