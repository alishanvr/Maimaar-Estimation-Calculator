<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('requires authentication', function () {
    $response = $this->get('/v2/reports');

    $response->assertRedirect('/v2/login');
});

it('renders reports page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/v2/reports');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Reports/Index')
        ->has('initialData')
    );
});
