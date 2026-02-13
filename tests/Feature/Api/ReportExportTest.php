<?php

use App\Models\Estimation;
use App\Models\User;

it('exports csv with correct headers', function () {
    $user = User::factory()->create();

    Estimation::factory()->calculated()->create([
        'user_id' => $user->id,
        'quote_number' => 'HQ-CSV-001',
        'customer_name' => 'Test Customer',
    ]);

    $response = $this->actingAs($user)
        ->get('/api/reports/export/csv');

    $response->assertSuccessful()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $content = $response->streamedContent();
    expect($content)->toContain('Quote #')
        ->and($content)->toContain('HQ-CSV-001')
        ->and($content)->toContain('Test Customer');
});

it('exports csv with filtered data', function () {
    $user = User::factory()->create();

    Estimation::factory()->calculated()->create([
        'user_id' => $user->id,
        'customer_name' => 'Include Me',
    ]);

    Estimation::factory()->calculated()->create([
        'user_id' => $user->id,
        'customer_name' => 'Exclude Me',
    ]);

    $response = $this->actingAs($user)
        ->get('/api/reports/export/csv?customer_name=Include');

    $content = $response->streamedContent();
    expect($content)->toContain('Include Me')
        ->and($content)->not->toContain('Exclude Me');
});

it('exports pdf successfully', function () {
    $user = User::factory()->create();

    Estimation::factory()->calculated()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->get('/api/reports/export/pdf');

    $response->assertSuccessful()
        ->assertHeader('content-type', 'application/pdf');
});

it('requires authentication for csv export', function () {
    $response = $this->getJson('/api/reports/export/csv');

    $response->assertUnauthorized();
});

it('requires authentication for pdf export', function () {
    $response = $this->getJson('/api/reports/export/pdf');

    $response->assertUnauthorized();
});

it('applies filters to pdf export', function () {
    $user = User::factory()->create();

    Estimation::factory()->calculated()->create([
        'user_id' => $user->id,
        'customer_name' => 'PDF Customer',
    ]);

    $response = $this->actingAs($user)
        ->get('/api/reports/export/pdf?customer_name=PDF');

    $response->assertSuccessful()
        ->assertHeader('content-type', 'application/pdf');
});
