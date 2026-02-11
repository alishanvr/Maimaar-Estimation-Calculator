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

it('can compare two estimations', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/estimations/compare', [
            'ids' => [$this->estimation1->id, $this->estimation2->id],
        ]);

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'quote_number', 'revision_no', 'building_name', 'status', 'total_weight_mt', 'total_price_aed', 'summary', 'input_data'],
            ],
        ]);
});

it('requires exactly two ids', function () {
    $this->actingAs($this->user)
        ->postJson('/api/estimations/compare', [
            'ids' => [$this->estimation1->id],
        ])
        ->assertUnprocessable();

    $est3 = Estimation::factory()->for($this->user)->create();

    $this->actingAs($this->user)
        ->postJson('/api/estimations/compare', [
            'ids' => [$this->estimation1->id, $this->estimation2->id, $est3->id],
        ])
        ->assertUnprocessable();
});

it('validates estimation ids exist', function () {
    $this->actingAs($this->user)
        ->postJson('/api/estimations/compare', [
            'ids' => [$this->estimation1->id, 99999],
        ])
        ->assertUnprocessable();
});

it('requires authorization for both estimations', function () {
    $otherUser = User::factory()->create();
    $otherEstimation = Estimation::factory()->for($otherUser)->create();

    $this->actingAs($this->user)
        ->postJson('/api/estimations/compare', [
            'ids' => [$this->estimation1->id, $otherEstimation->id],
        ])
        ->assertForbidden();
});

it('admin can compare any two estimations', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->postJson('/api/estimations/compare', [
            'ids' => [$this->estimation1->id, $this->estimation2->id],
        ])
        ->assertOk();
});

it('requires authentication', function () {
    $this->postJson('/api/estimations/compare', [
        'ids' => [$this->estimation1->id, $this->estimation2->id],
    ])
        ->assertUnauthorized();
});

it('returns summary data when available', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/estimations/compare', [
            'ids' => [$this->estimation1->id, $this->estimation2->id],
        ]);

    $data = $response->json('data');
    expect($data[0]['summary'])->not->toBeNull()
        ->and($data[0]['summary'])->toHaveKeys(['total_weight_kg', 'total_weight_mt', 'total_price_aed']);
});

it('handles comparison of draft estimations', function () {
    $draft1 = Estimation::factory()->for($this->user)->create();
    $draft2 = Estimation::factory()->for($this->user)->create();

    $response = $this->actingAs($this->user)
        ->postJson('/api/estimations/compare', [
            'ids' => [$draft1->id, $draft2->id],
        ]);

    $response->assertOk();
    $data = $response->json('data');
    expect($data[0]['summary'])->toBeNull()
        ->and($data[1]['summary'])->toBeNull();
});
