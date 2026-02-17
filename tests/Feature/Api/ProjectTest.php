<?php

use App\Models\Estimation;
use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->for($this->user)->create();
});

// ── CRUD ─────────────────────────────────────────────────────────────

it('can list projects', function () {
    Project::factory()->count(3)->for($this->user)->create();

    $this->actingAs($this->user)
        ->getJson('/api/projects')
        ->assertOk()
        ->assertJsonCount(4, 'data');
});

it('can create a project', function () {
    $payload = [
        'project_number' => 'PRJ-NEW-001',
        'project_name' => 'New Test Project',
        'customer_name' => 'Test Customer',
        'location' => 'Dubai, UAE',
        'description' => 'A test project.',
    ];

    $this->actingAs($this->user)
        ->postJson('/api/projects', $payload)
        ->assertCreated()
        ->assertJsonPath('data.project_number', 'PRJ-NEW-001')
        ->assertJsonPath('data.project_name', 'New Test Project');
});

it('can show a project', function () {
    $this->actingAs($this->user)
        ->getJson("/api/projects/{$this->project->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $this->project->id)
        ->assertJsonStructure([
            'data' => ['id', 'project_number', 'project_name', 'status', 'summary'],
        ]);
});

it('can update a project', function () {
    $this->actingAs($this->user)
        ->putJson("/api/projects/{$this->project->id}", [
            'project_name' => 'Updated Project Name',
            'status' => 'in_progress',
        ])
        ->assertOk()
        ->assertJsonPath('data.project_name', 'Updated Project Name')
        ->assertJsonPath('data.status', 'in_progress');
});

it('can delete a project', function () {
    $this->actingAs($this->user)
        ->deleteJson("/api/projects/{$this->project->id}")
        ->assertOk();

    expect(Project::find($this->project->id))->toBeNull();
    expect(Project::withTrashed()->find($this->project->id))->not->toBeNull();
});

// ── Authorization ───────────────────────────────────────────────────

it('cannot view another users project', function () {
    $otherUser = User::factory()->create();

    $this->actingAs($otherUser)
        ->getJson("/api/projects/{$this->project->id}")
        ->assertForbidden();
});

it('cannot update another users project', function () {
    $otherUser = User::factory()->create();

    $this->actingAs($otherUser)
        ->putJson("/api/projects/{$this->project->id}", ['project_name' => 'Hacked'])
        ->assertForbidden();
});

it('cannot delete another users project', function () {
    $otherUser = User::factory()->create();

    $this->actingAs($otherUser)
        ->deleteJson("/api/projects/{$this->project->id}")
        ->assertForbidden();
});

it('admin can view any project', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->getJson("/api/projects/{$this->project->id}")
        ->assertOk();
});

it('admin can update any project', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->putJson("/api/projects/{$this->project->id}", ['project_name' => 'Admin Edit'])
        ->assertOk();
});

it('requires authentication', function () {
    $this->getJson('/api/projects')
        ->assertUnauthorized();
});

// ── Filters & Search ────────────────────────────────────────────────

it('can filter projects by status', function () {
    Project::factory()->for($this->user)->inProgress()->create();

    $this->actingAs($this->user)
        ->getJson('/api/projects?status=in_progress')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('can search projects by name', function () {
    Project::factory()->for($this->user)->create(['project_name' => 'Unique Warehouse Project']);

    $this->actingAs($this->user)
        ->getJson('/api/projects?search=Unique Warehouse')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('can search projects by customer name', function () {
    Project::factory()->for($this->user)->create(['customer_name' => 'Acme Corp Special']);

    $this->actingAs($this->user)
        ->getJson('/api/projects?search=Acme Corp Special')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('can search projects by project number', function () {
    $this->actingAs($this->user)
        ->getJson("/api/projects?search={$this->project->project_number}")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

// ── Validation ──────────────────────────────────────────────────────

it('validates project_number is required', function () {
    $this->actingAs($this->user)
        ->postJson('/api/projects', [
            'project_name' => 'Test',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('project_number');
});

it('validates project_name is required', function () {
    $this->actingAs($this->user)
        ->postJson('/api/projects', [
            'project_number' => 'PRJ-001',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('project_name');
});

it('validates project_number is unique', function () {
    $this->actingAs($this->user)
        ->postJson('/api/projects', [
            'project_number' => $this->project->project_number,
            'project_name' => 'Duplicate',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('project_number');
});

it('validates status values', function () {
    $this->actingAs($this->user)
        ->postJson('/api/projects', [
            'project_number' => 'PRJ-VALID',
            'project_name' => 'Test',
            'status' => 'invalid_status',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');
});

// ── Buildings Management ────────────────────────────────────────────

it('can list buildings in a project', function () {
    Estimation::factory()
        ->count(3)
        ->for($this->user)
        ->create(['project_id' => $this->project->id]);

    $this->actingAs($this->user)
        ->getJson("/api/projects/{$this->project->id}/buildings")
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('can add a building to a project', function () {
    $estimation = Estimation::factory()->for($this->user)->create();

    $this->actingAs($this->user)
        ->postJson("/api/projects/{$this->project->id}/buildings", [
            'estimation_id' => $estimation->id,
        ])
        ->assertOk();

    expect($estimation->fresh()->project_id)->toBe($this->project->id);
});

it('cannot add another users estimation to project', function () {
    $otherUser = User::factory()->create();
    $estimation = Estimation::factory()->for($otherUser)->create();

    $this->actingAs($this->user)
        ->postJson("/api/projects/{$this->project->id}/buildings", [
            'estimation_id' => $estimation->id,
        ])
        ->assertForbidden();
});

it('can remove a building from a project', function () {
    $estimation = Estimation::factory()
        ->for($this->user)
        ->create(['project_id' => $this->project->id]);

    $this->actingAs($this->user)
        ->deleteJson("/api/projects/{$this->project->id}/buildings/{$estimation->id}")
        ->assertOk();

    expect($estimation->fresh()->project_id)->toBeNull();
});

it('cannot remove building that does not belong to project', function () {
    $estimation = Estimation::factory()->for($this->user)->create();

    $this->actingAs($this->user)
        ->deleteJson("/api/projects/{$this->project->id}/buildings/{$estimation->id}")
        ->assertUnprocessable();
});

it('can duplicate a building in a project', function () {
    $estimation = Estimation::factory()
        ->for($this->user)
        ->create([
            'project_id' => $this->project->id,
            'building_name' => 'Original Building',
            'input_data' => ['bay_spacing' => '6@6'],
        ]);

    $this->actingAs($this->user)
        ->postJson("/api/projects/{$this->project->id}/buildings/{$estimation->id}/duplicate")
        ->assertCreated()
        ->assertJsonPath('data.building_name', 'Original Building (Copy)')
        ->assertJsonPath('data.status', 'draft');

    expect($this->project->estimations()->count())->toBe(2);
});

it('cannot duplicate building not in project', function () {
    $estimation = Estimation::factory()->for($this->user)->create();

    $this->actingAs($this->user)
        ->postJson("/api/projects/{$this->project->id}/buildings/{$estimation->id}/duplicate")
        ->assertUnprocessable();
});

// ── Summary ─────────────────────────────────────────────────────────

it('returns correct summary aggregation', function () {
    Estimation::factory()
        ->for($this->user)
        ->calculated()
        ->create([
            'project_id' => $this->project->id,
            'total_weight_mt' => 50.0,
            'total_price_aed' => 400000.0,
        ]);

    Estimation::factory()
        ->for($this->user)
        ->calculated()
        ->create([
            'project_id' => $this->project->id,
            'total_weight_mt' => 30.0,
            'total_price_aed' => 200000.0,
        ]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/projects/{$this->project->id}")
        ->assertOk();

    $summary = $response->json('data.summary');
    expect($summary['building_count'])->toBe(2);
    expect((float) $summary['total_weight'])->toBe(80.0);
    expect((float) $summary['total_price'])->toBe(600000.0);
});

it('returns null summary values when no calculated estimations', function () {
    $emptyProject = Project::factory()->for($this->user)->create();

    $response = $this->actingAs($this->user)
        ->getJson("/api/projects/{$emptyProject->id}")
        ->assertOk();

    $summary = $response->json('data.summary');
    expect($summary['building_count'])->toBe(0);
    expect($summary['total_weight'])->toBeNull();
    expect($summary['total_price'])->toBeNull();
});

// ── History ─────────────────────────────────────────────────────────

it('can get project history', function () {
    // The project creation itself logs an activity
    $this->actingAs($this->user)
        ->putJson("/api/projects/{$this->project->id}", ['project_name' => 'Updated']);

    $this->actingAs($this->user)
        ->getJson("/api/projects/{$this->project->id}/history")
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'description', 'causer_name', 'created_at', 'properties'],
            ],
        ]);
});

// ── User only sees own projects ─────────────────────────────────────

it('user only sees own projects in list', function () {
    $otherUser = User::factory()->create();
    Project::factory()->count(2)->for($otherUser)->create();

    $this->actingAs($this->user)
        ->getJson('/api/projects')
        ->assertOk()
        ->assertJsonCount(1, 'data'); // only the beforeEach project
});

it('admin sees all projects in list', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Project::factory()->count(2)->for($this->user)->create();

    $this->actingAs($admin)
        ->getJson('/api/projects')
        ->assertOk()
        ->assertJsonCount(3, 'data'); // beforeEach + 2 new
});
