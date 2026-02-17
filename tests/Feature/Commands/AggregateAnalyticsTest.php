<?php

use App\Models\AnalyticsMetric;
use App\Models\Estimation;
use App\Models\User;

it('creates system-wide metrics for the current period', function () {
    $user = User::factory()->admin()->create();
    Estimation::factory()->for($user)->create([
        'estimation_date' => now()->format('Y-m').'-15',
        'status' => 'calculated',
        'total_weight_mt' => 100.0,
        'total_price_aed' => 50000.0,
    ]);

    $this->artisan('analytics:aggregate')
        ->assertSuccessful();

    $period = now()->format('Y-m');

    expect(AnalyticsMetric::systemWide()->forPeriod($period)->count())->toBe(4);
    expect(AnalyticsMetric::systemWide()->forMetric('monthly_estimations')->forPeriod($period)->first()->metric_value)
        ->toBe('1.0000');
    expect((float) AnalyticsMetric::systemWide()->forMetric('total_weight')->forPeriod($period)->first()->metric_value)
        ->toBe(100.0);
    expect((float) AnalyticsMetric::systemWide()->forMetric('total_revenue')->forPeriod($period)->first()->metric_value)
        ->toBe(50000.0);
});

it('creates per-user metrics', function () {
    $user = User::factory()->create();
    Estimation::factory()->for($user)->create([
        'estimation_date' => now()->format('Y-m').'-10',
        'status' => 'draft',
        'total_weight_mt' => 50.0,
        'total_price_aed' => 25000.0,
    ]);

    $this->artisan('analytics:aggregate')
        ->assertSuccessful();

    $period = now()->format('Y-m');
    $userMetrics = AnalyticsMetric::where('user_id', $user->id)->forPeriod($period)->count();

    expect($userMetrics)->toBe(4);
});

it('is idempotent — running twice does not create duplicates', function () {
    $user = User::factory()->create();
    Estimation::factory()->for($user)->create([
        'estimation_date' => now()->format('Y-m').'-05',
        'total_weight_mt' => 10.0,
        'total_price_aed' => 5000.0,
    ]);

    $this->artisan('analytics:aggregate')->assertSuccessful();
    $firstCount = AnalyticsMetric::count();

    $this->artisan('analytics:aggregate')->assertSuccessful();
    $secondCount = AnalyticsMetric::count();

    expect($secondCount)->toBe($firstCount);
});

it('processes a specific period with --period flag', function () {
    $user = User::factory()->create();
    Estimation::factory()->for($user)->create([
        'estimation_date' => '2025-06-15',
        'total_weight_mt' => 200.0,
        'total_price_aed' => 100000.0,
    ]);

    $this->artisan('analytics:aggregate', ['--period' => '2025-06'])
        ->assertSuccessful();

    expect(AnalyticsMetric::systemWide()->forPeriod('2025-06')->count())->toBe(4);
    expect((float) AnalyticsMetric::systemWide()->forMetric('total_weight')->forPeriod('2025-06')->first()->metric_value)
        ->toBe(200.0);
});

it('rejects invalid period format', function () {
    $this->artisan('analytics:aggregate', ['--period' => 'invalid'])
        ->assertFailed();
});

it('stores status breakdown metadata for monthly_estimations', function () {
    $user = User::factory()->create();
    Estimation::factory()->for($user)->create([
        'estimation_date' => now()->format('Y-m').'-10',
        'status' => 'calculated',
    ]);
    Estimation::factory()->for($user)->create([
        'estimation_date' => now()->format('Y-m').'-12',
        'status' => 'draft',
    ]);

    $this->artisan('analytics:aggregate')->assertSuccessful();

    $metric = AnalyticsMetric::systemWide()
        ->forMetric('monthly_estimations')
        ->forPeriod(now()->format('Y-m'))
        ->first();

    expect($metric->metadata)->toBeArray();
    expect($metric->metadata)->toHaveKey('status_breakdown');
    expect($metric->metadata['status_breakdown'])->toHaveKey('calculated');
    expect($metric->metadata['status_breakdown'])->toHaveKey('draft');
});

it('calculates correct avg_price_per_mt', function () {
    $user = User::factory()->create();
    Estimation::factory()->for($user)->create([
        'estimation_date' => now()->format('Y-m').'-01',
        'total_weight_mt' => 100.0,
        'total_price_aed' => 50000.0,
    ]);

    $this->artisan('analytics:aggregate')->assertSuccessful();

    $metric = AnalyticsMetric::systemWide()
        ->forMetric('avg_price_per_mt')
        ->forPeriod(now()->format('Y-m'))
        ->first();

    expect((float) $metric->metric_value)->toBe(500.0);
});
