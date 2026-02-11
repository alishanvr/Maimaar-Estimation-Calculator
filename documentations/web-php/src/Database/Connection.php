<?php
/**
 * QuickEst - Database Connection (SQLite)
 *
 * Handles SQLite database connection and initialization
 */

namespace QuickEst\Database;

use PDO;
use PDOException;

class Connection
{
    private static ?PDO $instance = null;
    private static string $dbPath;

    /**
     * Get database connection instance (singleton)
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::$dbPath = dirname(__DIR__, 2) . '/data/quickest.db';

            try {
                self::$instance = new PDO(
                    'sqlite:' . self::$dbPath,
                    null,
                    null,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );

                // Enable foreign keys for SQLite
                self::$instance->exec('PRAGMA foreign_keys = ON');

                // Initialize schema if needed
                self::initializeSchema();

            } catch (PDOException $e) {
                throw new \Exception('Database connection failed: ' . $e->getMessage());
            }
        }

        return self::$instance;
    }

    /**
     * Initialize database schema
     */
    private static function initializeSchema(): void
    {
        $schemaFile = dirname(__DIR__, 2) . '/config/schema.sql';

        if (!file_exists($schemaFile)) {
            return;
        }

        // Check if tables exist
        $stmt = self::$instance->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
        if ($stmt->fetch()) {
            return; // Schema already exists
        }

        // Execute schema SQL
        $schema = file_get_contents($schemaFile);
        self::$instance->exec($schema);

        // Create default admin user with proper password hash
        $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = self::$instance->prepare("
            INSERT OR REPLACE INTO users (id, username, email, password_hash, full_name, role)
            VALUES (1, 'admin', 'admin@quickest.local', ?, 'System Administrator', 'admin')
        ");
        $stmt->execute([$passwordHash]);
    }

    /**
     * Get database path
     */
    public static function getDbPath(): string
    {
        return self::$dbPath;
    }

    /**
     * Begin transaction
     */
    public static function beginTransaction(): bool
    {
        return self::getInstance()->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public static function commit(): bool
    {
        return self::getInstance()->commit();
    }

    /**
     * Rollback transaction
     */
    public static function rollback(): bool
    {
        return self::getInstance()->rollBack();
    }
}
