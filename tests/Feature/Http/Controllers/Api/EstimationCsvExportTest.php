<?php

use App\Models\Estimation;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->estimation = Estimation::factory()
        ->withResults()
        ->create(['user_id' => $this->user->id]);
});

describe('CSV exports', function () {
    it('exports recap as CSV', function () {
        $response = $this->actingAs($this->user)
            ->get("/api/estimations/{$this->estimation->id}/export/recap/csv");

        $response->assertSuccessful()
            ->assertDownload('RECAP-'.$this->estimation->quote_number.'.csv');
    });

    it('exports detail as CSV', function () {
        $response = $this->actingAs($this->user)
            ->get("/api/estimations/{$this->estimation->id}/export/detail/csv");

        $response->assertSuccessful()
            ->assertDownload('DETAIL-'.$this->estimation->quote_number.'.csv');
    });

    it('exports fcpbs as CSV', function () {
        $response = $this->actingAs($this->user)
            ->get("/api/estimations/{$this->estimation->id}/export/fcpbs/csv");

        $response->assertSuccessful()
            ->assertDownload('FCPBS-'.$this->estimation->quote_number.'.csv');
    });

    it('exports sal as CSV', function () {
        $response = $this->actingAs($this->user)
            ->get("/api/estimations/{$this->estimation->id}/export/sal/csv");

        $response->assertSuccessful()
            ->assertDownload('SAL-'.$this->estimation->quote_number.'.csv');
    });

    it('exports boq as CSV', function () {
        $response = $this->actingAs($this->user)
            ->get("/api/estimations/{$this->estimation->id}/export/boq/csv");

        $response->assertSuccessful()
            ->assertDownload('BOQ-'.$this->estimation->quote_number.'.csv');
    });

    it('exports jaf as CSV', function () {
        $response = $this->actingAs($this->user)
            ->get("/api/estimations/{$this->estimation->id}/export/jaf/csv");

        $response->assertSuccessful()
            ->assertDownload('JAF-'.$this->estimation->quote_number.'.csv');
    });

    it('exports rawmat as CSV', function () {
        $response = $this->actingAs($this->user)
            ->get("/api/estimations/{$this->estimation->id}/export/rawmat/csv");

        $response->assertSuccessful()
            ->assertDownload('RAWMAT-'.$this->estimation->quote_number.'.csv');
    });

    it('returns 422 for uncalculated estimation', function () {
        $draft = Estimation::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->user)
            ->get("/api/estimations/{$draft->id}/export/detail/csv");

        $response->assertStatus(422);
    });

    it('returns 404 for invalid sheet type', function () {
        $response = $this->actingAs($this->user)
            ->get("/api/estimations/{$this->estimation->id}/export/invalid/csv");

        $response->assertNotFound();
    });

    it('returns 403 for unauthorized user', function () {
        $other = User::factory()->create();

        $response = $this->actingAs($other)
            ->get("/api/estimations/{$this->estimation->id}/export/detail/csv");

        $response->assertForbidden();
    });

    it('logs export activity', function () {
        $this->actingAs($this->user)
            ->get("/api/estimations/{$this->estimation->id}/export/recap/csv");

        $this->assertDatabaseHas('reports', [
            'user_id' => $this->user->id,
            'estimation_id' => $this->estimation->id,
            'report_type' => 'csv',
            'sheet_name' => 'recap',
        ]);
    });
});
