<?php

use App\Services\StorageAnalyzerService;
use Illuminate\Support\Collection;

it('returns directory breakdown with expected structure', function () {
    $service = new StorageAnalyzerService;
    $breakdown = $service->getDirectoryBreakdown();

    expect($breakdown)->toBeArray()->not->toBeEmpty();

    foreach ($breakdown as $dir) {
        expect($dir)->toHaveKeys(['path', 'description', 'size', 'size_human', 'file_count']);
        expect($dir['size'])->toBeInt();
        expect($dir['file_count'])->toBeInt();
        expect($dir['size_human'])->toBeString();
    }
});

it('returns total usage with expected structure', function () {
    $service = new StorageAnalyzerService;
    $total = $service->getTotalUsage();

    expect($total)->toHaveKeys(['size', 'size_human', 'file_count']);
    expect($total['size'])->toBeInt();
    expect($total['file_count'])->toBeInt();
    expect($total['size_human'])->toBeString();
});

it('returns log files as a collection', function () {
    $service = new StorageAnalyzerService;
    $logs = $service->getLogFiles();

    expect($logs)->toBeInstanceOf(Collection::class);

    if ($logs->isNotEmpty()) {
        $log = $logs->first();
        expect($log)->toHaveKeys(['name', 'path', 'size', 'size_human', 'modified_at', 'age_days']);
    }
});

it('returns largest files limited by parameter', function () {
    $service = new StorageAnalyzerService;
    $files = $service->getLargestFiles(5);

    expect($files)->toBeInstanceOf(Collection::class);
    expect($files->count())->toBeLessThanOrEqual(5);

    if ($files->isNotEmpty()) {
        $file = $files->first();
        expect($file)->toHaveKeys(['name', 'path', 'relative_path', 'size', 'size_human', 'modified_at']);
    }
});

it('returns session and cache driver strings', function () {
    $service = new StorageAnalyzerService;

    expect($service->getSessionDriver())->toBeString()->not->toBeEmpty();
    expect($service->getCacheDriver())->toBeString()->not->toBeEmpty();
});

it('clearOldLogs returns expected result structure', function () {
    $service = new StorageAnalyzerService;
    $result = $service->clearOldLogs(30);

    expect($result)->toHaveKeys(['deleted_count', 'freed_bytes', 'freed_human']);
    expect($result['deleted_count'])->toBeInt();
    expect($result['freed_bytes'])->toBeInt();
    expect($result['freed_human'])->toBeString();
});

it('clearAllLogs returns expected result structure', function () {
    $service = new StorageAnalyzerService;
    $result = $service->clearAllLogs();

    expect($result)->toHaveKeys(['deleted_count', 'freed_bytes', 'freed_human']);
    expect($result['deleted_count'])->toBeInt();
    expect($result['freed_bytes'])->toBeInt();
});

it('clearFrameworkCacheFiles returns expected result structure', function () {
    $service = new StorageAnalyzerService;
    $result = $service->clearFrameworkCacheFiles();

    expect($result)->toHaveKeys(['deleted_count', 'freed_bytes', 'freed_human']);
});

it('clearCompiledViews returns expected result structure', function () {
    $service = new StorageAnalyzerService;
    $result = $service->clearCompiledViews();

    expect($result)->toHaveKeys(['deleted_count', 'freed_bytes', 'freed_human']);
});

it('clearLivewireTmp returns expected result structure', function () {
    $service = new StorageAnalyzerService;
    $result = $service->clearLivewireTmp();

    expect($result)->toHaveKeys(['deleted_count', 'freed_bytes', 'freed_human']);
});

it('directory breakdown is sorted by size descending', function () {
    $service = new StorageAnalyzerService;
    $breakdown = $service->getDirectoryBreakdown();

    for ($i = 1; $i < count($breakdown); $i++) {
        expect($breakdown[$i - 1]['size'])->toBeGreaterThanOrEqual($breakdown[$i]['size']);
    }
});
