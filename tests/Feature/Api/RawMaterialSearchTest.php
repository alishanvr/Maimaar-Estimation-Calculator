<?php

use App\Models\RawMaterial;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

// ── Authentication ──────────────────────────────────────────────────

it('requires authentication for raw material search', function () {
    $this->getJson('/api/raw-materials/search?q=test')
        ->assertUnauthorized();
});

// ── Raw Material Search ─────────────────────────────────────────────

it('can search raw materials by code', function () {
    RawMaterial::factory()->create(['code' => 'RM-STEEL-001']);
    RawMaterial::factory()->create(['code' => 'RM-ALUM-001']);

    $this->actingAs($this->user)
        ->getJson('/api/raw-materials/search?q=STEEL')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.code', 'RM-STEEL-001');
});

it('can search raw materials by description', function () {
    RawMaterial::factory()->create([
        'code' => 'RM-001',
        'description' => 'Hot Rolled Steel Coil',
    ]);
    RawMaterial::factory()->create([
        'code' => 'RM-002',
        'description' => 'Aluminium Sheet',
    ]);

    $this->actingAs($this->user)
        ->getJson('/api/raw-materials/search?q=Hot+Rolled')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.code', 'RM-001');
});

it('returns correct raw material fields in search response', function () {
    RawMaterial::factory()->create([
        'code' => 'RM-FIELDS-TEST',
        'description' => 'Test Material',
        'weight_per_sqm' => 7.8500,
        'unit' => 'kg/m²',
    ]);

    $this->actingAs($this->user)
        ->getJson('/api/raw-materials/search?q=RM-FIELDS')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'code', 'description', 'weight_per_sqm', 'unit', 'metadata'],
            ],
        ]);
});

it('requires q parameter for raw material search', function () {
    $this->actingAs($this->user)
        ->getJson('/api/raw-materials/search')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('q');
});

it('limits raw material search results to 50', function () {
    RawMaterial::factory()->count(60)->create(['code' => fn () => 'BULK-'.fake()->unique()->numerify('###')]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/raw-materials/search?q=BULK')
        ->assertOk();

    expect(count($response->json('data')))->toBeLessThanOrEqual(50);
});

it('returns partial matches for raw material search', function () {
    RawMaterial::factory()->create(['code' => 'GALV-SHEET-0.5']);

    $this->actingAs($this->user)
        ->getJson('/api/raw-materials/search?q=GALV')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});
