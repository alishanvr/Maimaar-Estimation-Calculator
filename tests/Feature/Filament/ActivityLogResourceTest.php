<?php

use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

it('can render the activity logs list page for admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    $this->get(ActivityLogResource::getUrl('index'))
        ->assertSuccessful();
});

it('cannot render the activity logs list page for non-admin', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get(ActivityLogResource::getUrl('index'))
        ->assertForbidden();
});

it('activity logs are read-only', function () {
    expect(ActivityLogResource::canCreate())->toBeFalse();

    $activity = Activity::create([
        'log_name' => 'default',
        'description' => 'test activity',
    ]);

    expect(ActivityLogResource::canEdit($activity))->toBeFalse();
    expect(ActivityLogResource::canDelete($activity))->toBeFalse();
});

it('displays activity log entries in the table', function () {
    $admin = User::factory()->admin()->create();

    activity()
        ->causedBy($admin)
        ->log('test action one');

    activity()
        ->causedBy($admin)
        ->log('test action two');

    $this->actingAs($admin);

    Livewire::test(ListActivityLogs::class)
        ->assertSuccessful();
});
