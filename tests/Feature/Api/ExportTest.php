<?php

use App\Models\Estimation;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

it('can export BOQ as PDF', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->withResults()->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->get("/api/estimations/{$estimation->id}/export/boq");

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('BOQ-');
});

it('cannot export BOQ without calculation', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/estimations/{$estimation->id}/export/boq");

    $response->assertStatus(422)
        ->assertJson(['message' => 'Estimation has not been calculated yet.']);
});

it('requires authentication for BOQ export', function () {
    $estimation = Estimation::factory()->withResults()->create();

    $response = $this->getJson("/api/estimations/{$estimation->id}/export/boq");

    $response->assertUnauthorized();
});

it('denies BOQ export to other users', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $token = $otherUser->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->withResults()->create(['user_id' => $owner->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/estimations/{$estimation->id}/export/boq");

    $response->assertForbidden();
});

it('can export JAF as PDF', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->withResults()->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->get("/api/estimations/{$estimation->id}/export/jaf");

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('JAF-');
});

it('cannot export JAF without calculation', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/estimations/{$estimation->id}/export/jaf");

    $response->assertStatus(422)
        ->assertJson(['message' => 'Estimation has not been calculated yet.']);
});

it('requires authentication for JAF export', function () {
    $estimation = Estimation::factory()->withResults()->create();

    $response = $this->getJson("/api/estimations/{$estimation->id}/export/jaf");

    $response->assertUnauthorized();
});

it('logs BOQ export activity', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->withResults()->create(['user_id' => $user->id]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->get("/api/estimations/{$estimation->id}/export/boq");

    $log = Activity::query()
        ->where('description', 'exported BOQ PDF')
        ->where('subject_id', $estimation->id)
        ->first();

    expect($log)->not->toBeNull();
    expect($log->causer_id)->toBe($user->id);
});

// ── Recap Export ─────────────────────────────────────────────────

it('can export Recap as PDF', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->withResults()->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->get("/api/estimations/{$estimation->id}/export/recap");

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('Recap-');
});

it('cannot export Recap without calculation', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/estimations/{$estimation->id}/export/recap");

    $response->assertStatus(422)
        ->assertJson(['message' => 'Estimation has not been calculated yet.']);
});

it('logs Recap export activity', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->withResults()->create(['user_id' => $user->id]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->get("/api/estimations/{$estimation->id}/export/recap");

    $log = Activity::query()
        ->where('description', 'exported Recap PDF')
        ->where('subject_id', $estimation->id)
        ->first();

    expect($log)->not->toBeNull();
    expect($log->causer_id)->toBe($user->id);
});

// ── Detail Export ────────────────────────────────────────────────

it('can export Detail as PDF', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->withResults()->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->get("/api/estimations/{$estimation->id}/export/detail");

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('Detail-');
});

it('cannot export Detail without calculation', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/estimations/{$estimation->id}/export/detail");

    $response->assertStatus(422)
        ->assertJson(['message' => 'Estimation has not been calculated yet.']);
});

it('logs Detail export activity', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->withResults()->create(['user_id' => $user->id]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->get("/api/estimations/{$estimation->id}/export/detail");

    $log = Activity::query()
        ->where('description', 'exported Detail PDF')
        ->where('subject_id', $estimation->id)
        ->first();

    expect($log)->not->toBeNull();
    expect($log->causer_id)->toBe($user->id);
});

// ── FCPBS Export ─────────────────────────────────────────────────

it('can export FCPBS as PDF', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->withResults()->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->get("/api/estimations/{$estimation->id}/export/fcpbs");

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('FCPBS-');
});

it('cannot export FCPBS without calculation', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/estimations/{$estimation->id}/export/fcpbs");

    $response->assertStatus(422)
        ->assertJson(['message' => 'Estimation has not been calculated yet.']);
});

it('logs FCPBS export activity', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->withResults()->create(['user_id' => $user->id]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->get("/api/estimations/{$estimation->id}/export/fcpbs");

    $log = Activity::query()
        ->where('description', 'exported FCPBS PDF')
        ->where('subject_id', $estimation->id)
        ->first();

    expect($log)->not->toBeNull();
    expect($log->causer_id)->toBe($user->id);
});

// ── SAL Export ───────────────────────────────────────────────────

it('can export SAL as PDF', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->withResults()->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->get("/api/estimations/{$estimation->id}/export/sal");

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('SAL-');
});

it('cannot export SAL without calculation', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/estimations/{$estimation->id}/export/sal");

    $response->assertStatus(422)
        ->assertJson(['message' => 'Estimation has not been calculated yet.']);
});

it('logs SAL export activity', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->withResults()->create(['user_id' => $user->id]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->get("/api/estimations/{$estimation->id}/export/sal");

    $log = Activity::query()
        ->where('description', 'exported SAL PDF')
        ->where('subject_id', $estimation->id)
        ->first();

    expect($log)->not->toBeNull();
    expect($log->causer_id)->toBe($user->id);
});

// ── RAWMAT Export ────────────────────────────────────────────────

it('can export RAWMAT as PDF', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->withResults()->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->get("/api/estimations/{$estimation->id}/export/rawmat");

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('RAWMAT-');
});

it('cannot export RAWMAT without calculation', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->create(['user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/estimations/{$estimation->id}/export/rawmat");

    $response->assertStatus(422)
        ->assertJson(['message' => 'Estimation has not been calculated yet.']);
});

it('logs RAWMAT export activity', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;
    $estimation = Estimation::factory()->withResults()->create(['user_id' => $user->id]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->get("/api/estimations/{$estimation->id}/export/rawmat");

    $log = Activity::query()
        ->where('description', 'exported RAWMAT PDF')
        ->where('subject_id', $estimation->id)
        ->first();

    expect($log)->not->toBeNull();
    expect($log->causer_id)->toBe($user->id);
});
