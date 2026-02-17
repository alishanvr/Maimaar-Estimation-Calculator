<?php

use App\Models\AnalyticsMetric;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('requires authentication', function () {
    $this->getJson('/api/analytics/aggregated')
        ->assertUnauthorized();
});

it('returns aggregated metrics for admin', function () {
    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);

    AnalyticsMetric::factory()->create([
        'metric_name' => 'monthly_estimations',
        'metric_value' => 42,
        'period' => '2026-02',
    ]);

    $this->getJson('/api/analytics/aggregated')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.metric_name', 'monthly_estimations')
        ->assertJsonPath('data.0.metric_value', 42);
});

it('non-admin sees only own metrics and system-wide', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    Sanctum::actingAs($user);

    AnalyticsMetric::factory()->create([
        'user_id' => $user->id,
        'metric_name' => 'total_weight',
        'period' => '2026-02',
    ]);

    AnalyticsMetric::factory()->create([
        'user_id' => $otherUser->id,
        'metric_name' => 'total_weight',
        'period' => '2026-02',
    ]);

    AnalyticsMetric::factory()->create([
        'user_id' => null,
        'metric_name' => 'total_weight',
        'period' => '2026-02',
    ]);

    $response = $this->getJson('/api/analytics/aggregated')
        ->assertSuccessful();

    expect($response->json('data'))->toHaveCount(2);
});

it('filters by period', function () {
    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);

    AnalyticsMetric::factory()->create(['period' => '2026-01']);
    AnalyticsMetric::factory()->create(['period' => '2026-02']);

    $this->getJson('/api/analytics/aggregated?period=2026-01')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data');
});

it('filters by metric_name', function () {
    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);

    AnalyticsMetric::factory()->create(['metric_name' => 'total_weight']);
    AnalyticsMetric::factory()->create(['metric_name' => 'total_revenue']);

    $this->getJson('/api/analytics/aggregated?metric_name=total_weight')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.metric_name', 'total_weight');
});

it('validates period format', function () {
    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);

    $this->getJson('/api/analytics/aggregated?period=invalid')
        ->assertUnprocessable();
});

it('validates metric_name value', function () {
    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);

    $this->getJson('/api/analytics/aggregated?metric_name=nonexistent')
        ->assertUnprocessable();
});
