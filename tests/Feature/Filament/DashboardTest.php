<?php

use App\Filament\Widgets\EstimationsByStatusWidget;
use App\Filament\Widgets\EstimationsOverTimeWidget;
use App\Filament\Widgets\EstimationValueTrendsWidget;
use App\Filament\Widgets\RecentActivityWidget;
use App\Filament\Widgets\RecentEstimationsWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\UserActivityWidget;
use App\Models\Estimation;
use App\Models\User;
use Livewire\Livewire;

it('can render the admin dashboard for admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    $this->get('/admin')
        ->assertSuccessful();
});

it('cannot access the admin dashboard as non-admin', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get('/admin')
        ->assertForbidden();
});

it('can render the stats overview widget', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(StatsOverviewWidget::class)
        ->assertSuccessful();
});

it('stats overview widget shows correct counts', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->count(3)->create(['status' => 'active']);
    User::factory()->count(2)->revoked()->create();

    Estimation::factory()->count(2)->create(['status' => 'draft']);
    Estimation::factory()->count(3)->create(['status' => 'calculated', 'total_price_aed' => 100000]);

    $this->actingAs($admin);

    Livewire::test(StatsOverviewWidget::class)
        ->assertSuccessful();
});

it('can render the recent activity widget', function () {
    $admin = User::factory()->admin()->create();

    activity()
        ->causedBy($admin)
        ->log('test activity');

    $this->actingAs($admin);

    Livewire::test(RecentActivityWidget::class)
        ->assertSuccessful();
});

it('can render the estimations by status widget', function () {
    $admin = User::factory()->admin()->create();

    Estimation::factory()->count(2)->create(['status' => 'draft']);
    Estimation::factory()->count(1)->create(['status' => 'calculated']);

    $this->actingAs($admin);

    Livewire::test(EstimationsByStatusWidget::class)
        ->assertSuccessful();
});

it('can render the recent estimations widget', function () {
    $admin = User::factory()->admin()->create();

    Estimation::factory()->count(3)->create();

    $this->actingAs($admin);

    Livewire::test(RecentEstimationsWidget::class)
        ->assertSuccessful();
});

it('can render the estimations over time widget', function () {
    $admin = User::factory()->admin()->create();

    Estimation::factory()->count(3)->create();

    $this->actingAs($admin);

    Livewire::test(EstimationsOverTimeWidget::class)
        ->assertSuccessful();
});

it('can render the estimation value trends widget', function () {
    $admin = User::factory()->admin()->create();

    Estimation::factory()->count(2)->create([
        'status' => 'calculated',
        'total_price_aed' => 50000,
    ]);

    $this->actingAs($admin);

    Livewire::test(EstimationValueTrendsWidget::class)
        ->assertSuccessful();
});

it('can render the user activity widget', function () {
    $admin = User::factory()->admin()->create();

    Estimation::factory()->count(3)->create();

    $this->actingAs($admin);

    Livewire::test(UserActivityWidget::class)
        ->assertSuccessful();
});
