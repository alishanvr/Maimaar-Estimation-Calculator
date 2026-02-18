<?php

use App\Models\Estimation;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->estimation = Estimation::factory()
        ->withResults()
        ->create(['user_id' => $this->user->id]);
});

describe('ERP export', function () {
    it('exports ERP CSV for calculated estimation', function () {
        $response = $this->actingAs($this->user)
            ->get("/api/estimations/{$this->estimation->id}/export/erp?".http_build_query([
                'job_number' => 'TEST01',
                'building_number' => '01',
                'contract_date' => '2026-01-15',
                'fiscal_year' => 2026,
            ]));

        $response->assertSuccessful()
            ->assertDownload('ERP-'.$this->estimation->quote_number.'.csv');
    });

    it('returns 422 for uncalculated estimation', function () {
        $draft = Estimation::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->user)
            ->get("/api/estimations/{$draft->id}/export/erp?".http_build_query([
                'job_number' => 'TEST01',
                'building_number' => '01',
                'contract_date' => '2026-01-15',
                'fiscal_year' => 2026,
            ]));

        $response->assertStatus(422);
    });

    it('validates required ERP input fields', function () {
        $response = $this->actingAs($this->user)
            ->getJson("/api/estimations/{$this->estimation->id}/export/erp");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['job_number', 'building_number', 'contract_date', 'fiscal_year']);
    });

    it('returns 403 for unauthorized user', function () {
        $other = User::factory()->create();

        $response = $this->actingAs($other)
            ->get("/api/estimations/{$this->estimation->id}/export/erp?".http_build_query([
                'job_number' => 'TEST01',
                'building_number' => '01',
                'contract_date' => '2026-01-15',
                'fiscal_year' => 2026,
            ]));

        $response->assertForbidden();
    });

    it('logs export activity', function () {
        $this->actingAs($this->user)
            ->get("/api/estimations/{$this->estimation->id}/export/erp?".http_build_query([
                'job_number' => 'TEST01',
                'building_number' => '01',
                'contract_date' => '2026-01-15',
                'fiscal_year' => 2026,
            ]));

        $this->assertDatabaseHas('reports', [
            'user_id' => $this->user->id,
            'estimation_id' => $this->estimation->id,
            'report_type' => 'csv',
            'sheet_name' => 'erp',
        ]);
    });
});
