<?php

use App\Models\Estimation;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->estimation = Estimation::factory()
        ->for($this->user)
        ->create([
            'revision_no' => 'R00',
            'input_data' => ['bay_spacing' => '1@6.865'],
        ]);
});

it('can create a revision from an estimation', function () {
    $response = $this->actingAs($this->user)
        ->postJson("/api/estimations/{$this->estimation->id}/revision");

    $response->assertStatus(201)
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.parent_id', $this->estimation->id);

    expect(Estimation::count())->toBe(2);
});

it('increments revision number correctly', function () {
    $response = $this->actingAs($this->user)
        ->postJson("/api/estimations/{$this->estimation->id}/revision");

    $response->assertJsonPath('data.revision_no', 'R01');

    $rev1 = Estimation::find($response->json('data.id'));

    $response2 = $this->actingAs($this->user)
        ->postJson("/api/estimations/{$rev1->id}/revision");

    $response2->assertJsonPath('data.revision_no', 'R02');
});

it('returns revision chain for an estimation', function () {
    $rev1 = Estimation::factory()
        ->for($this->user)
        ->create(['parent_id' => $this->estimation->id, 'revision_no' => 'R01', 'quote_number' => $this->estimation->quote_number]);

    $rev2 = Estimation::factory()
        ->for($this->user)
        ->create(['parent_id' => $rev1->id, 'revision_no' => 'R02', 'quote_number' => $this->estimation->quote_number]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/estimations/{$this->estimation->id}/revisions");

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

it('marks current estimation in revision chain', function () {
    Estimation::factory()
        ->for($this->user)
        ->create(['parent_id' => $this->estimation->id, 'revision_no' => 'R01']);

    $response = $this->actingAs($this->user)
        ->getJson("/api/estimations/{$this->estimation->id}/revisions");

    $data = collect($response->json('data'));
    $current = $data->firstWhere('is_current', true);
    expect($current['id'])->toBe($this->estimation->id);
});

it('revision chain works from any node', function () {
    $rev1 = Estimation::factory()
        ->for($this->user)
        ->create(['parent_id' => $this->estimation->id, 'revision_no' => 'R01']);

    $rev2 = Estimation::factory()
        ->for($this->user)
        ->create(['parent_id' => $rev1->id, 'revision_no' => 'R02']);

    // Call from the middle node
    $response = $this->actingAs($this->user)
        ->getJson("/api/estimations/{$rev1->id}/revisions");

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

it('cannot create revision for another user estimation', function () {
    $otherUser = User::factory()->create();

    $this->actingAs($otherUser)
        ->postJson("/api/estimations/{$this->estimation->id}/revision")
        ->assertForbidden();
});

it('admin can create revision for any estimation', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->postJson("/api/estimations/{$this->estimation->id}/revision")
        ->assertStatus(201);
});

it('requires authentication for revision endpoints', function () {
    $this->postJson("/api/estimations/{$this->estimation->id}/revision")
        ->assertUnauthorized();

    $this->getJson("/api/estimations/{$this->estimation->id}/revisions")
        ->assertUnauthorized();
});

it('logs revision creation activity', function () {
    $this->actingAs($this->user)
        ->postJson("/api/estimations/{$this->estimation->id}/revision");

    $this->assertDatabaseHas('activity_log', [
        'description' => 'created revision',
    ]);
});

it('revision inherits quote number from parent', function () {
    $response = $this->actingAs($this->user)
        ->postJson("/api/estimations/{$this->estimation->id}/revision");

    $response->assertJsonPath('data.quote_number', $this->estimation->quote_number);
});
