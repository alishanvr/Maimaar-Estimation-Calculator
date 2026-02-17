<?php

use App\Filament\Resources\Reports\Pages\ListReports;
use App\Filament\Resources\Reports\ReportResource;
use App\Models\Report;
use App\Models\User;
use Livewire\Livewire;

it('can render the export logs list page for admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    $this->get(ReportResource::getUrl('index'))
        ->assertSuccessful();
});

it('cannot render the export logs list page for non-admin', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get(ReportResource::getUrl('index'))
        ->assertForbidden();
});

it('export logs are read-only', function () {
    expect(ReportResource::canCreate())->toBeFalse();

    $report = Report::factory()->create();

    expect(ReportResource::canEdit($report))->toBeFalse();
    expect(ReportResource::canDelete($report))->toBeFalse();
});

it('displays export log entries in the table', function () {
    $admin = User::factory()->admin()->create();

    Report::factory()->count(3)->for($admin, 'user')->create();

    $this->actingAs($admin);

    Livewire::test(ListReports::class)
        ->assertSuccessful();
});

it('belongs to the Activity & Logs navigation group', function () {
    $reflection = new ReflectionClass(ReportResource::class);
    $property = $reflection->getProperty('navigationGroup');
    $property->setAccessible(true);

    expect($property->getValue())->toBe('Activity & Logs');
});
