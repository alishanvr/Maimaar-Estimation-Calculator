<?php

namespace App\Services;

use Illuminate\Support\Collection;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class StorageAnalyzerService
{
    /**
     * Directories to analyze within the storage path.
     *
     * @var array<string, string>
     */
    private const DIRECTORIES = [
        'storage/app/public' => 'Uploaded files & public assets',
        'storage/app/private' => 'Private application files',
        'storage/framework/cache' => 'File-based cache data',
        'storage/framework/sessions' => 'Session files (file driver)',
        'storage/framework/views' => 'Compiled Blade views',
        'storage/framework/testing' => 'Test artifacts',
        'storage/logs' => 'Application log files',
    ];

    /**
     * Get a breakdown of disk usage per storage subdirectory.
     *
     * @return array<int, array{path: string, description: string, size: int, size_human: string, file_count: int}>
     */
    public function getDirectoryBreakdown(): array
    {
        $breakdown = [];

        foreach (self::DIRECTORIES as $relativePath => $description) {
            $absolutePath = base_path($relativePath);

            if (! is_dir($absolutePath)) {
                $breakdown[] = [
                    'path' => $relativePath,
                    'description' => $description,
                    'size' => 0,
                    'size_human' => '0 B',
                    'file_count' => 0,
                ];

                continue;
            }

            [$size, $fileCount] = $this->calculateDirectorySize($absolutePath);

            $breakdown[] = [
                'path' => $relativePath,
                'description' => $description,
                'size' => $size,
                'size_human' => $this->formatBytes($size),
                'file_count' => $fileCount,
            ];
        }

        // Sort by size descending
        usort($breakdown, fn (array $a, array $b): int => $b['size'] <=> $a['size']);

        return $breakdown;
    }

    /**
     * Get total storage usage across all analyzed directories.
     *
     * @return array{size: int, size_human: string, file_count: int}
     */
    public function getTotalUsage(): array
    {
        $breakdown = $this->getDirectoryBreakdown();
        $totalSize = array_sum(array_column($breakdown, 'size'));
        $totalFiles = array_sum(array_column($breakdown, 'file_count'));

        return [
            'size' => $totalSize,
            'size_human' => $this->formatBytes($totalSize),
            'file_count' => $totalFiles,
        ];
    }

    /**
     * List log files with their metadata.
     *
     * @return Collection<int, array{name: string, path: string, size: int, size_human: string, modified_at: string, age_days: int}>
     */
    public function getLogFiles(): Collection
    {
        $logsPath = storage_path('logs');

        if (! is_dir($logsPath)) {
            return collect();
        }

        $files = [];

        foreach (new \DirectoryIterator($logsPath) as $file) {
            if ($file->isDot() || $file->isDir() || $file->getFilename() === '.gitignore') {
                continue;
            }

            $modifiedAt = $file->getMTime();
            $ageDays = (int) ((time() - $modifiedAt) / 86400);

            $files[] = [
                'name' => $file->getFilename(),
                'path' => $file->getPathname(),
                'size' => $file->getSize(),
                'size_human' => $this->formatBytes($file->getSize()),
                'modified_at' => date('Y-m-d H:i:s', $modifiedAt),
                'age_days' => $ageDays,
            ];
        }

        // Sort by modification date descending (newest first)
        usort($files, fn (array $a, array $b): int => strcmp($b['modified_at'], $a['modified_at']));

        return collect($files);
    }

    /**
     * Delete log files older than the given number of days.
     *
     * @return array{deleted_count: int, freed_bytes: int, freed_human: string}
     */
    public function clearOldLogs(int $keepDays = 1): array
    {
        $logsPath = storage_path('logs');
        $cutoff = time() - ($keepDays * 86400);
        $deletedCount = 0;
        $freedBytes = 0;

        if (! is_dir($logsPath)) {
            return ['deleted_count' => 0, 'freed_bytes' => 0, 'freed_human' => '0 B'];
        }

        foreach (new \DirectoryIterator($logsPath) as $file) {
            if ($file->isDot() || $file->isDir() || $file->getFilename() === '.gitignore') {
                continue;
            }

            if ($file->getMTime() < $cutoff) {
                $freedBytes += $file->getSize();

                if (@unlink($file->getPathname())) {
                    $deletedCount++;
                }
            }
        }

        return [
            'deleted_count' => $deletedCount,
            'freed_bytes' => $freedBytes,
            'freed_human' => $this->formatBytes($freedBytes),
        ];
    }

    /**
     * Delete all log files.
     *
     * @return array{deleted_count: int, freed_bytes: int, freed_human: string}
     */
    public function clearAllLogs(): array
    {
        $logsPath = storage_path('logs');
        $deletedCount = 0;
        $freedBytes = 0;

        if (! is_dir($logsPath)) {
            return ['deleted_count' => 0, 'freed_bytes' => 0, 'freed_human' => '0 B'];
        }

        foreach (new \DirectoryIterator($logsPath) as $file) {
            if ($file->isDot() || $file->isDir() || $file->getFilename() === '.gitignore') {
                continue;
            }

            $freedBytes += $file->getSize();

            if (@unlink($file->getPathname())) {
                $deletedCount++;
            }
        }

        return [
            'deleted_count' => $deletedCount,
            'freed_bytes' => $freedBytes,
            'freed_human' => $this->formatBytes($freedBytes),
        ];
    }

    /**
     * Clear framework cache files (storage/framework/cache/data).
     *
     * @return array{deleted_count: int, freed_bytes: int, freed_human: string}
     */
    public function clearFrameworkCacheFiles(): array
    {
        $cachePath = storage_path('framework/cache/data');

        return $this->deleteDirectoryContents($cachePath);
    }

    /**
     * Clear session files (storage/framework/sessions).
     *
     * @return array{deleted_count: int, freed_bytes: int, freed_human: string}
     */
    public function clearSessionFiles(): array
    {
        $sessionsPath = storage_path('framework/sessions');

        return $this->deleteDirectoryContents($sessionsPath);
    }

    /**
     * Clear compiled view files (storage/framework/views).
     *
     * @return array{deleted_count: int, freed_bytes: int, freed_human: string}
     */
    public function clearCompiledViews(): array
    {
        $viewsPath = storage_path('framework/views');

        return $this->deleteDirectoryContents($viewsPath);
    }

    /**
     * Clear Livewire temporary upload files.
     *
     * @return array{deleted_count: int, freed_bytes: int, freed_human: string}
     */
    public function clearLivewireTmp(): array
    {
        $tmpPath = storage_path('app/private/livewire-tmp');

        return $this->deleteDirectoryContents($tmpPath);
    }

    /**
     * Get the largest files across all storage directories.
     *
     * @return Collection<int, array{name: string, path: string, relative_path: string, size: int, size_human: string, modified_at: string}>
     */
    public function getLargestFiles(int $limit = 20): Collection
    {
        $storagePath = storage_path();
        $files = [];

        if (! is_dir($storagePath)) {
            return collect();
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($storagePath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getFilename() === '.gitignore') {
                continue;
            }

            $files[] = [
                'name' => $file->getFilename(),
                'path' => $file->getPathname(),
                'relative_path' => str_replace(base_path().'/', '', $file->getPathname()),
                'size' => $file->getSize(),
                'size_human' => $this->formatBytes($file->getSize()),
                'modified_at' => date('Y-m-d H:i:s', $file->getMTime()),
            ];
        }

        usort($files, fn (array $a, array $b): int => $b['size'] <=> $a['size']);

        return collect(array_slice($files, 0, $limit));
    }

    /**
     * Get the session driver currently in use.
     */
    public function getSessionDriver(): string
    {
        return config('session.driver', 'file');
    }

    /**
     * Get the cache driver currently in use.
     */
    public function getCacheDriver(): string
    {
        return config('cache.default', 'file');
    }

    /**
     * Calculate the total size and file count of a directory recursively.
     *
     * @return array{0: int, 1: int}
     */
    private function calculateDirectorySize(string $path): array
    {
        $size = 0;
        $count = 0;

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                    $count++;
                }
            }
        } catch (\Throwable) {
            // Directory may not be readable
        }

        return [$size, $count];
    }

    /**
     * Delete all files and subdirectories within a directory, preserving .gitignore files.
     *
     * @return array{deleted_count: int, freed_bytes: int, freed_human: string}
     */
    private function deleteDirectoryContents(string $path): array
    {
        $deletedCount = 0;
        $freedBytes = 0;

        if (! is_dir($path)) {
            return ['deleted_count' => 0, 'freed_bytes' => 0, 'freed_human' => '0 B'];
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        /** @var SplFileInfo $item */
        foreach ($iterator as $item) {
            if ($item->getFilename() === '.gitignore') {
                continue;
            }

            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                $freedBytes += $item->getSize();

                if (@unlink($item->getPathname())) {
                    $deletedCount++;
                }
            }
        }

        return [
            'deleted_count' => $deletedCount,
            'freed_bytes' => $freedBytes,
            'freed_human' => $this->formatBytes($freedBytes),
        ];
    }

    /**
     * Format bytes into a human-readable string.
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $exponent = (int) floor(log($bytes, 1024));
        $exponent = min($exponent, count($units) - 1);

        return number_format($bytes / (1024 ** $exponent), 2).' '.$units[$exponent];
    }
}
