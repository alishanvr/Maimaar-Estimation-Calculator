<?php

use App\Models\Estimation;
use App\Models\User;

it('returns dashboard data for authenticated user', function () {
    $user = User::factory()->create();
    Estimation::factory()->calculated()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->getJson('/api/reports/dashboard');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'kpis' => [
                    'total_estimations',
                    'total_weight_mt',
                    'total_revenue_aed',
                    'avg_price_per_mt',
                    'finalized_count',
                    'calculated_count',
                    'draft_count',
                ],
                'monthly_trends',
                'customer_revenue',
                'weight_distribution' => [
                    'steel_weight_kg',
                    'panels_weight_kg',
                ],
                'status_breakdown',
                'price_per_mt_trend',
                'cost_category_breakdown',
                'filters_meta' => [
                    'customers',
                    'salespersons',
                ],
            ],
        ]);
});

it('requires authentication', function () {
    $response = $this->getJson('/api/reports/dashboard');

    $response->assertUnauthorized();
});

it('returns correct KPI totals', function () {
    $user = User::factory()->create();

    Estimation::factory()->create([
        'user_id' => $user->id,
        'status' => 'draft',
    ]);

    Estimation::factory()->calculated()->create([
        'user_id' => $user->id,
        'total_weight_mt' => 100.5000,
        'total_price_aed' => 500000.00,
    ]);

    Estimation::factory()->finalized()->create([
        'user_id' => $user->id,
        'total_weight_mt' => 50.2500,
        'total_price_aed' => 250000.00,
    ]);

    $response = $this->actingAs($user)
        ->getJson('/api/reports/dashboard');

    $response->assertSuccessful();

    $kpis = $response->json('data.kpis');
    expect($kpis['total_estimations'])->toBe(3)
        ->and($kpis['draft_count'])->toBe(1)
        ->and($kpis['calculated_count'])->toBe(1)
        ->and($kpis['finalized_count'])->toBe(1)
        ->and((float) $kpis['total_weight_mt'])->toBe(150.75)
        ->and((float) $kpis['total_revenue_aed'])->toBe(750000.0);
});

it('returns status breakdown counts', function () {
    $user = User::factory()->create();

    Estimation::factory()->count(2)->create(['user_id' => $user->id, 'status' => 'draft']);
    Estimation::factory()->count(3)->calculated()->create(['user_id' => $user->id]);
    Estimation::factory()->finalized()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->getJson('/api/reports/dashboard');

    $breakdown = $response->json('data.status_breakdown');
    $indexed = collect($breakdown)->keyBy('status');

    expect($indexed->get('draft')['count'])->toBe(2)
        ->and($indexed->get('calculated')['count'])->toBe(3)
        ->and($indexed->get('finalized')['count'])->toBe(1);
});

it('returns weight distribution from results data', function () {
    $user = User::factory()->create();

    Estimation::factory()->withResults()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->getJson('/api/reports/dashboard');

    $dist = $response->json('data.weight_distribution');
    expect((float) $dist['steel_weight_kg'])->toBe(27200.0)
        ->and((float) $dist['panels_weight_kg'])->toBe(22340.0);
});

it('returns monthly trends grouped by month', function () {
    $user = User::factory()->create();

    Estimation::factory()->calculated()->create([
        'user_id' => $user->id,
        'estimation_date' => '2026-01-15',
        'total_price_aed' => 100000,
    ]);

    Estimation::factory()->finalized()->create([
        'user_id' => $user->id,
        'estimation_date' => '2026-01-20',
        'total_price_aed' => 200000,
    ]);

    Estimation::factory()->calculated()->create([
        'user_id' => $user->id,
        'estimation_date' => '2026-02-10',
        'total_price_aed' => 150000,
    ]);

    $response = $this->actingAs($user)
        ->getJson('/api/reports/dashboard');

    $trends = $response->json('data.monthly_trends');
    expect($trends)->toHaveCount(2);

    $jan = collect($trends)->firstWhere('month', '2026-01');
    expect($jan['count'])->toBe(2)
        ->and($jan['label'])->toBe('Jan 2026');
});

it('returns top customers by revenue', function () {
    $user = User::factory()->create();

    Estimation::factory()->count(2)->calculated()->create([
        'user_id' => $user->id,
        'customer_name' => 'Alpha Corp',
        'total_price_aed' => 500000,
    ]);

    Estimation::factory()->finalized()->create([
        'user_id' => $user->id,
        'customer_name' => 'Beta LLC',
        'total_price_aed' => 300000,
    ]);

    $response = $this->actingAs($user)
        ->getJson('/api/reports/dashboard');

    $customers = $response->json('data.customer_revenue');
    expect($customers[0]['customer_name'])->toBe('Alpha Corp')
        ->and($customers[0]['estimation_count'])->toBe(2);
});

it('returns filters meta with unique customers and salespersons', function () {
    $user = User::factory()->create();

    Estimation::factory()->create([
        'user_id' => $user->id,
        'customer_name' => 'Acme Corp',
        'salesperson_code' => 'JDM',
    ]);

    Estimation::factory()->create([
        'user_id' => $user->id,
        'customer_name' => 'BuildRight',
        'salesperson_code' => 'AKS',
    ]);

    Estimation::factory()->create([
        'user_id' => $user->id,
        'customer_name' => 'Acme Corp',
        'salesperson_code' => 'JDM',
    ]);

    $response = $this->actingAs($user)
        ->getJson('/api/reports/dashboard');

    $meta = $response->json('data.filters_meta');
    expect($meta['customers'])->toHaveCount(2)
        ->and($meta['salespersons'])->toHaveCount(2);
});

it('filters by date range', function () {
    $user = User::factory()->create();

    Estimation::factory()->calculated()->create([
        'user_id' => $user->id,
        'estimation_date' => '2026-01-15',
        'total_price_aed' => 100000,
    ]);

    Estimation::factory()->calculated()->create([
        'user_id' => $user->id,
        'estimation_date' => '2026-03-15',
        'total_price_aed' => 200000,
    ]);

    $response = $this->actingAs($user)
        ->getJson('/api/reports/dashboard?date_from=2026-01-01&date_to=2026-01-31');

    $response->assertSuccessful();
    expect($response->json('data.kpis.total_estimations'))->toBe(1)
        ->and((float) $response->json('data.kpis.total_revenue_aed'))->toBe(100000.0);
});

it('filters by status array', function () {
    $user = User::factory()->create();

    Estimation::factory()->create(['user_id' => $user->id, 'status' => 'draft']);
    Estimation::factory()->calculated()->create(['user_id' => $user->id]);
    Estimation::factory()->finalized()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->getJson('/api/reports/dashboard?statuses[]=calculated&statuses[]=finalized');

    $response->assertSuccessful();
    expect($response->json('data.kpis.total_estimations'))->toBe(2);
});

it('filters by customer name with partial match', function () {
    $user = User::factory()->create();

    Estimation::factory()->calculated()->create([
        'user_id' => $user->id,
        'customer_name' => 'Alpha Corporation',
        'total_price_aed' => 100000,
    ]);

    Estimation::factory()->calculated()->create([
        'user_id' => $user->id,
        'customer_name' => 'Beta LLC',
        'total_price_aed' => 200000,
    ]);

    $response = $this->actingAs($user)
        ->getJson('/api/reports/dashboard?customer_name=Alpha');

    $response->assertSuccessful();
    expect($response->json('data.kpis.total_estimations'))->toBe(1);
});

it('filters by salesperson code', function () {
    $user = User::factory()->create();

    Estimation::factory()->calculated()->create([
        'user_id' => $user->id,
        'salesperson_code' => 'JDM',
    ]);

    Estimation::factory()->calculated()->create([
        'user_id' => $user->id,
        'salesperson_code' => 'AKS',
    ]);

    $response = $this->actingAs($user)
        ->getJson('/api/reports/dashboard?salesperson_code=JDM');

    $response->assertSuccessful();
    expect($response->json('data.kpis.total_estimations'))->toBe(1);
});

it('scopes data to user for non-admin', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    Estimation::factory()->count(3)->calculated()->create(['user_id' => $user1->id]);
    Estimation::factory()->count(5)->calculated()->create(['user_id' => $user2->id]);

    $response = $this->actingAs($user1)
        ->getJson('/api/reports/dashboard');

    expect($response->json('data.kpis.total_estimations'))->toBe(3);
});

it('admin sees all estimations', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create();

    Estimation::factory()->count(3)->calculated()->create(['user_id' => $admin->id]);
    Estimation::factory()->count(5)->calculated()->create(['user_id' => $user->id]);

    $response = $this->actingAs($admin)
        ->getJson('/api/reports/dashboard');

    expect($response->json('data.kpis.total_estimations'))->toBe(8);
});

it('validates date format', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->getJson('/api/reports/dashboard?date_from=invalid');

    $response->assertUnprocessable();
});

it('validates date_to is after date_from', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->getJson('/api/reports/dashboard?date_from=2026-03-01&date_to=2026-01-01');

    $response->assertUnprocessable();
});

it('validates status values', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->getJson('/api/reports/dashboard?statuses[]=invalid');

    $response->assertUnprocessable();
});

it('returns zero KPIs when no estimations exist', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->getJson('/api/reports/dashboard');

    $response->assertSuccessful();
    $kpis = $response->json('data.kpis');
    expect($kpis['total_estimations'])->toBe(0)
        ->and((float) $kpis['total_weight_mt'])->toBe(0.0)
        ->and((float) $kpis['total_revenue_aed'])->toBe(0.0)
        ->and((float) $kpis['avg_price_per_mt'])->toBe(0.0);
});

it('returns cost category breakdown from results data', function () {
    $user = User::factory()->create();

    Estimation::factory()->withResults()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->getJson('/api/reports/dashboard');

    $categories = $response->json('data.cost_category_breakdown');
    expect($categories)->not->toBeEmpty();

    $mainFrames = collect($categories)->firstWhere('key', 'A');
    expect($mainFrames)->not->toBeNull()
        ->and($mainFrames['name'])->toBe('Main Frames')
        ->and((float) $mainFrames['total_cost'])->toBeGreaterThan(0);
});
