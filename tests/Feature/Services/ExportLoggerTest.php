<?php

use App\Models\Estimation;
use App\Models\Report;
use App\Models\User;
use App\Services\ExportLogger;

it('logs a PDF export with correct attributes', function () {
    $user = User::factory()->admin()->create();
    $estimation = Estimation::factory()->for($user)->create();

    $report = ExportLogger::log($user, 'pdf', 'boq', 'BOQ-HQ-O-12345.pdf', $estimation, 50000);

    expect($report)->toBeInstanceOf(Report::class);
    expect($report->user_id)->toBe($user->id);
    expect($report->estimation_id)->toBe($estimation->id);
    expect($report->report_type)->toBe('pdf');
    expect($report->sheet_name)->toBe('boq');
    expect($report->filename)->toBe('BOQ-HQ-O-12345.pdf');
    expect($report->file_size)->toBe(50000);
});

it('logs an export without estimation', function () {
    $user = User::factory()->admin()->create();

    $report = ExportLogger::log($user, 'csv', 'dashboard', 'estimations-report.csv');

    expect($report->estimation_id)->toBeNull();
    expect($report->file_size)->toBeNull();
    expect($report->report_type)->toBe('csv');
    expect($report->sheet_name)->toBe('dashboard');
});

it('logs a bulk ZIP export', function () {
    $user = User::factory()->admin()->create();

    $report = ExportLogger::log($user, 'zip', 'bulk', 'estimations-export.zip');

    expect($report->report_type)->toBe('zip');
    expect($report->sheet_name)->toBe('bulk');
    expect($report->estimation_id)->toBeNull();
});

it('creates a persisted database record', function () {
    $user = User::factory()->admin()->create();

    ExportLogger::log($user, 'pdf', 'detail', 'Detail-test.pdf');

    expect(Report::count())->toBe(1);
    expect(Report::first()->user_id)->toBe($user->id);
});

it('report has correct relationships', function () {
    $user = User::factory()->admin()->create();
    $estimation = Estimation::factory()->for($user)->create();

    $report = ExportLogger::log($user, 'pdf', 'sal', 'SAL-test.pdf', $estimation);

    expect($report->user->id)->toBe($user->id);
    expect($report->estimation->id)->toBe($estimation->id);
});

it('user has reports relationship', function () {
    $user = User::factory()->admin()->create();

    ExportLogger::log($user, 'pdf', 'recap', 'Recap-1.pdf');
    ExportLogger::log($user, 'pdf', 'boq', 'BOQ-1.pdf');

    expect($user->reports)->toHaveCount(2);
});
