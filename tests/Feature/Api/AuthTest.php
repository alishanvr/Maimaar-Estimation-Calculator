<?php

use App\Models\User;

it('can login with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'message',
            'user' => ['id', 'name', 'email', 'role'],
            'token',
        ]);
});

it('cannot login with invalid credentials', function () {
    User::factory()->create([
        'email' => 'test@example.com',
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(401)
        ->assertJson(['message' => 'Invalid credentials.']);
});

it('cannot login with revoked account', function () {
    User::factory()->revoked()->create([
        'email' => 'revoked@example.com',
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'revoked@example.com',
        'password' => 'password',
    ]);

    $response->assertForbidden()
        ->assertJsonFragment(['message' => 'Your account has been revoked. Please contact the administrator.']);
});

it('validates login request', function () {
    $response = $this->postJson('/api/login', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password']);
});

it('can logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/logout');

    $response->assertSuccessful()
        ->assertJson(['message' => 'Logged out successfully.']);

    expect($user->tokens()->count())->toBe(0);
});

it('can get authenticated user', function () {
    $user = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'role' => 'user',
        'company_name' => 'Test Company',
    ]);
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/user');

    $response->assertSuccessful()
        ->assertJson([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'user',
            'company_name' => 'Test Company',
        ]);
});

it('cannot access user endpoint without authentication', function () {
    $response = $this->getJson('/api/user');

    $response->assertUnauthorized();
});

it('admin user has admin role in response', function () {
    $admin = User::factory()->admin()->create([
        'email' => 'admin@example.com',
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('user.role', 'admin');
});

it('logs login activity', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $this->assertDatabaseHas('activity_log', [
        'description' => 'logged in',
        'causer_id' => $user->id,
        'causer_type' => User::class,
    ]);
});

it('logs logout activity', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/logout');

    $this->assertDatabaseHas('activity_log', [
        'description' => 'logged out',
        'causer_id' => $user->id,
        'causer_type' => User::class,
    ]);
});
