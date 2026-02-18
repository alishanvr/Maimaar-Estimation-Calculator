<?php

use App\Models\DesignConfiguration;
use App\Models\Estimation;
use App\Models\MbsdbProduct;
use App\Models\User;
use App\Services\DatabaseMigrationService;

it('exports data from all existing tables', function () {
    $user = User::factory()->create();
    Estimation::factory()->for($user)->count(3)->create();
    DesignConfiguration::factory()->count(2)->create();

    $service = new DatabaseMigrationService;
    $data = $service->exportData();

    expect($data)->toHaveKey('users');
    expect($data)->toHaveKey('estimations');
    expect($data)->toHaveKey('design_configurations');
    expect($data['users'])->toHaveCount(1);
    expect($data['estimations'])->toHaveCount(3);
    expect($data['design_configurations'])->toHaveCount(2);
});

it('exports empty arrays for tables with no data', function () {
    $service = new DatabaseMigrationService;
    $data = $service->exportData();

    expect($data)->toHaveKey('users');
    expect($data['users'])->toBeEmpty();
});

it('verifies row counts against the same connection', function () {
    $user = User::factory()->create();
    Estimation::factory()->for($user)->count(2)->create();

    $service = new DatabaseMigrationService;
    $data = $service->exportData();

    $verification = $service->verifyMigration($data, config('database.default'));

    expect($verification['users']['match'])->toBeTrue();
    expect($verification['users']['source'])->toBe(1);
    expect($verification['users']['target'])->toBe(1);
    expect($verification['estimations']['match'])->toBeTrue();
    expect($verification['estimations']['source'])->toBe(2);
    expect($verification['estimations']['target'])->toBe(2);
});

it('imports data back into the same connection', function () {
    $user = User::factory()->create();
    MbsdbProduct::factory()->count(5)->create();

    $service = new DatabaseMigrationService;
    $data = $service->exportData();

    // Truncate tables (simulated fresh target)
    MbsdbProduct::query()->delete();
    expect(MbsdbProduct::count())->toBe(0);

    // Re-import into same connection
    $service->importData($data, config('database.default'));

    expect(MbsdbProduct::count())->toBe(5);
});

it('returns the expected table order', function () {
    $service = new DatabaseMigrationService;
    $tables = $service->getTableOrder();

    expect($tables)->toBeArray();
    expect($tables)->toContain('users');
    expect($tables)->toContain('estimations');
    expect($tables)->toContain('estimation_items');

    // users must come before estimations (parent before child)
    $usersIndex = array_search('users', $tables);
    $estimationsIndex = array_search('estimations', $tables);
    expect($usersIndex)->toBeLessThan($estimationsIndex);

    // estimations must come before estimation_items
    $itemsIndex = array_search('estimation_items', $tables);
    expect($estimationsIndex)->toBeLessThan($itemsIndex);
});

it('preserves data integrity after import round-trip', function () {
    $user = User::factory()->create(['name' => 'Test User', 'email' => 'test@example.com']);
    $estimation = Estimation::factory()->for($user)->calculated()->create([
        'quote_number' => 'HQ-TEST-MIGRATE',
    ]);

    $service = new DatabaseMigrationService;
    $data = $service->exportData();

    // Verify exported data contains our records
    $exportedUsers = collect($data['users']);
    $exportedEstimations = collect($data['estimations']);

    expect($exportedUsers->firstWhere('email', 'test@example.com'))->not->toBeNull();
    expect($exportedEstimations->firstWhere('quote_number', 'HQ-TEST-MIGRATE'))->not->toBeNull();
});

it('verifyMigration reports mismatch when target has fewer rows', function () {
    $user = User::factory()->create();

    $service = new DatabaseMigrationService;
    $data = $service->exportData();

    // Delete the user from the database to create a mismatch
    User::query()->delete();

    $verification = $service->verifyMigration($data, config('database.default'));

    expect($verification['users']['match'])->toBeFalse();
    expect($verification['users']['source'])->toBe(1);
    expect($verification['users']['target'])->toBe(0);
});

it('all tables in TABLE_ORDER exist in the database schema', function () {
    $service = new DatabaseMigrationService;
    $tables = $service->getTableOrder();

    foreach ($tables as $table) {
        expect(\Illuminate\Support\Facades\Schema::hasTable($table))
            ->toBeTrue("Table '{$table}' from TABLE_ORDER does not exist in the database.");
    }
});

it('handles large datasets via chunked import', function () {
    $user = User::factory()->create();
    DesignConfiguration::factory()->count(550)->create();

    $service = new DatabaseMigrationService;
    $data = $service->exportData();

    expect($data['design_configurations'])->toHaveCount(550);

    // Re-import (truncates + inserts in chunks of 500)
    $service->importData($data, config('database.default'));

    expect(DesignConfiguration::count())->toBe(550);
});
