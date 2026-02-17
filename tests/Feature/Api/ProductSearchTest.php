<?php

use App\Models\MbsdbProduct;
use App\Models\SsdbProduct;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

// ── Authentication ──────────────────────────────────────────────────

it('requires authentication for product search', function () {
    $this->getJson('/api/products/search?q=test')
        ->assertUnauthorized();
});

it('requires authentication for product show', function () {
    $this->getJson('/api/products/TEST-CODE')
        ->assertUnauthorized();
});

it('requires authentication for structural steel search', function () {
    $this->getJson('/api/structural-steel/search?q=test')
        ->assertUnauthorized();
});

// ── MBSDB Product Search ────────────────────────────────────────────

it('can search MBSDB products by code', function () {
    MbsdbProduct::factory()->create(['code' => 'PANEL-ROOF-001']);
    MbsdbProduct::factory()->create(['code' => 'BOLT-HEX-001']);

    $this->actingAs($this->user)
        ->getJson('/api/products/search?q=PANEL')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.code', 'PANEL-ROOF-001');
});

it('can search MBSDB products by description', function () {
    MbsdbProduct::factory()->create([
        'code' => 'MBS-001',
        'description' => 'Galvanized Steel Panel 0.5mm',
    ]);
    MbsdbProduct::factory()->create([
        'code' => 'MBS-002',
        'description' => 'Hex Bolt M16',
    ]);

    $this->actingAs($this->user)
        ->getJson('/api/products/search?q=Galvanized')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.code', 'MBS-001');
});

it('can filter product search by category', function () {
    MbsdbProduct::factory()->create([
        'code' => 'PAN-001',
        'category' => 'Panel',
    ]);
    MbsdbProduct::factory()->create([
        'code' => 'PAN-002',
        'category' => 'Gang',
    ]);

    $this->actingAs($this->user)
        ->getJson('/api/products/search?q=PAN&category=Panel')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.category', 'Panel');
});

it('returns correct product fields in search response', function () {
    MbsdbProduct::factory()->create([
        'code' => 'TEST-PRODUCT',
        'description' => 'Test Product Description',
        'unit' => 'kg',
        'category' => 'Panel',
        'rate' => 12.5000,
        'rate_type' => 'kg',
    ]);

    $this->actingAs($this->user)
        ->getJson('/api/products/search?q=TEST-PRODUCT')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'code', 'description', 'unit', 'category', 'rate', 'rate_type', 'metadata'],
            ],
        ]);
});

it('limits search results to 50', function () {
    MbsdbProduct::factory()->count(60)->create(['code' => fn () => 'BULK-'.fake()->unique()->numerify('###')]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/products/search?q=BULK')
        ->assertOk();

    expect(count($response->json('data')))->toBeLessThanOrEqual(50);
});

it('requires q parameter for product search', function () {
    $this->actingAs($this->user)
        ->getJson('/api/products/search')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('q');
});

it('returns partial matches for product search', function () {
    MbsdbProduct::factory()->create(['code' => 'M45-250-0.5AZ']);

    $this->actingAs($this->user)
        ->getJson('/api/products/search?q=M45')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

// ── MBSDB Product Show ──────────────────────────────────────────────

it('can get product by exact code', function () {
    MbsdbProduct::factory()->create(['code' => 'EXACT-CODE-001']);

    $this->actingAs($this->user)
        ->getJson('/api/products/EXACT-CODE-001')
        ->assertOk()
        ->assertJsonPath('data.code', 'EXACT-CODE-001');
});

it('returns 404 for unknown product code', function () {
    $this->actingAs($this->user)
        ->getJson('/api/products/NONEXISTENT-CODE')
        ->assertNotFound();
});

// ── SSDB Structural Steel Search ────────────────────────────────────

it('can search SSDB products by code', function () {
    SsdbProduct::factory()->create(['code' => 'UB-203x133x25']);
    SsdbProduct::factory()->create(['code' => 'UC-305x305x97']);

    $this->actingAs($this->user)
        ->getJson('/api/structural-steel/search?q=UB')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.code', 'UB-203x133x25');
});

it('can search SSDB products by description', function () {
    SsdbProduct::factory()->create([
        'code' => 'SS-001',
        'description' => 'Universal Beam 203x133',
    ]);

    $this->actingAs($this->user)
        ->getJson('/api/structural-steel/search?q=Universal')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('returns correct SSDB fields in search response', function () {
    SsdbProduct::factory()->create(['code' => 'SS-FIELDS-TEST']);

    $this->actingAs($this->user)
        ->getJson('/api/structural-steel/search?q=SS-FIELDS')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'code', 'description', 'unit', 'category', 'rate', 'grade', 'metadata'],
            ],
        ]);
});

it('requires q parameter for structural steel search', function () {
    $this->actingAs($this->user)
        ->getJson('/api/structural-steel/search')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('q');
});

it('limits structural steel search results to 50', function () {
    SsdbProduct::factory()->count(60)->create(['code' => fn () => 'STEEL-'.fake()->unique()->numerify('###')]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/structural-steel/search?q=STEEL')
        ->assertOk();

    expect(count($response->json('data')))->toBeLessThanOrEqual(50);
});
