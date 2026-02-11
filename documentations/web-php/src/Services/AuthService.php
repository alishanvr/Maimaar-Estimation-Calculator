<?php
/**
 * QuickEst - Authentication Service
 *
 * Handles user authentication, sessions, and authorization
 */

namespace QuickEst\Services;

use QuickEst\Models\User;
use QuickEst\Database\Connection;

class AuthService
{
    private static ?User $currentUser = null;
    private static string $sessionName = 'quickest_session';

    /**
     * Initialize authentication (call at start of each request)
     */
    public static function init(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(self::$sessionName);
            session_start();
        }

        // Check for existing session
        if (isset($_SESSION['user_id'])) {
            self::$currentUser = User::find($_SESSION['user_id']);

            // Clear invalid session
            if (!self::$currentUser) {
                self::logout();
            }
        }

        // Check for API token authentication
        if (!self::$currentUser && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
            self::authenticateByToken($token);
        }
    }

    /**
     * Attempt to login user
     */
    public static function login(string $username, string $password, bool $remember = false): array
    {
        $user = User::authenticate($username, $password);

        if (!$user) {
            return [
                'success' => false,
                'error' => 'Invalid username or password'
            ];
        }

        if (!$user->isActive) {
            return [
                'success' => false,
                'error' => 'Account is disabled'
            ];
        }

        // Set session
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_role'] = $user->role;
        $_SESSION['login_time'] = time();

        self::$currentUser = $user;

        // Store session in database for tracking
        self::storeSession($user->id);

        return [
            'success' => true,
            'user' => $user->toArray()
        ];
    }

    /**
     * Logout current user
     */
    public static function logout(): void
    {
        // Remove session from database
        if (isset($_SESSION['user_id'])) {
            self::removeSession(session_id());
        }

        // Clear session
        $_SESSION = [];
        session_destroy();

        self::$currentUser = null;
    }

    /**
     * Get current authenticated user
     */
    public static function user(): ?User
    {
        return self::$currentUser;
    }

    /**
     * Check if user is authenticated
     */
    public static function check(): bool
    {
        return self::$currentUser !== null;
    }

    /**
     * Check if current user is admin
     */
    public static function isAdmin(): bool
    {
        return self::$currentUser && self::$currentUser->isAdmin();
    }

    /**
     * Check if user has specific role
     */
    public static function hasRole(string $role): bool
    {
        return self::$currentUser && self::$currentUser->hasRole($role);
    }

    /**
     * Require authentication (redirect or return error if not authenticated)
     */
    public static function requireAuth(): void
    {
        if (!self::check()) {
            if (self::isApiRequest()) {
                http_response_code(401);
                echo json_encode(['error' => 'Authentication required']);
                exit;
            }

            header('Location: ?page=login');
            exit;
        }
    }

    /**
     * Require admin role
     */
    public static function requireAdmin(): void
    {
        self::requireAuth();

        if (!self::isAdmin()) {
            if (self::isApiRequest()) {
                http_response_code(403);
                echo json_encode(['error' => 'Admin access required']);
                exit;
            }

            header('Location: ?page=dashboard');
            exit;
        }
    }

    /**
     * Register new user
     */
    public static function register(array $data): array
    {
        // Validate required fields
        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            return [
                'success' => false,
                'error' => 'Username, email, and password are required'
            ];
        }

        // Check username format
        if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $data['username'])) {
            return [
                'success' => false,
                'error' => 'Username must be 3-30 characters (letters, numbers, underscore)'
            ];
        }

        // Check email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'error' => 'Invalid email address'
            ];
        }

        // Check password strength
        if (strlen($data['password']) < 6) {
            return [
                'success' => false,
                'error' => 'Password must be at least 6 characters'
            ];
        }

        // Check if username exists
        if (User::findByUsername($data['username'])) {
            return [
                'success' => false,
                'error' => 'Username already exists'
            ];
        }

        // Check if email exists
        if (User::findByEmail($data['email'])) {
            return [
                'success' => false,
                'error' => 'Email already registered'
            ];
        }

        try {
            $user = User::create($data);

            return [
                'success' => true,
                'user' => $user->toArray()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Registration failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Change password
     */
    public static function changePassword(string $currentPassword, string $newPassword): array
    {
        if (!self::check()) {
            return [
                'success' => false,
                'error' => 'Not authenticated'
            ];
        }

        if (!self::$currentUser->verifyPassword($currentPassword)) {
            return [
                'success' => false,
                'error' => 'Current password is incorrect'
            ];
        }

        if (strlen($newPassword) < 6) {
            return [
                'success' => false,
                'error' => 'New password must be at least 6 characters'
            ];
        }

        self::$currentUser->updatePassword($newPassword);

        return [
            'success' => true,
            'message' => 'Password changed successfully'
        ];
    }

    /**
     * Generate API token
     */
    public static function generateApiToken(string $name, array $permissions = ['read'], ?string $expiresAt = null): array
    {
        if (!self::check()) {
            return [
                'success' => false,
                'error' => 'Not authenticated'
            ];
        }

        $token = bin2hex(random_bytes(32));

        $db = Connection::getInstance();
        $stmt = $db->prepare("
            INSERT INTO api_tokens (user_id, token, name, permissions, expires_at)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            self::$currentUser->id,
            $token,
            $name,
            json_encode($permissions),
            $expiresAt
        ]);

        return [
            'success' => true,
            'token' => $token,
            'name' => $name,
            'permissions' => $permissions
        ];
    }

    /**
     * Revoke API token
     */
    public static function revokeApiToken(int $tokenId): bool
    {
        if (!self::check()) {
            return false;
        }

        $db = Connection::getInstance();
        $stmt = $db->prepare("UPDATE api_tokens SET is_active = 0 WHERE id = ? AND user_id = ?");
        return $stmt->execute([$tokenId, self::$currentUser->id]);
    }

    /**
     * Get user's API tokens
     */
    public static function getApiTokens(): array
    {
        if (!self::check()) {
            return [];
        }

        $db = Connection::getInstance();
        $stmt = $db->prepare("
            SELECT id, name, permissions, expires_at, last_used_at, created_at
            FROM api_tokens
            WHERE user_id = ? AND is_active = 1
            ORDER BY created_at DESC
        ");
        $stmt->execute([self::$currentUser->id]);

        return $stmt->fetchAll();
    }

    /**
     * Authenticate by API token
     */
    private static function authenticateByToken(string $token): void
    {
        $db = Connection::getInstance();
        $stmt = $db->prepare("
            SELECT t.*, u.id as user_id
            FROM api_tokens t
            JOIN users u ON t.user_id = u.id
            WHERE t.token = ?
              AND t.is_active = 1
              AND u.is_active = 1
              AND (t.expires_at IS NULL OR t.expires_at > CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$token]);
        $row = $stmt->fetch();

        if ($row) {
            self::$currentUser = User::find($row['user_id']);

            // Update last used
            $updateStmt = $db->prepare("UPDATE api_tokens SET last_used_at = CURRENT_TIMESTAMP WHERE id = ?");
            $updateStmt->execute([$row['id']]);
        }
    }

    /**
     * Store session in database
     */
    private static function storeSession(int $userId): void
    {
        $db = Connection::getInstance();

        $stmt = $db->prepare("
            INSERT OR REPLACE INTO sessions (id, user_id, ip_address, user_agent, expires_at)
            VALUES (?, ?, ?, ?, datetime('now', '+7 days'))
        ");

        $stmt->execute([
            session_id(),
            $userId,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    }

    /**
     * Remove session from database
     */
    private static function removeSession(string $sessionId): void
    {
        $db = Connection::getInstance();
        $stmt = $db->prepare("DELETE FROM sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
    }

    /**
     * Clean expired sessions
     */
    public static function cleanExpiredSessions(): int
    {
        $db = Connection::getInstance();
        $stmt = $db->prepare("DELETE FROM sessions WHERE expires_at < CURRENT_TIMESTAMP");
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * Check if request is API request
     */
    private static function isApiRequest(): bool
    {
        return isset($_SERVER['HTTP_ACCEPT'])
            && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
    }

    /**
     * Get CSRF token
     */
    public static function getCsrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verify CSRF token
     */
    public static function verifyCsrfToken(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
