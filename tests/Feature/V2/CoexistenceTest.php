<?php

use App\Models\User;

it('does not break the API login endpoint', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure(['token', 'user']);
});

it('does not break the admin panel login page', function () {
    $response = $this->get('/admin/login');

    $response->assertSuccessful();
});

it('catch-all excludes v2 routes', function () {
    $response = $this->get('/v2/dashboard');

    // Should redirect to /v2/login, NOT serve the SPA index.html
    $response->assertRedirect('/v2/login');
});
