<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('can change password with valid current password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old-password'),
    ]);

    $response = $this->actingAs($user)->putJson('/api/user/password', [
        'current_password' => 'old-password',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertSuccessful()
        ->assertJson(['message' => 'Password changed successfully.']);

    expect(Hash::check('new-password-123', $user->fresh()->password))->toBeTrue();
});

it('rejects incorrect current password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old-password'),
    ]);

    $response = $this->actingAs($user)->putJson('/api/user/password', [
        'current_password' => 'wrong-password',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['current_password']);
});

it('requires password confirmation', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old-password'),
    ]);

    $response = $this->actingAs($user)->putJson('/api/user/password', [
        'current_password' => 'old-password',
        'password' => 'new-password-123',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

it('rejects mismatched password confirmation', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old-password'),
    ]);

    $response = $this->actingAs($user)->putJson('/api/user/password', [
        'current_password' => 'old-password',
        'password' => 'new-password-123',
        'password_confirmation' => 'different-password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

it('requires minimum 8 character password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old-password'),
    ]);

    $response = $this->actingAs($user)->putJson('/api/user/password', [
        'current_password' => 'old-password',
        'password' => 'short',
        'password_confirmation' => 'short',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

it('validates all fields are required', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->putJson('/api/user/password', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['current_password', 'password']);
});

it('requires authentication', function () {
    $response = $this->putJson('/api/user/password', [
        'current_password' => 'old-password',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertUnauthorized();
});

it('logs password change activity', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old-password'),
    ]);

    $this->actingAs($user)->putJson('/api/user/password', [
        'current_password' => 'old-password',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $this->assertDatabaseHas('activity_log', [
        'description' => 'changed password',
        'causer_id' => $user->id,
        'causer_type' => User::class,
        'subject_id' => $user->id,
        'subject_type' => User::class,
    ]);
});
