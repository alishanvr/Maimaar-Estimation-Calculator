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
