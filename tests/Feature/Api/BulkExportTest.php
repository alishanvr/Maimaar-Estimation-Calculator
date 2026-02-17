<?php

use App\Models\Estimation;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->estimation1 = Estimation::factory()
        ->for($this->user)
        ->withResults()
        ->create();
    $this->estimation2 = Estimation::factory()
        ->for($this->user)
        ->withResults()
        ->create();
});

it('can bulk export estimations as zip', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/estimations/bulk-export', [
            'ids' => [$this->estimation1->id, $this->estimation2->id],
            'sheets' => ['recap', 'boq'],
        ]);

    $response->assertOk()
        ->assertHeader('content-type', 'application/zip');
});

it('validates ids are required', function () {
    $this->actingAs($this->user)
        ->postJson('/api/estimations/bulk-export', [
            'ids' => [],
            'sheets' => ['recap'],
        ])
        ->assertUnprocessable();
});

it('validates sheets are required', function () {
    $this->actingAs($this->user)
        ->postJson('/api/estimations/bulk-export', [
            'ids' => [$this->estimation1->id],
            'sheets' => [],
        ])
        ->assertUnprocessable();
});

it('validates sheet names', function () {
    $this->actingAs($this->user)
        ->postJson('/api/estimations/bulk-export', [
            'ids' => [$this->estimation1->id],
            'sheets' => ['invalid_sheet'],
        ])
        ->assertUnprocessable();
});

it('accepts rawmat as valid sheet name', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/estimations/bulk-export', [
            'ids' => [$this->estimation1->id],
            'sheets' => ['rawmat'],
        ]);

    $response->assertOk()
        ->assertHeader('content-type', 'application/zip');
});

it('limits to 20 estimations', function () {
    $ids = Estimation::factory()
        ->for($this->user)
        ->count(21)
        ->create()
        ->pluck('id')
        ->all();

    $this->actingAs($this->user)
        ->postJson('/api/estimations/bulk-export', [
            'ids' => $ids,
            'sheets' => ['recap'],
        ])
        ->assertUnprocessable();
});

it('skips uncalculated estimations', function () {
    $draft = Estimation::factory()->for($this->user)->create();

    $response = $this->actingAs($this->user)
        ->postJson('/api/estimations/bulk-export', [
            'ids' => [$this->estimation1->id, $draft->id],
            'sheets' => ['recap'],
        ]);

    // Should succeed since at least one estimation is calculated
    $response->assertOk();
});

it('returns 422 when all estimations are drafts', function () {
    $draft1 = Estimation::factory()->for($this->user)->create();
    $draft2 = Estimation::factory()->for($this->user)->create();

    $this->actingAs($this->user)
        ->postJson('/api/estimations/bulk-export', [
            'ids' => [$draft1->id, $draft2->id],
            'sheets' => ['recap'],
        ])
        ->assertUnprocessable();
});

it('requires authorization for all estimations', function () {
    $otherUser = User::factory()->create();
    $otherEstimation = Estimation::factory()->for($otherUser)->withResults()->create();

    $this->actingAs($this->user)
        ->postJson('/api/estimations/bulk-export', [
            'ids' => [$this->estimation1->id, $otherEstimation->id],
            'sheets' => ['recap'],
        ])
        ->assertForbidden();
});

it('admin can bulk export any estimations', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->postJson('/api/estimations/bulk-export', [
            'ids' => [$this->estimation1->id, $this->estimation2->id],
            'sheets' => ['recap'],
        ])
        ->assertOk();
});

it('requires authentication', function () {
    $this->postJson('/api/estimations/bulk-export', [
        'ids' => [$this->estimation1->id],
        'sheets' => ['recap'],
    ])
        ->assertUnauthorized();
});
