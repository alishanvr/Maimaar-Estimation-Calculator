<?php

use App\Filament\Pages\PdfSettings;
use App\Models\DesignConfiguration;
use App\Models\User;
use Livewire\Livewire;

it('can render the pdf settings page for admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    $this->get('/admin/pdf-settings')
        ->assertSuccessful();
});

it('cannot access pdf settings page as non-admin', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get('/admin/pdf-settings')
        ->assertForbidden();
});

it('loads existing settings into the form', function () {
    $admin = User::factory()->admin()->create();

    DesignConfiguration::query()->create([
        'category' => 'pdf_settings',
        'key' => 'company_name',
        'value' => 'Test Corp',
        'label' => 'Company Name',
    ]);

    DesignConfiguration::query()->create([
        'category' => 'pdf_settings',
        'key' => 'header_color',
        'value' => '#ff0000',
        'label' => 'Header Color',
    ]);

    $this->actingAs($admin);

    Livewire::test(PdfSettings::class)
        ->assertFormSet([
            'company_name' => 'Test Corp',
            'header_color' => '#ff0000',
        ]);
});

it('can save pdf settings', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(PdfSettings::class)
        ->fillForm([
            'company_name' => 'New Company',
            'font_family' => 'helvetica',
            'header_color' => '#00ff00',
            'body_font_size' => '12',
            'body_line_height' => '1.5',
            'show_page_numbers' => true,
            'paper_size' => 'letter',
            'footer_text' => 'Confidential',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(DesignConfiguration::query()
        ->where('category', 'pdf_settings')
        ->where('key', 'company_name')
        ->value('value')
    )->toBe('New Company');

    expect(DesignConfiguration::query()
        ->where('category', 'pdf_settings')
        ->where('key', 'font_family')
        ->value('value')
    )->toBe('helvetica');

    expect(DesignConfiguration::query()
        ->where('category', 'pdf_settings')
        ->where('key', 'header_color')
        ->value('value')
    )->toBe('#00ff00');

    expect(DesignConfiguration::query()
        ->where('category', 'pdf_settings')
        ->where('key', 'paper_size')
        ->value('value')
    )->toBe('letter');

    expect(DesignConfiguration::query()
        ->where('category', 'pdf_settings')
        ->where('key', 'footer_text')
        ->value('value')
    )->toBe('Confidential');
});

it('validates required fields', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(PdfSettings::class)
        ->fillForm([
            'company_name' => '',
            'font_family' => null,
            'header_color' => '',
        ])
        ->call('save')
        ->assertHasFormErrors([
            'company_name' => 'required',
            'font_family' => 'required',
            'header_color' => 'required',
        ]);
});

it('flushes cache after saving', function () {
    $admin = User::factory()->admin()->create();

    DesignConfiguration::query()->create([
        'category' => 'pdf_settings',
        'key' => 'company_name',
        'value' => 'Cached Value',
        'label' => 'Company Name',
    ]);

    $this->actingAs($admin);

    // Warm up the cache
    $service = app(\App\Services\Pdf\PdfSettingsService::class);
    expect($service->companyName())->toBe('Cached Value');

    // Save new value via Filament page
    Livewire::test(PdfSettings::class)
        ->fillForm([
            'company_name' => 'Fresh Value',
            'font_family' => 'dejavu-sans',
            'header_color' => '#1e3a5f',
            'body_font_size' => '11',
            'body_line_height' => '1.4',
            'show_page_numbers' => true,
            'paper_size' => 'a4',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    // Cache should be flushed so new value is returned
    expect($service->companyName())->toBe('Fresh Value');
});
