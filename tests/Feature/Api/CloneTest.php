<?php

use App\Models\Estimation;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->estimation = Estimation::factory()
        ->for($this->user)
        ->withResults()
        ->create(['input_data' => ['bay_spacing' => '1@6.865', 'span_widths' => '1@28.5']]);
});

it('can clone an estimation', function () {
    $response = $this->actingAs($this->user)
        ->postJson("/api/estimations/{$this->estimation->id}/clone");

    $response->assertStatus(201)
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.total_weight_mt', null)
        ->assertJsonPath('data.total_price_aed', null);

    expect(Estimation::count())->toBe(2);
});

it('cloned estimation belongs to current user', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)
        ->postJson("/api/estimations/{$this->estimation->id}/clone");

    $response->assertStatus(201);

    $cloneId = $response->json('data.id');
    expect(Estimation::find($cloneId)->user_id)->toBe($admin->id);
});

it('copies input data but clears results', function () {
    $response = $this->actingAs($this->user)
        ->postJson("/api/estimations/{$this->estimation->id}/clone");

    $response->assertStatus(201);

    $clone = Estimation::find($response->json('data.id'));
    expect($clone->input_data)->toBe($this->estimation->input_data)
        ->and($clone->results_data)->toBeNull()
        ->and($clone->status)->toBe('draft');
});

it('cannot clone another user estimation', function () {
    $otherUser = User::factory()->create();

    $this->actingAs($otherUser)
        ->postJson("/api/estimations/{$this->estimation->id}/clone")
        ->assertForbidden();
});

it('admin can clone any estimation', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->postJson("/api/estimations/{$this->estimation->id}/clone")
        ->assertStatus(201);
});

it('requires authentication to clone', function () {
    $this->postJson("/api/estimations/{$this->estimation->id}/clone")
        ->assertUnauthorized();
});

it('logs clone activity with source id', function () {
    $this->actingAs($this->user)
        ->postJson("/api/estimations/{$this->estimation->id}/clone");

    $this->assertDatabaseHas('activity_log', [
        'description' => 'cloned estimation',
    ]);
});

it('sets estimation date to today on clone', function () {
    $response = $this->actingAs($this->user)
        ->postJson("/api/estimations/{$this->estimation->id}/clone");

    $clone = Estimation::find($response->json('data.id'));
    expect($clone->estimation_date->format('Y-m-d'))->toBe(now()->format('Y-m-d'));
});

it('clone from calculated estimation resets to draft', function () {
    expect($this->estimation->status)->toBe('calculated');

    $response = $this->actingAs($this->user)
        ->postJson("/api/estimations/{$this->estimation->id}/clone");

    $response->assertJsonPath('data.status', 'draft');
});

it('clone from finalized estimation resets to draft', function () {
    $finalized = Estimation::factory()
        ->for($this->user)
        ->finalized()
        ->create();

    $response = $this->actingAs($this->user)
        ->postJson("/api/estimations/{$finalized->id}/clone");

    $response->assertJsonPath('data.status', 'draft');
});
