<?php

use App\Models\Estimation;
use App\Models\User;
use App\Services\Estimation\EstimationService;

// ── CRUD Happy Paths ──────────────────────────────────────────────────

it('can list user estimations', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    Estimation::factory()->count(3)->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/estimations');

    $response->assertSuccessful()
        ->assertJsonCount(3, 'data');
});

it('can list estimations with status filter', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    Estimation::factory()->count(2)->create(['user_id' => $user->id, 'status' => 'draft']);
    Estimation::factory()->calculated()->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/estimations?status=draft');

    $response->assertSuccessful()
        ->assertJsonCount(2, 'data');
});

it('returns only own estimations for regular user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    Estimation::factory()->count(2)->create(['user_id' => $user->id]);
    Estimation::factory()->count(3)->create(['user_id' => $otherUser->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/estimations');

    $response->assertSuccessful()
        ->assertJsonCount(2, 'data');
});

it('admin can list all estimations', function () {
    $admin = User::factory()->admin()->create();
    $otherUser = User::factory()->create();
    $token = $admin->createToken('test-token')->plainTextToken;

    Estimation::factory()->count(2)->create(['user_id' => $admin->id]);
    Estimation::factory()->count(3)->create(['user_id' => $otherUser->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/estimations');

    $response->assertSuccessful()
        ->assertJsonCount(5, 'data');
});

it('can create a new estimation', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/estimations', [
            'quote_number' => 'HQ-O-99999',
            'building_name' => 'Test Building',
            'project_name' => 'Test Project',
            'customer_name' => 'Test Customer',
            'input_data' => [
                'bay_spacing' => '1@6.865+1@9.104+2@9.144',
                'back_eave_height' => 6.0,
            ],
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.quote_number', 'HQ-O-99999')
        ->assertJsonPath('data.status', 'draft');

    $this->assertDatabaseHas('estimations', [
        'quote_number' => 'HQ-O-99999',
        'user_id' => $user->id,
    ]);
});

it('creates estimation with draft status', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/estimations', [
            'building_name' => 'Draft Test',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.status', 'draft');
});

it('can show a single estimation', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/estimations/{$estimation->id}");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => ['id', 'quote_number', 'status', 'input_data', 'created_at'],
        ]);
});

it('can update an estimation', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/estimations/{$estimation->id}", [
            'building_name' => 'Updated Building',
        ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.building_name', 'Updated Building');
});

it('resets status to draft when input data changes', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->calculated()->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/estimations/{$estimation->id}", [
            'input_data' => ['bay_spacing' => '4@9.0'],
        ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.status', 'draft');

    $this->assertDatabaseHas('estimations', [
        'id' => $estimation->id,
        'status' => 'draft',
    ]);
});

it('can soft delete an estimation', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/estimations/{$estimation->id}");

    $response->assertSuccessful()
        ->assertJson(['message' => 'Estimation deleted successfully.']);

    $this->assertSoftDeleted('estimations', ['id' => $estimation->id]);
});

it('returns paginated results', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    Estimation::factory()->count(20)->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/estimations');

    $response->assertSuccessful()
        ->assertJsonCount(15, 'data')
        ->assertJsonStructure(['data', 'links', 'meta']);
});

// ── Authorization ─────────────────────────────────────────────────────

it('cannot access estimations without authentication', function () {
    $response = $this->getJson('/api/estimations');

    $response->assertUnauthorized();
});

it('cannot view another user estimation', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/estimations/{$estimation->id}");

    $response->assertForbidden();
});

it('cannot update another user estimation', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/estimations/{$estimation->id}", [
            'building_name' => 'Hacked',
        ]);

    $response->assertForbidden();
});

it('cannot delete another user estimation', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/estimations/{$estimation->id}");

    $response->assertForbidden();
});

it('admin can view any estimation', function () {
    $admin = User::factory()->admin()->create();
    $otherUser = User::factory()->create();
    $token = $admin->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/estimations/{$estimation->id}");

    $response->assertSuccessful();
});

// ── Validation ────────────────────────────────────────────────────────

it('validates store estimation request', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/estimations', [
            'input_data' => 'not-an-array',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['input_data']);
});

it('validates estimation date format', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/estimations', [
            'estimation_date' => 'not-a-date',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['estimation_date']);
});

// ── Calculation ───────────────────────────────────────────────────────

it('can trigger calculation on estimation', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $estimation = Estimation::factory()->create([
        'user_id' => $user->id,
        'input_data' => ['bay_spacing' => '4@9.0', 'span_widths' => '28.5'],
    ]);

    $mockService = $this->mock(EstimationService::class);
    $mockService->shouldReceive('calculateAndSave')
        ->once()
        ->andReturnUsing(function (Estimation $est) {
            $est->update([
                'status' => 'calculated',
                'results_data' => ['summary' => ['total_weight_mt' => 49.54]],
                'total_weight_mt' => 49.54,
                'total_price_aed' => 424933.33,
            ]);

            return $est->fresh();
        });

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/estimations/{$estimation->id}/calculate");

    $response->assertSuccessful()
        ->assertJsonPath('data.status', 'calculated');
});

it('cannot calculate estimation without input data', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->create([
        'user_id' => $user->id,
        'input_data' => [],
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/estimations/{$estimation->id}/calculate");

    $response->assertStatus(422)
        ->assertJson(['message' => 'Estimation has no input data to calculate.']);
});

// ── Sheet Data ────────────────────────────────────────────────────────

it('can get detail sheet data', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $estimation = Estimation::factory()->create([
        'user_id' => $user->id,
        'status' => 'calculated',
        'results_data' => [
            'summary' => ['total_weight_mt' => 49.54],
            'detail' => [['item_code' => 'MF1', 'description' => 'Main Frame']],
        ],
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/estimations/{$estimation->id}/detail");

    $response->assertSuccessful()
        ->assertJsonPath('data.0.item_code', 'MF1');
});

it('can get recap sheet data', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $estimation = Estimation::factory()->create([
        'user_id' => $user->id,
        'status' => 'calculated',
        'results_data' => [
            'summary' => ['total_weight_mt' => 49.54, 'total_price_aed' => 424933.33],
        ],
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/estimations/{$estimation->id}/recap");

    $response->assertSuccessful()
        ->assertJsonPath('data.total_weight_mt', 49.54);
});

it('can get fcpbs sheet data', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $estimation = Estimation::factory()->create([
        'user_id' => $user->id,
        'status' => 'calculated',
        'results_data' => [
            'summary' => [],
            'fcpbs' => ['categories' => ['A' => ['name' => 'Main Frames']]],
        ],
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/estimations/{$estimation->id}/fcpbs");

    $response->assertSuccessful()
        ->assertJsonPath('data.categories.A.name', 'Main Frames');
});

it('can get sal sheet data', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $estimation = Estimation::factory()->create([
        'user_id' => $user->id,
        'status' => 'calculated',
        'results_data' => ['summary' => [], 'sal' => ['markup_ratio' => 0.97]],
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/estimations/{$estimation->id}/sal");

    $response->assertSuccessful()
        ->assertJsonPath('data.markup_ratio', 0.97);
});

it('can get boq sheet data', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $estimation = Estimation::factory()->create([
        'user_id' => $user->id,
        'status' => 'calculated',
        'results_data' => ['summary' => [], 'boq' => ['items' => []]],
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/estimations/{$estimation->id}/boq");

    $response->assertSuccessful();
});

it('can get jaf sheet data', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $estimation = Estimation::factory()->create([
        'user_id' => $user->id,
        'status' => 'calculated',
        'results_data' => ['summary' => [], 'jaf' => ['checklist' => []]],
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/estimations/{$estimation->id}/jaf");

    $response->assertSuccessful();
});

it('can get rawmat sheet data', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $estimation = Estimation::factory()->create([
        'user_id' => $user->id,
        'status' => 'calculated',
        'results_data' => [
            'summary' => [],
            'rawmat' => [
                'items' => [
                    ['no' => 1, 'code' => 'BU200', 'category' => 'Primary Steel'],
                ],
                'summary' => ['unique_materials' => 1, 'total_weight_kg' => 5016.0],
                'categories' => ['Primary Steel' => ['count' => 1, 'weight_kg' => 5016.0]],
            ],
        ],
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/estimations/{$estimation->id}/rawmat");

    $response->assertSuccessful()
        ->assertJsonPath('data.summary.unique_materials', 1)
        ->assertJsonPath('data.items.0.code', 'BU200');
});

it('returns 422 for sheet data when not calculated', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->create([
        'user_id' => $user->id,
        'status' => 'draft',
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/estimations/{$estimation->id}/detail");

    $response->assertStatus(422)
        ->assertJson(['message' => 'Estimation has not been calculated yet.']);
});

// ── Activity Logging ──────────────────────────────────────────────────

it('logs estimation creation activity', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/estimations', [
            'building_name' => 'Logged Building',
        ]);

    $this->assertDatabaseHas('activity_log', [
        'description' => 'created estimation',
        'causer_id' => $user->id,
        'causer_type' => User::class,
    ]);
});

it('logs estimation deletion activity', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->create(['user_id' => $user->id]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/estimations/{$estimation->id}");

    $this->assertDatabaseHas('activity_log', [
        'description' => 'deleted estimation',
        'causer_id' => $user->id,
        'causer_type' => User::class,
    ]);
});

it('logs estimation update activity with changed fields', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->create([
        'user_id' => $user->id,
        'building_name' => 'Original Building',
        'customer_name' => 'Original Customer',
    ]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/estimations/{$estimation->id}", [
            'building_name' => 'Updated Building',
            'customer_name' => 'Updated Customer',
        ]);

    $activity = \Spatie\Activitylog\Models\Activity::query()
        ->where('subject_type', Estimation::class)
        ->where('subject_id', $estimation->id)
        ->where('event', 'updated')
        ->latest()
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->causer_id)->toBe($user->id);
    expect($activity->properties['old']['building_name'])->toBe('Original Building');
    expect($activity->properties['attributes']['building_name'])->toBe('Updated Building');
    expect($activity->properties['old']['customer_name'])->toBe('Original Customer');
    expect($activity->properties['attributes']['customer_name'])->toBe('Updated Customer');
});

// ── Edge Cases ──────────────────────────────────────────────────────

it('handles estimation with empty results_data gracefully for sheet endpoints', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->calculated()->create([
        'user_id' => $user->id,
        'results_data' => [],
    ]);

    $sheets = ['detail', 'recap', 'fcpbs', 'sal', 'boq', 'jaf'];

    foreach ($sheets as $sheet) {
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/estimations/{$estimation->id}/{$sheet}");

        $response->assertSuccessful();
        expect($response->json('data'))->toBeNull();
    }
});

it('returns pagination metadata for estimations list', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    Estimation::factory()->count(3)->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/estimations');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data',
            'links',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
});

it('can filter estimations by finalized status', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    Estimation::factory()->create(['user_id' => $user->id, 'status' => 'draft']);
    Estimation::factory()->finalized()->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/estimations?status=finalized');

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.status'))->toBe('finalized');
});

// ── Estimation Items Sync ─────────────────────────────────────────────

it('syncs estimation items from detail data on calculation', function () {
    $estimation = Estimation::factory()->withItems()->create();

    // withItems() calls withResults() which has 5 detail items (2 headers + 3 data rows)
    expect($estimation->items)->toHaveCount(3);

    $mfrItem = $estimation->items->firstWhere('item_code', 'MFR');
    expect($mfrItem)->not->toBeNull();
    expect($mfrItem->description)->toBe('Main Frame Rafters');
    expect($mfrItem->unit)->toBe('m');
    expect((float) $mfrItem->quantity)->toBe(5.0);
    // weight_kg = weight_per_unit * size * qty = 35.2 * 28.5 * 5 = 5016.0
    expect((float) $mfrItem->weight_kg)->toBe(5016.0);
    expect((float) $mfrItem->rate)->toBe(3.5);
    // amount = weight_kg * rate = 5016.0 * 3.50 = 17556.0
    expect((float) $mfrItem->amount)->toBe(17556.0);
});

it('clears estimation items when input data changes via api', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->withItems()->create(['user_id' => $user->id]);

    expect($estimation->items()->count())->toBe(3);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/estimations/{$estimation->id}", [
            'input_data' => ['bay_spacing' => '5@8.0'],
        ]);

    expect($estimation->items()->count())->toBe(0);
    expect($estimation->fresh()->status)->toBe('draft');
});

it('clears estimation items when estimation is unlocked', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->withItems()->create([
        'user_id' => $user->id,
        'status' => 'finalized',
    ]);

    expect($estimation->items()->count())->toBe(3);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/estimations/{$estimation->id}/unlock");

    expect($estimation->items()->count())->toBe(0);
    expect($estimation->fresh()->status)->toBe('draft');
});
