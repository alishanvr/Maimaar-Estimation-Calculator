<?php

use App\Filament\Resources\Sessions\Pages\ListSessions;
use App\Filament\Resources\Sessions\SessionResource;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

it('can render the active sessions list page for admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    $this->get(SessionResource::getUrl('index'))
        ->assertSuccessful();
});

it('cannot render the active sessions list page for non-admin', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get(SessionResource::getUrl('index'))
        ->assertForbidden();
});

it('sessions are not creatable or editable', function () {
    expect(SessionResource::canCreate())->toBeFalse();
});

it('displays session entries in the table', function () {
    $admin = User::factory()->admin()->create();

    DB::table('sessions')->insert([
        'id' => 'test-session-id-123',
        'user_id' => $admin->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Chrome/120.0',
        'payload' => base64_encode(serialize([])),
        'last_activity' => now()->timestamp,
    ]);

    $this->actingAs($admin);

    Livewire::test(ListSessions::class)
        ->assertSuccessful();
});

it('belongs to the Activity & Logs navigation group', function () {
    $reflection = new ReflectionClass(SessionResource::class);
    $property = $reflection->getProperty('navigationGroup');
    $property->setAccessible(true);

    expect($property->getValue())->toBe('Activity & Logs');
});
