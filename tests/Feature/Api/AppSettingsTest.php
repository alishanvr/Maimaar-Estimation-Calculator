<?php

use App\Models\DesignConfiguration;

it('returns app settings without authentication', function () {
    $response = $this->getJson('/api/app-settings');

    $response->assertSuccessful();
});

it('returns correct json structure', function () {
    $response = $this->getJson('/api/app-settings');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'app_name',
            'company_name',
            'logo_url',
            'favicon_url',
            'primary_color',
            'enable_fill_test_data',
        ]);
});

it('returns defaults when no settings exist', function () {
    $response = $this->getJson('/api/app-settings');

    $response->assertSuccessful()
        ->assertJson([
            'app_name' => 'Maimaar Estimation Calculator',
            'company_name' => 'Maimaar',
            'logo_url' => null,
            'favicon_url' => null,
            'primary_color' => '#3B82F6',
            'enable_fill_test_data' => false,
        ]);
});

it('returns configured values from design configurations', function () {
    DesignConfiguration::query()->create([
        'category' => 'app_settings',
        'key' => 'app_name',
        'value' => 'My Custom App',
        'label' => 'App Name',
    ]);

    DesignConfiguration::query()->create([
        'category' => 'app_settings',
        'key' => 'company_name',
        'value' => 'Custom Co',
        'label' => 'Company Name',
    ]);

    DesignConfiguration::query()->create([
        'category' => 'app_settings',
        'key' => 'primary_color',
        'value' => '#ff0000',
        'label' => 'Primary Color',
    ]);

    $response = $this->getJson('/api/app-settings');

    $response->assertSuccessful()
        ->assertJson([
            'app_name' => 'My Custom App',
            'company_name' => 'Custom Co',
            'primary_color' => '#ff0000',
        ]);
});

it('returns null for logo and favicon when no file uploaded', function () {
    DesignConfiguration::query()->create([
        'category' => 'app_settings',
        'key' => 'app_logo_path',
        'value' => '',
        'label' => 'App Logo',
    ]);

    DesignConfiguration::query()->create([
        'category' => 'app_settings',
        'key' => 'favicon_path',
        'value' => '',
        'label' => 'Favicon',
    ]);

    $response = $this->getJson('/api/app-settings');

    $response->assertSuccessful()
        ->assertJson([
            'logo_url' => null,
            'favicon_url' => null,
        ]);
});

it('returns logo url when file exists on disk', function () {
    Storage::fake('public');
    Storage::disk('public')->put('app-settings/logo.png', 'fake-image-content');

    DesignConfiguration::query()->create([
        'category' => 'app_settings',
        'key' => 'app_logo_path',
        'value' => 'app-settings/logo.png',
        'label' => 'App Logo',
    ]);

    $response = $this->getJson('/api/app-settings');

    $response->assertSuccessful();
    expect($response->json('logo_url'))->toContain('app-settings/logo.png');
});

it('returns favicon url when file exists on disk', function () {
    Storage::fake('public');
    Storage::disk('public')->put('app-settings/favicon.ico', 'fake-icon-content');

    DesignConfiguration::query()->create([
        'category' => 'app_settings',
        'key' => 'favicon_path',
        'value' => 'app-settings/favicon.ico',
        'label' => 'Favicon',
    ]);

    $response = $this->getJson('/api/app-settings');

    $response->assertSuccessful();
    expect($response->json('favicon_url'))->toContain('app-settings/favicon.ico');
});

it('returns enable_fill_test_data as true when configured', function () {
    DesignConfiguration::query()->create([
        'category' => 'app_settings',
        'key' => 'enable_fill_test_data',
        'value' => 'true',
        'label' => 'Enable Fill Test Data',
    ]);

    $response = $this->getJson('/api/app-settings');

    $response->assertSuccessful()
        ->assertJson([
            'enable_fill_test_data' => true,
        ]);
});

it('returns enable_fill_test_data as false when not configured', function () {
    $response = $this->getJson('/api/app-settings');

    $response->assertSuccessful()
        ->assertJson([
            'enable_fill_test_data' => false,
        ]);
});

it('does not require sanctum middleware', function () {
    // Ensure the endpoint works without any Bearer token
    $response = $this->getJson('/api/app-settings', [
        'Authorization' => '',
    ]);

    $response->assertSuccessful();
});
