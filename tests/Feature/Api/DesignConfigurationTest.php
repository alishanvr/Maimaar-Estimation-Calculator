<?php

use App\Models\DesignConfiguration;
use App\Models\User;

it('can list design configurations by category', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    DesignConfiguration::factory()->create(['category' => 'frame_type', 'key' => 'rigid', 'value' => 'Rigid Frame']);
    DesignConfiguration::factory()->create(['category' => 'frame_type', 'key' => 'truss', 'value' => 'Truss Frame']);
    DesignConfiguration::factory()->create(['category' => 'base_condition', 'key' => 'pinned', 'value' => 'Pinned Base']);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/design-configurations?category=frame_type');

    $response->assertSuccessful()
        ->assertJsonCount(2, 'data');
});

it('requires category parameter', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/design-configurations');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['category']);
});

it('returns empty array for unknown category', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/design-configurations?category=nonexistent');

    $response->assertSuccessful()
        ->assertJsonCount(0, 'data');
});

it('returns ordered by sort order', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    DesignConfiguration::factory()->create(['category' => 'test_cat', 'key' => 'second', 'sort_order' => 2]);
    DesignConfiguration::factory()->create(['category' => 'test_cat', 'key' => 'first', 'sort_order' => 1]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/design-configurations?category=test_cat');

    $response->assertSuccessful();
    $data = $response->json('data');
    expect($data[0]['key'])->toBe('first');
    expect($data[1]['key'])->toBe('second');
});

it('can access freight codes endpoint', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    DesignConfiguration::factory()->create(['category' => 'freight_code', 'key' => 'fc1', 'value' => 'Standard']);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/freight-codes');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data');
});

it('can access paint systems endpoint', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    DesignConfiguration::factory()->create(['category' => 'paint_system', 'key' => 'ps1', 'value' => 'Primer Only']);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/paint-systems');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data');
});

it('requires authentication for design configurations', function () {
    $response = $this->getJson('/api/design-configurations?category=frame_type');

    $response->assertUnauthorized();
});

it('returns correct resource structure', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    DesignConfiguration::factory()->create(['category' => 'test_cat']);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/design-configurations?category=test_cat');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'category', 'key', 'value', 'label', 'sort_order', 'metadata'],
            ],
        ]);
});
