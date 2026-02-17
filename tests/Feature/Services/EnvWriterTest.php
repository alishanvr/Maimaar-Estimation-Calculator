<?php

use App\Services\EnvWriter;

beforeEach(function () {
    // Create a temporary .env file for testing
    $this->testEnvPath = base_path('.env.test-writer');
    file_put_contents($this->testEnvPath, "APP_NAME=TestApp\nAPP_ENV=local\nDB_HOST=127.0.0.1\n");
});

afterEach(function () {
    if (file_exists($this->testEnvPath)) {
        unlink($this->testEnvPath);
    }
});

it('sets a new env value', function () {
    // We test the format logic by directly calling the writer on the real env
    // Use a unique key that won't conflict
    $writer = app(EnvWriter::class);

    $envPath = base_path('.env');
    $originalContent = file_get_contents($envPath);

    $writer->set('TEST_INSTALLER_KEY', 'test_value');

    $content = file_get_contents($envPath);
    expect($content)->toContain('TEST_INSTALLER_KEY=test_value');

    // Cleanup: restore original
    file_put_contents($envPath, $originalContent, LOCK_EX);
});

it('updates an existing env value', function () {
    $writer = app(EnvWriter::class);

    $envPath = base_path('.env');
    $originalContent = file_get_contents($envPath);

    // First add the key
    $writer->set('TEST_UPDATE_KEY', 'original');
    expect(file_get_contents($envPath))->toContain('TEST_UPDATE_KEY=original');

    // Now update it
    $writer->set('TEST_UPDATE_KEY', 'updated');
    $content = file_get_contents($envPath);
    expect($content)->toContain('TEST_UPDATE_KEY=updated');
    expect($content)->not->toContain('TEST_UPDATE_KEY=original');

    // Cleanup
    file_put_contents($envPath, $originalContent, LOCK_EX);
});

it('handles values with spaces by quoting', function () {
    $writer = app(EnvWriter::class);

    $envPath = base_path('.env');
    $originalContent = file_get_contents($envPath);

    $writer->set('TEST_SPACE_KEY', 'value with spaces');

    $content = file_get_contents($envPath);
    expect($content)->toContain('TEST_SPACE_KEY="value with spaces"');

    // Cleanup
    file_put_contents($envPath, $originalContent, LOCK_EX);
});

it('handles empty values', function () {
    $writer = app(EnvWriter::class);

    $envPath = base_path('.env');
    $originalContent = file_get_contents($envPath);

    $writer->set('TEST_EMPTY_KEY', '');

    $content = file_get_contents($envPath);
    expect($content)->toContain('TEST_EMPTY_KEY=');

    // Cleanup
    file_put_contents($envPath, $originalContent, LOCK_EX);
});

it('sets multiple values at once', function () {
    $writer = app(EnvWriter::class);

    $envPath = base_path('.env');
    $originalContent = file_get_contents($envPath);

    $writer->setMany([
        'TEST_MULTI_A' => 'value_a',
        'TEST_MULTI_B' => 'value_b',
        'TEST_MULTI_C' => 'value with spaces',
    ]);

    $content = file_get_contents($envPath);
    expect($content)->toContain('TEST_MULTI_A=value_a');
    expect($content)->toContain('TEST_MULTI_B=value_b');
    expect($content)->toContain('TEST_MULTI_C="value with spaces"');

    // Cleanup
    file_put_contents($envPath, $originalContent, LOCK_EX);
});
