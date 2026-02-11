<?php
/**
 * QuickEst - REST API Entry Point
 *
 * External API access for integrations
 * Base URL: /api/
 */

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't output errors in JSON API

// Define base path
define('BASE_PATH', dirname(dirname(__DIR__)));

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'QuickEst\\';
    $baseDir = BASE_PATH . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Load database config
require_once BASE_PATH . '/config/database.php';

// Initialize database
Database::init(BASE_PATH . '/data');

// CORS Headers for API access
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Token');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

use QuickEst\Api\Router;
use QuickEst\Api\Endpoints;
use QuickEst\Services\AuthService;
use QuickEst\Database\Connection;

// Initialize auth
AuthService::init();

// Get request details
$method = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];

// Parse the path - remove /api/ prefix and query string
$basePath = '/api';
$path = parse_url($requestUri, PHP_URL_PATH);
if (strpos($path, $basePath) === 0) {
    $path = substr($path, strlen($basePath));
}
if (empty($path) || $path === '/') {
    $path = '/';
}

// API Token Authentication
$apiToken = null;
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['HTTP_X_API_TOKEN'] ?? '';

if (preg_match('/Bearer\s+(.+)$/i', $authHeader, $matches)) {
    $apiToken = $matches[1];
} elseif (isset($_SERVER['HTTP_X_API_TOKEN'])) {
    $apiToken = $_SERVER['HTTP_X_API_TOKEN'];
}

// Verify API token if provided
if ($apiToken) {
    try {
        $db = Connection::getInstance();
        $stmt = $db->prepare("
            SELECT t.*, u.id as user_id, u.username
            FROM api_tokens t
            JOIN users u ON t.user_id = u.id
            WHERE t.token = ?
            AND (t.expires_at IS NULL OR t.expires_at > datetime('now'))
        ");
        $stmt->execute([$apiToken]);
        $tokenData = $stmt->fetch();

        if ($tokenData) {
            // Update last used timestamp
            $stmt = $db->prepare("UPDATE api_tokens SET last_used_at = datetime('now') WHERE token = ?");
            $stmt->execute([$apiToken]);

            // Set user context
            $_SESSION['user_id'] = $tokenData['user_id'];
            $_SESSION['api_permissions'] = json_decode($tokenData['permissions'], true);
        }
    } catch (Exception $e) {
        // Token validation failed
    }
}

// Create router and register endpoints
$router = new Router();
Endpoints::register($router);

// Add API info endpoint
$router->get('/', function() {
    return [
        'name' => 'QuickEst API',
        'version' => '2.0',
        'description' => 'Pre-Engineered Metal Building Estimation API',
        'endpoints' => [
            'auth' => [
                'POST /auth/login' => 'Login with username and password',
                'POST /auth/register' => 'Register new account',
                'POST /auth/logout' => 'Logout current session',
                'GET /auth/me' => 'Get current user info',
                'PUT /auth/password' => 'Change password',
                'GET /auth/tokens' => 'List API tokens',
                'POST /auth/tokens' => 'Create API token',
                'DELETE /auth/tokens/{id}' => 'Revoke API token'
            ],
            'projects' => [
                'GET /projects' => 'List all projects',
                'POST /projects' => 'Create new project',
                'GET /projects/{id}' => 'Get project details',
                'PUT /projects/{id}' => 'Update project',
                'DELETE /projects/{id}' => 'Delete project'
            ],
            'buildings' => [
                'GET /projects/{id}/buildings' => 'List project buildings',
                'POST /projects/{id}/buildings' => 'Add building to project',
                'PUT /projects/{id}/buildings/{buildingId}' => 'Update building',
                'POST /projects/{id}/buildings/{buildingId}/calculate' => 'Run calculation',
                'POST /projects/{id}/buildings/{buildingId}/duplicate' => 'Duplicate building',
                'DELETE /projects/{id}/buildings/{buildingId}' => 'Remove building'
            ],
            'calculation' => [
                'POST /calculate' => 'Run standalone calculation',
                'POST /calculate/mezzanine' => 'Calculate mezzanine',
                'POST /calculate/crane' => 'Calculate crane system',
                'POST /calculate/accessory' => 'Calculate accessories',
                'POST /calculate/partition' => 'Calculate partition',
                'POST /calculate/canopy' => 'Calculate canopy',
                'POST /calculate/monitor' => 'Calculate roof monitor',
                'POST /calculate/liner' => 'Calculate liner panels'
            ],
            'analytics' => [
                'GET /analytics/dashboard' => 'Get dashboard statistics',
                'GET /analytics/data' => 'Get analytics data'
            ]
        ],
        'authentication' => [
            'session' => 'Cookie-based session (for web)',
            'token' => 'API Token via Authorization: Bearer <token> or X-API-Token header'
        ]
    ];
});

// Add standalone calculation endpoints
$router->post('/calculate', function() {
    $input = json_decode(file_get_contents('php://input'), true);

    $building = \QuickEst\Models\Building::fromArray($input);
    $errors = $building->validate();

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    $engine = new \QuickEst\Services\CalculationEngine();
    $bom = $engine->calculate($building);

    return [
        'success' => true,
        'dimensions' => $engine->getDimensions(),
        'loads' => $engine->getLoads(),
        'items' => $bom->toArray(),
        'summary' => [
            'totalWeight' => round($bom->getTotalWeight(), 2),
            'totalPrice' => round($bom->getTotalPrice(), 2),
            'itemCount' => $bom->getItemCount(),
        ]
    ];
});

$router->post('/calculate/mezzanine', function() {
    $input = json_decode(file_get_contents('php://input'), true);
    $calculator = new \QuickEst\Services\MezzanineCalculator();
    $bom = $calculator->calculate($input);

    return [
        'success' => true,
        'items' => $bom->toArray(),
        'summary' => [
            'totalWeight' => round($bom->getTotalWeight(), 2),
            'totalPrice' => round($bom->getTotalPrice(), 2),
            'itemCount' => $bom->getItemCount(),
        ]
    ];
});

$router->post('/calculate/crane', function() {
    $input = json_decode(file_get_contents('php://input'), true);
    $calculator = new \QuickEst\Services\CraneCalculator();
    $bom = $calculator->calculate($input);

    return [
        'success' => true,
        'items' => $bom->toArray(),
        'summary' => [
            'totalWeight' => round($bom->getTotalWeight(), 2),
            'totalPrice' => round($bom->getTotalPrice(), 2),
            'itemCount' => $bom->getItemCount(),
        ]
    ];
});

$router->post('/calculate/accessory', function() {
    $input = json_decode(file_get_contents('php://input'), true);
    $calculator = new \QuickEst\Services\AccessoryCalculator();
    $bom = $calculator->calculate($input);

    return [
        'success' => true,
        'items' => $bom->toArray(),
        'summary' => [
            'totalWeight' => round($bom->getTotalWeight(), 2),
            'totalPrice' => round($bom->getTotalPrice(), 2),
            'itemCount' => $bom->getItemCount(),
        ]
    ];
});

$router->post('/calculate/partition', function() {
    $input = json_decode(file_get_contents('php://input'), true);
    $calculator = new \QuickEst\Services\PartitionCalculator();
    $bom = $calculator->calculate($input);

    return [
        'success' => true,
        'items' => $bom->toArray(),
        'summary' => [
            'totalWeight' => round($bom->getTotalWeight(), 2),
            'totalPrice' => round($bom->getTotalPrice(), 2),
            'itemCount' => $bom->getItemCount(),
        ]
    ];
});

$router->post('/calculate/canopy', function() {
    $input = json_decode(file_get_contents('php://input'), true);
    $calculator = new \QuickEst\Services\CanopyCalculator();
    $bom = $calculator->calculate($input);

    return [
        'success' => true,
        'items' => $bom->toArray(),
        'summary' => [
            'totalWeight' => round($bom->getTotalWeight(), 2),
            'totalPrice' => round($bom->getTotalPrice(), 2),
            'itemCount' => $bom->getItemCount(),
        ]
    ];
});

$router->post('/calculate/monitor', function() {
    $input = json_decode(file_get_contents('php://input'), true);
    $calculator = new \QuickEst\Services\MonitorCalculator();
    $bom = $calculator->calculate($input);

    return [
        'success' => true,
        'items' => $bom->toArray(),
        'summary' => [
            'totalWeight' => round($bom->getTotalWeight(), 2),
            'totalPrice' => round($bom->getTotalPrice(), 2),
            'itemCount' => $bom->getItemCount(),
        ]
    ];
});

$router->post('/calculate/liner', function() {
    $input = json_decode(file_get_contents('php://input'), true);
    $calculator = new \QuickEst\Services\LinerCalculator();
    $bom = $calculator->calculate($input);

    return [
        'success' => true,
        'items' => $bom->toArray(),
        'summary' => [
            'totalWeight' => round($bom->getTotalWeight(), 2),
            'totalPrice' => round($bom->getTotalPrice(), 2),
            'itemCount' => $bom->getItemCount(),
        ]
    ];
});

// Handle the request
try {
    $result = $router->handleRequest($method, $path);
    echo json_encode($result, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
