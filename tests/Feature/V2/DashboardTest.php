<?php

use App\Models\Estimation;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('requires authentication', function () {
    $response = $this->get('/v2/dashboard');

    $response->assertRedirect('/v2/login');
});

it('renders dashboard with stats', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/v2/dashboard');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->has('stats', fn (Assert $prop) => $prop
            ->has('total')
            ->has('draft')
            ->has('calculated')
            ->has('finalized')
        )
    );
});

it('shows correct estimation counts for regular user', function () {
    $user = User::factory()->create(['role' => 'user']);
    $otherUser = User::factory()->create();

    Estimation::factory()->count(2)->for($user)->create(['status' => 'draft']);
    Estimation::factory()->for($user)->calculated()->create();
    Estimation::factory()->for($user)->finalized()->create();

    // Other user's estimations should not count
    Estimation::factory()->count(3)->for($otherUser)->create(['status' => 'draft']);

    $response = $this->actingAs($user)->get('/v2/dashboard');

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('stats.total', 4)
        ->where('stats.draft', 2)
        ->where('stats.calculated', 1)
        ->where('stats.finalized', 1)
    );
});

it('shows all estimation counts for admin', function () {
    $admin = User::factory()->admin()->create();
    $otherUser = User::factory()->create();

    Estimation::factory()->count(2)->for($admin)->create(['status' => 'draft']);
    Estimation::factory()->for($otherUser)->calculated()->create();
    Estimation::factory()->for($otherUser)->finalized()->create();

    $response = $this->actingAs($admin)->get('/v2/dashboard');

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('stats.total', 4)
        ->where('stats.draft', 2)
        ->where('stats.calculated', 1)
        ->where('stats.finalized', 1)
    );
});
