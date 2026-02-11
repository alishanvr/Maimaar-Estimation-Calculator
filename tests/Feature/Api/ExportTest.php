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
