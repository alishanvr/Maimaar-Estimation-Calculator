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

it('shows trashed estimations', function () {
    $admin = User::factory()->admin()->create();
    $estimation = Estimation::factory()->create();
    $estimation->delete();

    $this->actingAs($admin);

    $this->get(EstimationResource::getUrl('view', ['record' => $estimation]))
        ->assertSuccessful();
});
