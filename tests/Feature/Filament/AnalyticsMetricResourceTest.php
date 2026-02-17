<?php

use App\Filament\Resources\AnalyticsMetrics\AnalyticsMetricResource;
use App\Filament\Resources\AnalyticsMetrics\Pages\ListAnalyticsMetrics;
use App\Models\AnalyticsMetric;
use App\Models\User;
use Livewire\Livewire;

it('can render the analytics metrics list page for admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    $this->get(AnalyticsMetricResource::getUrl('index'))
        ->assertSuccessful();
});

it('cannot render the analytics metrics list page for non-admin', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get(AnalyticsMetricResource::getUrl('index'))
        ->assertForbidden();
});

it('analytics metrics are read-only', function () {
    expect(AnalyticsMetricResource::canCreate())->toBeFalse();

    $metric = AnalyticsMetric::factory()->create();

    expect(AnalyticsMetricResource::canEdit($metric))->toBeFalse();
    expect(AnalyticsMetricResource::canDelete($metric))->toBeFalse();
});

it('displays analytics metric entries in the table', function () {
    $admin = User::factory()->admin()->create();

    AnalyticsMetric::factory()->count(3)->create();

    $this->actingAs($admin);

    Livewire::test(ListAnalyticsMetrics::class)
        ->assertSuccessful();
});

it('belongs to the Activity & Logs navigation group', function () {
    $reflection = new ReflectionClass(AnalyticsMetricResource::class);
    $property = $reflection->getProperty('navigationGroup');
    $property->setAccessible(true);

    expect($property->getValue())->toBe('Activity & Logs');
});
