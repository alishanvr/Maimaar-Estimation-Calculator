<?php

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Notifications\PasswordChangedNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Livewire\Livewire;

it('can render the users list page for admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    $this->get(UserResource::getUrl('index'))
        ->assertSuccessful();
});

it('cannot render the users list page for non-admin', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get(UserResource::getUrl('index'))
        ->assertForbidden();
});

it('can list users in the table', function () {
    $admin = User::factory()->admin()->create();
    $users = User::factory()->count(3)->create();

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords($users);
});

it('can render the create user page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    $this->get(UserResource::getUrl('create'))
        ->assertSuccessful();
});

it('can create a user', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Test User',
            'email' => 'newuser@example.com',
            'password' => 'securepassword',
            'role' => 'user',
            'status' => 'active',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('users', [
        'email' => 'newuser@example.com',
        'role' => 'user',
        'status' => 'active',
    ]);
});

it('validates required fields when creating user', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => '',
            'email' => '',
            'password' => '',
            'role' => '',
            'status' => '',
        ])
        ->call('create')
        ->assertHasFormErrors(['name', 'email', 'password', 'role', 'status']);
});

it('can render the edit user page', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin);

    $this->get(UserResource::getUrl('edit', ['record' => $user]))
        ->assertSuccessful();
});

it('can update a user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->fillForm([
            'name' => 'Updated Name',
            'email' => $user->email,
            'role' => 'admin',
            'status' => 'active',
            'phone' => '+971501234567',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($user->fresh())
        ->name->toBe('Updated Name')
        ->role->toBe('admin');
});

it('can revoke a user via table action', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['status' => 'active']);

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->callTableAction('revoke', $user);

    expect($user->fresh()->status)->toBe('revoked');
});

it('can activate a revoked user via table action', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->revoked()->create();

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->callTableAction('activate', $user);

    expect($user->fresh()->status)->toBe('active');
});

// Self-revoke prevention

it('cannot see the revoke action for the currently authenticated admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->assertTableActionHidden('revoke', $admin);
});

it('can see the revoke action for other active users', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['status' => 'active']);

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->assertTableActionVisible('revoke', $user);
});

// Manage Password

it('can set a specific password for a user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->callTableAction('managePassword', $user, data: [
            'action_type' => 'set_password',
            'new_password' => 'NewSecurePass123',
            'new_password_confirmation' => 'NewSecurePass123',
        ]);

    expect(Hash::check('NewSecurePass123', $user->fresh()->password))->toBeTrue();
});

it('can set password and send email notification', function () {
    NotificationFacade::fake();

    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->callTableAction('managePassword', $user, data: [
            'action_type' => 'set_password_and_notify',
            'new_password' => 'NewSecurePass123',
            'new_password_confirmation' => 'NewSecurePass123',
        ]);

    expect(Hash::check('NewSecurePass123', $user->fresh()->password))->toBeTrue();
    NotificationFacade::assertSentTo($user, PasswordChangedNotification::class);
});

it('can send a password reset link', function () {
    NotificationFacade::fake();

    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->callTableAction('managePassword', $user, data: [
            'action_type' => 'send_reset_link',
        ]);

    NotificationFacade::assertSentTo($user, \Illuminate\Auth\Notifications\ResetPassword::class);
});

it('logs activity when setting password manually', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->callTableAction('managePassword', $user, data: [
            'action_type' => 'set_password',
            'new_password' => 'NewSecurePass123',
            'new_password_confirmation' => 'NewSecurePass123',
        ]);

    $this->assertDatabaseHas('activity_log', [
        'description' => 'set user password manually',
        'subject_id' => $user->id,
        'causer_id' => $admin->id,
    ]);
});

it('logs activity when sending password reset link', function () {
    NotificationFacade::fake();

    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->callTableAction('managePassword', $user, data: [
            'action_type' => 'send_reset_link',
        ]);

    $this->assertDatabaseHas('activity_log', [
        'description' => 'sent password reset link',
        'subject_id' => $user->id,
        'causer_id' => $admin->id,
    ]);
});
