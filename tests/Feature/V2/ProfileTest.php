<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('requires authentication to access profile page', function () {
    $response = $this->get('/v2/profile');

    $response->assertRedirect('/v2/login');
});

it('renders the profile edit page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/v2/profile');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Profile/Edit')
        ->has('user', fn (Assert $prop) => $prop
            ->where('id', $user->id)
            ->where('name', $user->name)
            ->where('email', $user->email)
            ->where('company_name', $user->company_name)
            ->where('phone', $user->phone)
        )
    );
});

it('updates profile information', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->put('/v2/profile', [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
        'company_name' => 'Updated Company',
        'phone' => '1234567890',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Profile updated successfully.');

    $user->refresh();
    expect($user->name)->toBe('Updated Name');
    expect($user->email)->toBe('updated@example.com');
    expect($user->company_name)->toBe('Updated Company');
    expect($user->phone)->toBe('1234567890');
});

it('validates profile update data', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->put('/v2/profile', [
        'name' => '',
        'email' => 'not-an-email',
    ]);

    $response->assertSessionHasErrors(['name', 'email']);
});

it('validates unique email on profile update', function () {
    $existing = User::factory()->create(['email' => 'taken@example.com']);
    $user = User::factory()->create();

    $response = $this->actingAs($user)->put('/v2/profile', [
        'name' => 'Test',
        'email' => 'taken@example.com',
    ]);

    $response->assertSessionHasErrors(['email']);
});

it('allows keeping own email on profile update', function () {
    $user = User::factory()->create(['email' => 'mine@example.com']);

    $response = $this->actingAs($user)->put('/v2/profile', [
        'name' => 'Updated',
        'email' => 'mine@example.com',
    ]);

    $response->assertSessionDoesntHaveErrors(['email']);
    $response->assertRedirect();
});

it('changes password with correct current password', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->put('/v2/profile/password', [
        'current_password' => 'password',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Password changed successfully.');
});

it('rejects password change with wrong current password', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->put('/v2/profile/password', [
        'current_password' => 'wrong-password',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertSessionHasErrors(['current_password']);
});

it('validates new password confirmation', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->put('/v2/profile/password', [
        'current_password' => 'password',
        'password' => 'new-password-123',
        'password_confirmation' => 'mismatched',
    ]);

    $response->assertSessionHasErrors(['password']);
});
