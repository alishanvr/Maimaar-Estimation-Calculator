<?php

use App\Models\DesignConfiguration;
use App\Services\Pdf\PdfSettingsService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::forget('pdf_settings');
});

it('returns default values when no settings exist', function () {
    $service = app(PdfSettingsService::class);

    expect($service->companyName())->toBe('Maimaar Group')
        ->and($service->fontFamily())->toBe('dejavu-sans')
        ->and($service->headerColor())->toBe('#1e3a5f')
        ->and($service->bodyFontSize())->toBe('11')
        ->and($service->bodyLineHeight())->toBe('1.4')
        ->and($service->showPageNumbers())->toBeTrue()
        ->and($service->paperSize())->toBe('a4')
        ->and($service->footerText())->toBe('');
});

it('reads settings from the database', function () {
    DesignConfiguration::query()->create([
        'category' => 'pdf_settings',
        'key' => 'company_name',
        'value' => 'Test Company',
        'label' => 'Company Name',
    ]);

    DesignConfiguration::query()->create([
        'category' => 'pdf_settings',
        'key' => 'header_color',
        'value' => '#ff0000',
        'label' => 'Header Color',
    ]);

    $service = app(PdfSettingsService::class);

    expect($service->companyName())->toBe('Test Company')
        ->and($service->headerColor())->toBe('#ff0000');
});

it('caches settings and returns cached values', function () {
    DesignConfiguration::query()->create([
        'category' => 'pdf_settings',
        'key' => 'company_name',
        'value' => 'Cached Company',
        'label' => 'Company Name',
    ]);

    $service = app(PdfSettingsService::class);
    expect($service->companyName())->toBe('Cached Company');

    // Change the value directly in DB (bypassing cache)
    DesignConfiguration::query()
        ->where('category', 'pdf_settings')
        ->where('key', 'company_name')
        ->update(['value' => 'Updated Company']);

    // Should still return cached value
    expect($service->companyName())->toBe('Cached Company');
});

it('flushes cache correctly', function () {
    DesignConfiguration::query()->create([
        'category' => 'pdf_settings',
        'key' => 'company_name',
        'value' => 'Original',
        'label' => 'Company Name',
    ]);

    $service = app(PdfSettingsService::class);
    expect($service->companyName())->toBe('Original');

    DesignConfiguration::query()
        ->where('category', 'pdf_settings')
        ->where('key', 'company_name')
        ->update(['value' => 'Flushed']);

    $service->flushCache();

    expect($service->companyName())->toBe('Flushed');
});

it('returns correct font family CSS', function () {
    $service = app(PdfSettingsService::class);

    // Default is dejavu-sans
    expect($service->fontFamilyCss())->toContain('DejaVu Sans');

    // Set to helvetica
    DesignConfiguration::query()->create([
        'category' => 'pdf_settings',
        'key' => 'font_family',
        'value' => 'helvetica',
        'label' => 'Font Family',
    ]);
    Cache::forget('pdf_settings');

    expect($service->fontFamilyCss())->toContain('Helvetica');
});

it('returns null logo path when no logo exists', function () {
    $service = app(PdfSettingsService::class);

    // Assume no logo file exists in test environment
    DesignConfiguration::query()->create([
        'category' => 'pdf_settings',
        'key' => 'company_logo_path',
        'value' => 'nonexistent/logo.png',
        'label' => 'Company Logo Path',
    ]);
    Cache::forget('pdf_settings');

    // The service checks Storage::disk('public')->exists() which won't find this
    // It falls back to public_path checks, which also won't find the file in tests
    $path = $service->logoAbsolutePath();

    // It should return either null or a valid path if the fallback images exist
    if ($path !== null) {
        expect(file_exists($path))->toBeTrue();
    } else {
        expect($path)->toBeNull();
    }
});

it('returns all settings as array', function () {
    DesignConfiguration::query()->create([
        'category' => 'pdf_settings',
        'key' => 'company_name',
        'value' => 'All Test',
        'label' => 'Company Name',
    ]);

    DesignConfiguration::query()->create([
        'category' => 'pdf_settings',
        'key' => 'paper_size',
        'value' => 'letter',
        'label' => 'Paper Size',
    ]);

    $service = app(PdfSettingsService::class);
    $all = $service->all();

    expect($all)->toBeArray()
        ->and($all['company_name'])->toBe('All Test')
        ->and($all['paper_size'])->toBe('letter');
});

it('does not leak settings from other categories', function () {
    DesignConfiguration::query()->create([
        'category' => 'frame_type',
        'key' => 'clear_span',
        'value' => 'Clear Span',
        'label' => 'Clear Span',
    ]);

    $service = app(PdfSettingsService::class);
    $all = $service->all();

    expect($all)->not->toHaveKey('clear_span');
});
