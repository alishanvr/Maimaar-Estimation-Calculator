<?php

use App\Filament\Resources\Estimations\EstimationResource;
use App\Filament\Resources\Estimations\Pages\ListEstimations;
use App\Models\Estimation;
use App\Models\User;
use Livewire\Livewire;

it('can render the estimations list page for admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    $this->get(EstimationResource::getUrl('index'))
        ->assertSuccessful();
});

it('cannot render the estimations list page for non-admin', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get(EstimationResource::getUrl('index'))
        ->assertForbidden();
});

it('can list estimations in the table', function () {
    $admin = User::factory()->admin()->create();
    $estimations = Estimation::factory()->count(3)->create();

    $this->actingAs($admin);

    Livewire::test(ListEstimations::class)
        ->assertCanSeeTableRecords($estimations);
});

it('can render the view estimation page', function () {
    $admin = User::factory()->admin()->create();
    $estimation = Estimation::factory()->create();

    $this->actingAs($admin);

    $this->get(EstimationResource::getUrl('view', ['record' => $estimation]))
        ->assertSuccessful();
});

it('cannot create estimations through filament', function () {
    expect(EstimationResource::canCreate())->toBeFalse();
});

it('logs activity when admin edits an estimation', function () {
    $admin = User::factory()->admin()->create();
    $estimation = Estimation::factory()->create([
        'building_name' => 'Old Building',
        'status' => 'draft',
    ]);

    $this->actingAs($admin);

    Livewire::test(\App\Filament\Resources\Estimations\Pages\EditEstimation::class, [
        'record' => $estimation->getRouteKey(),
    ])
        ->fillForm([
            'building_name' => 'New Building',
            'status' => 'finalized',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $activity = \Spatie\Activitylog\Models\Activity::query()
        ->where('subject_type', Estimation::class)
        ->where('subject_id', $estimation->id)
        ->where('event', 'updated')
        ->latest()
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->causer_id)->toBe($admin->id);
    expect($activity->properties['old']['building_name'])->toBe('Old Building');
    expect($activity->properties['attributes']['building_name'])->toBe('New Building');
    expect($activity->properties['old']['status'])->toBe('draft');
    expect($activity->properties['attributes']['status'])->toBe('finalized');
});

it('can render view page with calculation results for calculated estimation', function () {
    $admin = User::factory()->admin()->create();
    $estimation = Estimation::factory()->withResults()->create();

    $this->actingAs($admin);

    $this->get(EstimationResource::getUrl('view', ['record' => $estimation]))
        ->assertSuccessful()
        ->assertSeeText('Calculation Results')
        ->assertSeeText('FCPBS')
        ->assertSeeText('BOQ');
});

it('shows estimation items in edit page relation manager', function () {
    $admin = User::factory()->admin()->create();
    $estimation = Estimation::factory()->withItems()->create();

    $this->actingAs($admin);

    $this->get(EstimationResource::getUrl('edit', ['record' => $estimation]))
        ->assertSuccessful();

    expect($estimation->items)->toHaveCount(3);
    expect($estimation->items->pluck('item_code')->toArray())->toContain('MFR', 'MFC', 'RP');
});

it('shows trashed estimations', function () {
    $admin = User::factory()->admin()->create();
    $estimation = Estimation::factory()->create();
    $estimation->delete();

    $this->actingAs($admin);

    $this->get(EstimationResource::getUrl('view', ['record' => $estimation]))
        ->assertSuccessful();
});
