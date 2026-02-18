<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use ZipArchive;

class PackageApplication extends Command
{
    protected $signature = 'app:package
        {--skip-build : Skip running npm run build before packaging}
        {--output= : Output directory for the zip file (default: storage/app)}';

    protected $description = 'Create a deployable zip archive of the application, excluding dev files, logs, and documentation.';

    /**
     * Directories to exclude entirely (matched by name at any depth unless in EXCLUDED_SUBPATHS).
     *
     * @var array<int, string>
     */
    private const EXCLUDED_DIRS = [
        '.git',
        '.github',
        'node_modules',
        'vendor',
        '.idea',
        '.cursor',
        '.vscode',
        '.junie',
        '.claude',
        '.zed',
        '.fleet',
        '.nova',
        'documentations',
        'tests',
        '.phpunit.cache',
        'frontend',
    ];

    /**
     * Specific file names to exclude.
     *
     * @var array<int, string>
     */
    private const EXCLUDED_FILES = [
        '.env',
        '.env.production',
        '.env.backup',
        '.DS_Store',
        'Thumbs.db',
        '.phpunit.result.cache',
        'phpunit.xml',
        '.editorconfig',
        '.mcp.json',
        '.phpactor.json',
        '.gitattributes',
        '.gitignore',
        'Homestead.json',
        'Homestead.yaml',
        'auth.json',
        'installed',
    ];

    /**
     * File extensions to exclude.
     *
     * @var array<int, string>
     */
    private const EXCLUDED_EXTENSIONS = [
        'md',
        'log',
        'sqlite',
        'zip',
    ];

    /**
     * Sub-paths within included directories that should be excluded.
     *
     * @var array<int, string>
     */
    private const EXCLUDED_SUBPATHS = [
        'storage/logs',
        'storage/pail',
        'storage/app/private/livewire-tmp',
        'storage/app/public/app-settings',
        'storage/app/public/pdf-settings',
        'storage/framework/testing',
        'frontend/node_modules',
        'frontend/.next',
    ];

    public function handle(): int
    {
        $outputDir = $this->option('output') ?? storage_path('app');
        $timestamp = now()->format('Y-m-d_His');
        $zipName = "maimaar-{$timestamp}.zip";
        $zipPath = rtrim($outputDir, '/').'/'.$zipName;

        if (! is_dir($outputDir)) {
            $this->error("Output directory does not exist: {$outputDir}");

            return self::FAILURE;
        }

        // Step 1: Optionally build frontend assets
        if (! $this->option('skip-build')) {
            $this->info('Building frontend assets...');

            $result = Process::path(base_path())
                ->timeout(300)
                ->run('npm run build');

            if (! $result->successful()) {
                $this->error('Frontend build failed: '.$result->errorOutput());

                return self::FAILURE;
            }

            $this->info('Frontend build completed.');
        }

        // Step 2: Create zip archive
        $this->info('Creating zip archive...');

        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error("Cannot create zip file at: {$zipPath}");

            return self::FAILURE;
        }

        $basePath = base_path();
        $fileCount = $this->addDirectoryToZip($zip, $basePath, '');

        // Ensure essential storage subdirectories exist in the zip
        $storageDirs = [
            'storage/app/public',
            'storage/framework/cache',
            'storage/framework/sessions',
            'storage/framework/testing',
            'storage/framework/views',
            'storage/logs',
        ];

        foreach ($storageDirs as $dir) {
            $zip->addEmptyDir($dir);
        }

        $zip->close();

        $sizeMb = round(filesize($zipPath) / 1024 / 1024, 2);

        $this->newLine();
        $this->info('Package created successfully!');
        $this->table(
            ['Property', 'Value'],
            [
                ['File', $zipPath],
                ['Size', "{$sizeMb} MB"],
                ['Files', $fileCount],
            ]
        );

        return self::SUCCESS;
    }

    private function addDirectoryToZip(ZipArchive $zip, string $basePath, string $relativePath): int
    {
        $absolutePath = $relativePath === '' ? $basePath : "{$basePath}/{$relativePath}";
        $entries = scandir($absolutePath);
        $count = 0;

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $entryRelative = $relativePath === '' ? $entry : "{$relativePath}/{$entry}";
            $entryAbsolute = "{$basePath}/{$entryRelative}";

            // Skip symlinks — storage:link will recreate them on deploy
            if (is_link($entryAbsolute)) {
                continue;
            }

            if (is_dir($entryAbsolute)) {
                if ($this->isExcludedDirectory($entryRelative, $entry)) {
                    continue;
                }

                $zip->addEmptyDir($entryRelative);
                $count += $this->addDirectoryToZip($zip, $basePath, $entryRelative);
            } else {
                if ($this->isExcludedFile($entryRelative, $entry)) {
                    continue;
                }

                $zip->addFile($entryAbsolute, $entryRelative);
                $count++;
            }
        }

        return $count;
    }

    private function isExcludedDirectory(string $relativePath, string $dirName): bool
    {
        if (in_array($dirName, self::EXCLUDED_DIRS, true)) {
            return true;
        }

        foreach (self::EXCLUDED_SUBPATHS as $subpath) {
            if ($relativePath === $subpath || str_starts_with($relativePath, $subpath.'/')) {
                return true;
            }
        }

        return false;
    }

    private function isExcludedFile(string $relativePath, string $fileName): bool
    {
        if (in_array($fileName, self::EXCLUDED_FILES, true)) {
            return true;
        }

        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        return in_array($extension, self::EXCLUDED_EXTENSIONS, true);
    }
}
