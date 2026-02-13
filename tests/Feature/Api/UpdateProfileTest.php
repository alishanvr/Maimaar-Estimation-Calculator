<?php

use App\Models\User;

it('can update profile', function () {
    $user = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@example.com',
        'company_name' => 'Old Company',
        'phone' => '111-111-1111',
    ]);

    $response = $this->actingAs($user)->putJson('/api/user/profile', [
        'name' => 'New Name',
        'email' => 'new@example.com',
        'company_name' => 'New Company',
        'phone' => '222-222-2222',
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'message' => 'Profile updated successfully.',
            'user' => [
                'name' => 'New Name',
                'email' => 'new@example.com',
                'company_name' => 'New Company',
                'phone' => '222-222-2222',
            ],
        ]);

    $user->refresh();
    expect($user->name)->toBe('New Name')
        ->and($user->email)->toBe('new@example.com')
        ->and($user->company_name)->toBe('New Company')
        ->and($user->phone)->toBe('222-222-2222');
});

it('validates name and email are required', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->putJson('/api/user/profile', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email']);
});

it('validates email format', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->putJson('/api/user/profile', [
        'name' => 'Test',
        'email' => 'not-an-email',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('validates email uniqueness for other users', function () {
    User::factory()->create(['email' => 'taken@example.com']);
    $user = User::factory()->create(['email' => 'mine@example.com']);

    $response = $this->actingAs($user)->putJson('/api/user/profile', [
        'name' => 'Test',
        'email' => 'taken@example.com',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('allows keeping own email unchanged', function () {
    $user = User::factory()->create(['email' => 'mine@example.com']);

    $response = $this->actingAs($user)->putJson('/api/user/profile', [
        'name' => 'Updated Name',
        'email' => 'mine@example.com',
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('user.name', 'Updated Name')
        ->assertJsonPath('user.email', 'mine@example.com');
});

it('allows nullable company name and phone', function () {
    $user = User::factory()->create([
        'company_name' => 'Old Company',
        'phone' => '111-111-1111',
    ]);

    $response = $this->actingAs($user)->putJson('/api/user/profile', [
        'name' => $user->name,
        'email' => $user->email,
        'company_name' => null,
        'phone' => null,
    ]);

    $response->assertSuccessful();

    $user->refresh();
    expect($user->company_name)->toBeNull()
        ->and($user->phone)->toBeNull();
});

it('requires authentication', function () {
    $response = $this->putJson('/api/user/profile', [
        'name' => 'Test',
        'email' => 'test@example.com',
    ]);

    $response->assertUnauthorized();
});

it('logs profile update activity', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->putJson('/api/user/profile', [
        'name' => 'Updated Name',
        'email' => $user->email,
    ]);

    $this->assertDatabaseHas('activity_log', [
        'description' => 'updated profile',
        'causer_id' => $user->id,
        'causer_type' => User::class,
        'subject_id' => $user->id,
        'subject_type' => User::class,
    ]);
});

it('does not allow changing role or status via profile', function () {
    $user = User::factory()->create([
        'role' => 'user',
        'status' => 'active',
    ]);

    $this->actingAs($user)->putJson('/api/user/profile', [
        'name' => $user->name,
        'email' => $user->email,
        'role' => 'admin',
        'status' => 'revoked',
    ]);

    $user->refresh();
    expect($user->role)->toBe('user')
        ->and($user->status)->toBe('active');
});
