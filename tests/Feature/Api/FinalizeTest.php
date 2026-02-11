<?php

use App\Models\Estimation;
use App\Models\User;

it('can finalize a calculated estimation', function () {
    $user = User::factory()->create();
    $estimation = Estimation::factory()->create([
        'user_id' => $user->id,
        'status' => 'calculated',
        'total_price_aed' => 50000,
        'total_weight_mt' => 10,
    ]);

    $this->actingAs($user)
        ->postJson("/api/estimations/{$estimation->id}/finalize")
        ->assertSuccessful()
        ->assertJsonPath('data.status', 'finalized');

    expect($estimation->fresh()->status)->toBe('finalized');
});

it('cannot finalize a draft estimation', function () {
    $user = User::factory()->create();
    $estimation = Estimation::factory()->create([
        'user_id' => $user->id,
        'status' => 'draft',
    ]);

    $this->actingAs($user)
        ->postJson("/api/estimations/{$estimation->id}/finalize")
        ->assertUnprocessable();

    expect($estimation->fresh()->status)->toBe('draft');
});

it('cannot finalize another user estimation', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $estimation = Estimation::factory()->create([
        'user_id' => $other->id,
        'status' => 'calculated',
    ]);

    $this->actingAs($user)
        ->postJson("/api/estimations/{$estimation->id}/finalize")
        ->assertForbidden();
});

it('admin can finalize any estimation', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $estimation = Estimation::factory()->create([
        'user_id' => $user->id,
        'status' => 'calculated',
    ]);

    $this->actingAs($admin)
        ->postJson("/api/estimations/{$estimation->id}/finalize")
        ->assertSuccessful()
        ->assertJsonPath('data.status', 'finalized');
});

it('can unlock a finalized estimation', function () {
    $user = User::factory()->create();
    $estimation = Estimation::factory()->create([
        'user_id' => $user->id,
        'status' => 'finalized',
        'results_data' => ['summary' => ['total_weight_mt' => 10]],
        'total_price_aed' => 50000,
        'total_weight_mt' => 10,
    ]);

    $this->actingAs($user)
        ->postJson("/api/estimations/{$estimation->id}/unlock")
        ->assertSuccessful()
        ->assertJsonPath('data.status', 'draft');

    $fresh = $estimation->fresh();
    expect($fresh->status)->toBe('draft')
        ->and($fresh->results_data)->toBeNull()
        ->and($fresh->total_weight_mt)->toBeNull()
        ->and($fresh->total_price_aed)->toBeNull();
});

it('cannot unlock a non-finalized estimation', function () {
    $user = User::factory()->create();
    $estimation = Estimation::factory()->create([
        'user_id' => $user->id,
        'status' => 'calculated',
    ]);

    $this->actingAs($user)
        ->postJson("/api/estimations/{$estimation->id}/unlock")
        ->assertUnprocessable();

    expect($estimation->fresh()->status)->toBe('calculated');
});

it('cannot unlock another user estimation', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $estimation = Estimation::factory()->create([
        'user_id' => $other->id,
        'status' => 'finalized',
    ]);

    $this->actingAs($user)
        ->postJson("/api/estimations/{$estimation->id}/unlock")
        ->assertForbidden();
});

it('requires authentication for finalize', function () {
    $estimation = Estimation::factory()->create(['status' => 'calculated']);

    $this->postJson("/api/estimations/{$estimation->id}/finalize")
        ->assertUnauthorized();
});

it('requires authentication for unlock', function () {
    $estimation = Estimation::factory()->create(['status' => 'finalized']);

    $this->postJson("/api/estimations/{$estimation->id}/unlock")
        ->assertUnauthorized();
});

it('logs finalize activity', function () {
    $user = User::factory()->create();
    $estimation = Estimation::factory()->create([
        'user_id' => $user->id,
        'status' => 'calculated',
    ]);

    $this->actingAs($user)
        ->postJson("/api/estimations/{$estimation->id}/finalize")
        ->assertSuccessful();

    $this->assertDatabaseHas('activity_log', [
        'description' => 'finalized estimation',
        'subject_id' => $estimation->id,
        'causer_id' => $user->id,
    ]);
});

it('logs unlock activity', function () {
    $user = User::factory()->create();
    $estimation = Estimation::factory()->create([
        'user_id' => $user->id,
        'status' => 'finalized',
    ]);

    $this->actingAs($user)
        ->postJson("/api/estimations/{$estimation->id}/unlock")
        ->assertSuccessful();

    $this->assertDatabaseHas('activity_log', [
        'description' => 'unlocked estimation',
        'subject_id' => $estimation->id,
        'causer_id' => $user->id,
    ]);
});
