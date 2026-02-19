<?php

use App\Models\User;

it('shows the v2 login page', function () {
    $response = $this->get('/v2/login');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('Auth/Login'));
});

it('can login via v2 with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    $response = $this->post('/v2/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $response->assertRedirect('/v2/dashboard');
    $this->assertAuthenticatedAs($user);
});

it('cannot login via v2 with invalid credentials', function () {
    User::factory()->create([
        'email' => 'test@example.com',
    ]);

    $response = $this->post('/v2/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertRedirect();
    $this->assertGuest();
});

it('cannot login via v2 with revoked account', function () {
    User::factory()->revoked()->create([
        'email' => 'revoked@example.com',
    ]);

    $response = $this->post('/v2/login', [
        'email' => 'revoked@example.com',
        'password' => 'password',
    ]);

    $response->assertRedirect();
    $this->assertGuest();
});

it('can access v2 dashboard when authenticated', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/v2/dashboard');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('Dashboard'));
});

it('redirects to v2 login when accessing v2 dashboard unauthenticated', function () {
    $response = $this->get('/v2/dashboard');

    $response->assertRedirect('/v2/login');
});

it('can logout via v2', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/v2/logout');

    $response->assertRedirect('/v2/login');
    $this->assertGuest();
});

it('redirects authenticated users away from v2 login', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/v2/login');

    $response->assertRedirect('/v2/dashboard');
});
