<?php

use App\Models\Estimation;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('requires authentication for estimation list', function () {
    $response = $this->get('/v2/estimations');

    $response->assertRedirect('/v2/login');
});

it('renders estimations index page', function () {
    $user = User::factory()->create();
    Estimation::factory()->count(3)->for($user)->create();

    $response = $this->actingAs($user)->get('/v2/estimations');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Estimations/Index')
        ->has('estimations.data', 3)
        ->has('filters')
    );
});

it('filters estimations by status', function () {
    $user = User::factory()->create();
    Estimation::factory()->count(2)->for($user)->create(['status' => 'draft']);
    Estimation::factory()->for($user)->calculated()->create();

    $response = $this->actingAs($user)->get('/v2/estimations?status=draft');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Estimations/Index')
        ->has('estimations.data', 2)
        ->where('filters.status', 'draft')
    );
});

it('creates a new estimation', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/v2/estimations', [
        'building_name' => 'Test Building',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Estimation created.');

    $this->assertDatabaseHas('estimations', [
        'user_id' => $user->id,
        'building_name' => 'Test Building',
        'status' => 'draft',
    ]);
});

it('deletes an estimation', function () {
    $user = User::factory()->create();
    $estimation = Estimation::factory()->for($user)->create();

    $response = $this->actingAs($user)->delete("/v2/estimations/{$estimation->id}");

    $response->assertRedirect(route('v2.estimations.index'));
    $response->assertSessionHas('success', 'Estimation deleted.');

    $this->assertSoftDeleted('estimations', ['id' => $estimation->id]);
});

it('clones an estimation', function () {
    $user = User::factory()->create();
    $estimation = Estimation::factory()->for($user)->create([
        'building_name' => 'Original',
        'project_name' => 'Project A',
        'customer_name' => 'Customer A',
        'salesperson_code' => 'SP01',
        'input_data' => ['key' => 'value'],
    ]);

    $response = $this->actingAs($user)->post("/v2/estimations/{$estimation->id}/clone");

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Estimation cloned.');

    $this->assertDatabaseHas('estimations', [
        'user_id' => $user->id,
        'building_name' => 'Original (Copy)',
        'project_name' => 'Project A',
        'customer_name' => 'Customer A',
        'salesperson_code' => 'SP01',
        'status' => 'draft',
    ]);
});

it('shows estimation detail page', function () {
    $user = User::factory()->create();
    $estimation = Estimation::factory()->for($user)->create();

    $response = $this->actingAs($user)->get("/v2/estimations/{$estimation->id}");

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Estimations/Show')
        ->has('estimation', fn (Assert $prop) => $prop
            ->where('id', $estimation->id)
            ->etc()
        )
    );
});

it('compares two estimations', function () {
    $user = User::factory()->create();
    $estimation1 = Estimation::factory()->for($user)->calculated()->create();
    $estimation2 = Estimation::factory()->for($user)->calculated()->create();

    $response = $this->actingAs($user)
        ->get("/v2/estimations/compare?ids={$estimation1->id},{$estimation2->id}");

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Estimations/Compare')
        ->has('estimations', 2)
    );
});

it('rejects comparison without exactly two estimations', function () {
    $user = User::factory()->create();
    $estimation = Estimation::factory()->for($user)->create();

    $response = $this->actingAs($user)
        ->get("/v2/estimations/compare?ids={$estimation->id}");

    $response->assertStatus(422);
});

it('scopes estimations to current user for non-admin', function () {
    $user = User::factory()->create(['role' => 'user']);
    $otherUser = User::factory()->create();

    Estimation::factory()->count(2)->for($user)->create();
    Estimation::factory()->count(3)->for($otherUser)->create();

    $response = $this->actingAs($user)->get('/v2/estimations');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Estimations/Index')
        ->has('estimations.data', 2)
    );
});

it('shows all estimations for admin', function () {
    $admin = User::factory()->admin()->create();
    $otherUser = User::factory()->create();

    Estimation::factory()->count(2)->for($admin)->create();
    Estimation::factory()->count(3)->for($otherUser)->create();

    $response = $this->actingAs($admin)->get('/v2/estimations');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Estimations/Index')
        ->has('estimations.data', 5)
    );
});
