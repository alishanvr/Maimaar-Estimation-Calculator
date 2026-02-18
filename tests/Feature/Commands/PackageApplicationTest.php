<?php

use function Pest\Laravel\artisan;

afterEach(function () {
    // Clean up any generated zip files
    $files = glob(storage_path('app/maimaar-*.zip'));
    foreach ($files as $file) {
        unlink($file);
    }
});

it('creates a zip file with --skip-build', function () {
    artisan('app:package', ['--skip-build' => true])
        ->assertSuccessful();

    $files = glob(storage_path('app/maimaar-*.zip'));

    expect($files)->not->toBeEmpty();
    expect(filesize($files[0]))->toBeGreaterThan(0);
});

it('excludes sensitive and dev files from the zip', function () {
    artisan('app:package', ['--skip-build' => true])
        ->assertSuccessful();

    $files = glob(storage_path('app/maimaar-*.zip'));
    $entries = getZipEntries($files[0]);

    // Sensitive files must not be present
    expect($entries)->not->toContain('.env');
    expect($entries)->not->toContain('.env.production');
    expect($entries)->not->toContain('.env.backup');

    // Dev directories must not be present
    $devDirs = ['vendor/', '.git/', 'documentations/', 'tests/', '.idea/', '.cursor/', '.claude/'];
    foreach ($devDirs as $dir) {
        $found = array_filter($entries, fn ($e) => str_starts_with($e, $dir));
        expect($found)->toBeEmpty("Expected '{$dir}' to be excluded from zip");
    }

    // No markdown files
    $mdFiles = array_filter($entries, fn ($e) => str_ends_with($e, '.md'));
    expect($mdFiles)->toBeEmpty('Expected .md files to be excluded from zip');

    // No node_modules anywhere
    $nodeModules = array_filter($entries, fn ($e) => str_contains($e, 'node_modules'));
    expect($nodeModules)->toBeEmpty('Expected node_modules to be excluded from zip');

    // No installed marker file
    $installedFiles = array_filter($entries, fn ($e) => str_ends_with($e, '/installed') || $e === 'installed');
    expect($installedFiles)->toBeEmpty('Expected installed marker file to be excluded from zip');

    // No SQLite database files
    $sqliteFiles = array_filter($entries, fn ($e) => str_ends_with($e, '.sqlite'));
    expect($sqliteFiles)->toBeEmpty('Expected .sqlite files to be excluded from zip');

    // No zip files
    $zipFiles = array_filter($entries, fn ($e) => str_ends_with($e, '.zip'));
    expect($zipFiles)->toBeEmpty('Expected .zip files to be excluded from zip');

    // No user-uploaded runtime data
    $uploadedSettings = array_filter($entries, fn ($e) => str_starts_with($e, 'storage/app/public/app-settings/') || str_starts_with($e, 'storage/app/public/pdf-settings/'));
    expect($uploadedSettings)->toBeEmpty('Expected user-uploaded settings files to be excluded from zip');
});

it('includes essential application files', function () {
    artisan('app:package', ['--skip-build' => true])
        ->assertSuccessful();

    $files = glob(storage_path('app/maimaar-*.zip'));
    $entries = getZipEntries($files[0]);

    expect($entries)->toContain('artisan');
    expect($entries)->toContain('composer.json');
    expect($entries)->toContain('composer.lock');
    expect($entries)->toContain('.env.example');
    expect($entries)->toContain('package.json');
    expect($entries)->toContain('vite.config.js');

    // App directories should be present
    $appFiles = array_filter($entries, fn ($e) => str_starts_with($e, 'app/'));
    expect($appFiles)->not->toBeEmpty();

    $configFiles = array_filter($entries, fn ($e) => str_starts_with($e, 'config/'));
    expect($configFiles)->not->toBeEmpty();

    $databaseFiles = array_filter($entries, fn ($e) => str_starts_with($e, 'database/'));
    expect($databaseFiles)->not->toBeEmpty();
});

it('includes storage directory structure without logs', function () {
    artisan('app:package', ['--skip-build' => true])
        ->assertSuccessful();

    $files = glob(storage_path('app/maimaar-*.zip'));
    $entries = getZipEntries($files[0]);

    // Storage structure directories should exist
    $storageDirs = array_filter($entries, fn ($e) => str_starts_with($e, 'storage/'));
    expect($storageDirs)->not->toBeEmpty();

    // Log files should not be present
    $logFiles = array_filter($entries, fn ($e) => str_ends_with($e, '.log'));
    expect($logFiles)->toBeEmpty('Expected .log files to be excluded from zip');
});

it('fails gracefully with invalid output directory', function () {
    artisan('app:package', [
        '--skip-build' => true,
        '--output' => '/nonexistent/path',
    ])->assertFailed();
});

/**
 * Extract all entry names from a zip file.
 *
 * @return array<int, string>
 */
function getZipEntries(string $zipPath): array
{
    $zip = new ZipArchive;
    $zip->open($zipPath);

    $entries = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $entries[] = $zip->getNameIndex($i);
    }

    $zip->close();

    return $entries;
}
