<?php
/**
 * QuickEst - User Model
 *
 * Handles user data and authentication
 */

namespace QuickEst\Models;

use QuickEst\Database\Connection;
use PDO;

class User
{
    public ?int $id = null;
    public string $username = '';
    public string $email = '';
    public string $fullName = '';
    public string $company = '';
    public string $role = 'user';
    public array $preferences = [];
    public ?string $createdAt = null;
    public ?string $lastLogin = null;
    public bool $isActive = true;

    /**
     * Find user by ID
     */
    public static function find(int $id): ?self
    {
        $db = Connection::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND is_active = 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * Find user by username
     */
    public static function findByUsername(string $username): ?self
    {
        $db = Connection::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * Find user by email
     */
    public static function findByEmail(string $email): ?self
    {
        $db = Connection::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * Get all users
     */
    public static function all(): array
    {
        $db = Connection::getInstance();
        $stmt = $db->query("SELECT * FROM users WHERE is_active = 1 ORDER BY username");
        $users = [];

        while ($row = $stmt->fetch()) {
            $users[] = self::fromRow($row);
        }

        return $users;
    }

    /**
     * Authenticate user
     */
    public static function authenticate(string $username, string $password): ?self
    {
        $db = Connection::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1");
        $stmt->execute([$username, $username]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        if (!password_verify($password, $row['password_hash'])) {
            return null;
        }

        // Update last login
        $updateStmt = $db->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
        $updateStmt->execute([$row['id']]);

        return self::fromRow($row);
    }

    /**
     * Create new user
     */
    public static function create(array $data): self
    {
        $db = Connection::getInstance();

        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);

        $stmt = $db->prepare("
            INSERT INTO users (username, email, password_hash, full_name, company, role, preferences)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['username'],
            $data['email'],
            $passwordHash,
            $data['full_name'] ?? '',
            $data['company'] ?? '',
            $data['role'] ?? 'user',
            json_encode($data['preferences'] ?? [])
        ]);

        return self::find($db->lastInsertId());
    }

    /**
     * Save user changes
     */
    public function save(): bool
    {
        $db = Connection::getInstance();

        if ($this->id) {
            $stmt = $db->prepare("
                UPDATE users SET
                    username = ?,
                    email = ?,
                    full_name = ?,
                    company = ?,
                    role = ?,
                    preferences = ?,
                    is_active = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");

            return $stmt->execute([
                $this->username,
                $this->email,
                $this->fullName,
                $this->company,
                $this->role,
                json_encode($this->preferences),
                $this->isActive ? 1 : 0,
                $this->id
            ]);
        }

        return false;
    }

    /**
     * Update password
     */
    public function updatePassword(string $newPassword): bool
    {
        $db = Connection::getInstance();
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt = $db->prepare("UPDATE users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        return $stmt->execute([$passwordHash, $this->id]);
    }

    /**
     * Verify password
     */
    public function verifyPassword(string $password): bool
    {
        $db = Connection::getInstance();
        $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$this->id]);
        $row = $stmt->fetch();

        return $row && password_verify($password, $row['password_hash']);
    }

    /**
     * Delete user (soft delete)
     */
    public function delete(): bool
    {
        $db = Connection::getInstance();
        $stmt = $db->prepare("UPDATE users SET is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        return $stmt->execute([$this->id]);
    }

    /**
     * Check if user has role
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role || $this->role === 'admin';
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Get user statistics
     */
    public function getStatistics(): array
    {
        $db = Connection::getInstance();

        // Project count
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM projects WHERE user_id = ?");
        $stmt->execute([$this->id]);
        $projectCount = $stmt->fetch()['count'];

        // Building count
        $stmt = $db->prepare("
            SELECT COUNT(*) as count FROM buildings b
            JOIN projects p ON b.project_id = p.id
            WHERE p.user_id = ?
        ");
        $stmt->execute([$this->id]);
        $buildingCount = $stmt->fetch()['count'];

        // Total weight and price
        $stmt = $db->prepare("
            SELECT
                COALESCE(SUM(b.total_weight), 0) as total_weight,
                COALESCE(SUM(b.total_price), 0) as total_price
            FROM buildings b
            JOIN projects p ON b.project_id = p.id
            WHERE p.user_id = ?
        ");
        $stmt->execute([$this->id]);
        $totals = $stmt->fetch();

        return [
            'project_count' => $projectCount,
            'building_count' => $buildingCount,
            'total_weight' => $totals['total_weight'],
            'total_price' => $totals['total_price']
        ];
    }

    /**
     * Create from database row
     */
    private static function fromRow(array $row): self
    {
        $user = new self();
        $user->id = (int)$row['id'];
        $user->username = $row['username'];
        $user->email = $row['email'];
        $user->fullName = $row['full_name'] ?? '';
        $user->company = $row['company'] ?? '';
        $user->role = $row['role'];
        $user->preferences = json_decode($row['preferences'] ?? '{}', true) ?: [];
        $user->createdAt = $row['created_at'];
        $user->lastLogin = $row['last_login'];
        $user->isActive = (bool)$row['is_active'];

        return $user;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'fullName' => $this->fullName,
            'company' => $this->company,
            'role' => $this->role,
            'preferences' => $this->preferences,
            'createdAt' => $this->createdAt,
            'lastLogin' => $this->lastLogin,
            'isActive' => $this->isActive
        ];
    }
}
