<?php

namespace App\Services;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseMigrationService
{
    /**
     * Tables in dependency order (parents before children).
     * Foreign-key-free or parent tables come first, then child tables.
     *
     * @var array<int, string>
     */
    private const TABLE_ORDER = [
        'users',
        'personal_access_tokens',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'password_reset_tokens',
        'sessions',
        'design_configurations',
        'mbsdb_products',
        'ssdb_products',
        'raw_materials',
        'projects',
        'estimations',
        'estimation_items',
        'reports',
        'analytics_metrics',
        'activity_log',
    ];

    /**
     * Export all data from the current database connection.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function exportData(): array
    {
        $data = [];

        foreach (self::TABLE_ORDER as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $data[$table] = DB::table($table)
                ->get()
                ->map(fn ($row) => (array) $row)
                ->toArray();
        }

        return $data;
    }

    /**
     * Import data into the specified database connection.
     *
     * @param  array<string, array<int, array<string, mixed>>>  $data
     */
    public function importData(array $data, string $connectionName): void
    {
        $connection = DB::connection($connectionName);

        $this->disableForeignKeyChecks($connection);

        try {
            foreach (self::TABLE_ORDER as $table) {
                if (! isset($data[$table]) || empty($data[$table])) {
                    continue;
                }

                $connection->table($table)->truncate();

                foreach (array_chunk($data[$table], 500) as $chunk) {
                    $connection->table($table)->insert($chunk);
                }
            }

            $this->resetPostgresSequences($connection);
        } finally {
            $this->enableForeignKeyChecks($connection);
        }
    }

    /**
     * Verify that row counts match between exported data and the target database.
     *
     * @param  array<string, array<int, array<string, mixed>>>  $sourceData
     * @return array<string, array{source: int, target: int, match: bool}>
     */
    public function verifyMigration(array $sourceData, string $targetConnection): array
    {
        $connection = DB::connection($targetConnection);
        $results = [];

        foreach (self::TABLE_ORDER as $table) {
            $sourceCount = count($sourceData[$table] ?? []);

            try {
                $targetCount = $connection->table($table)->count();
            } catch (\Throwable) {
                $targetCount = 0;
            }

            $results[$table] = [
                'source' => $sourceCount,
                'target' => $targetCount,
                'match' => $sourceCount === $targetCount,
            ];
        }

        return $results;
    }

    /**
     * Get the list of tables managed by this service.
     *
     * @return array<int, string>
     */
    public function getTableOrder(): array
    {
        return self::TABLE_ORDER;
    }

    /**
     * Disable foreign key constraint checks for the given connection.
     */
    private function disableForeignKeyChecks(Connection $connection): void
    {
        match ($connection->getDriverName()) {
            'sqlite' => $connection->statement('PRAGMA foreign_keys = OFF'),
            'mysql', 'mariadb' => $connection->statement('SET FOREIGN_KEY_CHECKS = 0'),
            'pgsql' => $connection->statement('SET session_replication_role = replica'),
            default => null,
        };
    }

    /**
     * Re-enable foreign key constraint checks for the given connection.
     */
    private function enableForeignKeyChecks(Connection $connection): void
    {
        match ($connection->getDriverName()) {
            'sqlite' => $connection->statement('PRAGMA foreign_keys = ON'),
            'mysql', 'mariadb' => $connection->statement('SET FOREIGN_KEY_CHECKS = 1'),
            'pgsql' => $connection->statement('SET session_replication_role = DEFAULT'),
            default => null,
        };
    }

    /**
     * Reset PostgreSQL auto-increment sequences after data import
     * so that new inserts get correct IDs.
     */
    private function resetPostgresSequences(Connection $connection): void
    {
        if ($connection->getDriverName() !== 'pgsql') {
            return;
        }

        foreach (self::TABLE_ORDER as $table) {
            try {
                $maxId = $connection->table($table)->max('id');

                if ($maxId !== null) {
                    $sequence = "{$table}_id_seq";
                    $connection->statement("SELECT setval('{$sequence}', {$maxId})");
                }
            } catch (\Throwable) {
                // Table may not have an 'id' column (e.g. cache, sessions)
            }
        }
    }
}
