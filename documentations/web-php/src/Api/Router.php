<?php
/**
 * QuickEst - API Router
 *
 * Handles REST API routing and request processing
 */

namespace QuickEst\Api;

use QuickEst\Services\AuthService;

class Router
{
    private array $routes = [];
    private string $basePath = '/api';

    /**
     * Register GET route
     */
    public function get(string $path, callable $handler, bool $requireAuth = true): void
    {
        $this->addRoute('GET', $path, $handler, $requireAuth);
    }

    /**
     * Register POST route
     */
    public function post(string $path, callable $handler, bool $requireAuth = true): void
    {
        $this->addRoute('POST', $path, $handler, $requireAuth);
    }

    /**
     * Register PUT route
     */
    public function put(string $path, callable $handler, bool $requireAuth = true): void
    {
        $this->addRoute('PUT', $path, $handler, $requireAuth);
    }

    /**
     * Register DELETE route
     */
    public function delete(string $path, callable $handler, bool $requireAuth = true): void
    {
        $this->addRoute('DELETE', $path, $handler, $requireAuth);
    }

    /**
     * Add route to registry
     */
    private function addRoute(string $method, string $path, callable $handler, bool $requireAuth): void
    {
        // Convert path parameters to regex
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $this->basePath . $pattern . '$#';

        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'handler' => $handler,
            'requireAuth' => $requireAuth
        ];
    }

    /**
     * Handle incoming request
     */
    public function handleRequest(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Handle CORS preflight
        if ($method === 'OPTIONS') {
            $this->sendCorsHeaders();
            exit;
        }

        $this->sendCorsHeaders();
        header('Content-Type: application/json');

        // Find matching route
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $path, $matches)) {
                // Check authentication
                if ($route['requireAuth']) {
                    AuthService::init();
                    if (!AuthService::check()) {
                        $this->sendError(401, 'Authentication required');
                        return;
                    }
                }

                // Extract path parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Get request body for POST/PUT
                $body = [];
                if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
                    $input = file_get_contents('php://input');
                    $body = json_decode($input, true) ?? [];
                }

                try {
                    $response = call_user_func($route['handler'], $params, $body);
                    $this->sendResponse($response);
                } catch (\Exception $e) {
                    $this->sendError(500, $e->getMessage());
                }

                return;
            }
        }

        // No route found
        $this->sendError(404, 'Endpoint not found');
    }

    /**
     * Send success response
     */
    private function sendResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        echo json_encode($data, JSON_PRETTY_PRINT);
    }

    /**
     * Send error response
     */
    private function sendError(int $statusCode, string $message): void
    {
        http_response_code($statusCode);
        echo json_encode([
            'error' => true,
            'message' => $message,
            'status' => $statusCode
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Send CORS headers
     */
    private function sendCorsHeaders(): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Max-Age: 86400');
    }
}
