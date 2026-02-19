<?php

use App\Models\Estimation;
use App\Models\Project;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('requires authentication for project list', function () {
    $response = $this->get('/v2/projects');

    $response->assertRedirect('/v2/login');
});

it('renders projects index page', function () {
    $user = User::factory()->create();
    Project::factory()->count(3)->for($user)->create();

    $response = $this->actingAs($user)->get('/v2/projects');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Projects/Index')
        ->has('projects.data', 3)
        ->has('filters')
    );
});

it('creates a new project', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/v2/projects', [
        'project_number' => 'PRJ-TEST-001',
        'project_name' => 'Test Project',
        'customer_name' => 'Test Customer',
        'location' => 'Dubai, UAE',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Project created.');

    $this->assertDatabaseHas('projects', [
        'user_id' => $user->id,
        'project_number' => 'PRJ-TEST-001',
        'project_name' => 'Test Project',
        'customer_name' => 'Test Customer',
        'location' => 'Dubai, UAE',
    ]);
});

it('validates required fields when creating a project', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/v2/projects', []);

    $response->assertSessionHasErrors(['project_number', 'project_name']);
});

it('validates unique project number', function () {
    $user = User::factory()->create();
    Project::factory()->for($user)->create(['project_number' => 'PRJ-DUPE']);

    $response = $this->actingAs($user)->post('/v2/projects', [
        'project_number' => 'PRJ-DUPE',
        'project_name' => 'Another Project',
    ]);

    $response->assertSessionHasErrors(['project_number']);
});

it('shows project detail page', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $response = $this->actingAs($user)->get("/v2/projects/{$project->id}");

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Projects/Show')
        ->has('project', fn (Assert $prop) => $prop
            ->where('id', $project->id)
            ->etc()
        )
    );
});

it('updates project information', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $response = $this->actingAs($user)->put("/v2/projects/{$project->id}", [
        'project_name' => 'Updated Project Name',
        'customer_name' => 'Updated Customer',
        'location' => 'Abu Dhabi, UAE',
        'status' => 'in_progress',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Project updated.');

    $project->refresh();
    expect($project->project_name)->toBe('Updated Project Name');
    expect($project->customer_name)->toBe('Updated Customer');
    expect($project->location)->toBe('Abu Dhabi, UAE');
    expect($project->status)->toBe('in_progress');
});

it('validates status value on update', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $response = $this->actingAs($user)->put("/v2/projects/{$project->id}", [
        'status' => 'invalid_status',
    ]);

    $response->assertSessionHasErrors(['status']);
});

it('deletes a project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $response = $this->actingAs($user)->delete("/v2/projects/{$project->id}");

    $response->assertRedirect(route('v2.projects.index'));
    $response->assertSessionHas('success', 'Project deleted.');

    $this->assertSoftDeleted('projects', ['id' => $project->id]);
});

it('adds a building to project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $response = $this->actingAs($user)->post("/v2/projects/{$project->id}/buildings");

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Building added to project.');

    $this->assertDatabaseHas('estimations', [
        'user_id' => $user->id,
        'project_id' => $project->id,
        'building_name' => 'New Building',
        'status' => 'draft',
    ]);
});

it('removes a building from project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $estimation = Estimation::factory()->for($user)->create([
        'project_id' => $project->id,
    ]);

    $response = $this->actingAs($user)
        ->delete("/v2/projects/{$project->id}/buildings/{$estimation->id}");

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Building removed from project.');

    $estimation->refresh();
    expect($estimation->project_id)->toBeNull();
});

it('filters projects by status', function () {
    $user = User::factory()->create();
    Project::factory()->count(2)->for($user)->create(['status' => 'draft']);
    Project::factory()->for($user)->inProgress()->create();

    $response = $this->actingAs($user)->get('/v2/projects?status=draft');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Projects/Index')
        ->has('projects.data', 2)
        ->where('filters.status', 'draft')
    );
});

it('searches projects by name', function () {
    $user = User::factory()->create();
    Project::factory()->for($user)->create(['project_name' => 'Alpha Warehouse']);
    Project::factory()->for($user)->create(['project_name' => 'Beta Factory']);
    Project::factory()->for($user)->create(['project_name' => 'Alpha Tower']);

    $response = $this->actingAs($user)->get('/v2/projects?search=Alpha');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Projects/Index')
        ->has('projects.data', 2)
        ->where('filters.search', 'Alpha')
    );
});

it('scopes projects to current user for non-admin', function () {
    $user = User::factory()->create(['role' => 'user']);
    $otherUser = User::factory()->create();

    Project::factory()->count(2)->for($user)->create();
    Project::factory()->count(3)->for($otherUser)->create();

    $response = $this->actingAs($user)->get('/v2/projects');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Projects/Index')
        ->has('projects.data', 2)
    );
});

it('shows all projects for admin', function () {
    $admin = User::factory()->admin()->create();
    $otherUser = User::factory()->create();

    Project::factory()->count(2)->for($admin)->create();
    Project::factory()->count(3)->for($otherUser)->create();

    $response = $this->actingAs($admin)->get('/v2/projects');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Projects/Index')
        ->has('projects.data', 5)
    );
});
