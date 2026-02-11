<?php
/**
 * QuickEst - API Endpoints
 *
 * Defines all REST API endpoints
 */

namespace QuickEst\Api;

use QuickEst\Services\AuthService;
use QuickEst\Services\CalculationEngine;
use QuickEst\Models\User;
use QuickEst\Models\Project;
use QuickEst\Models\ProjectBuilding;
use QuickEst\Models\Building;

class Endpoints
{
    /**
     * Register all API routes
     */
    public static function register(Router $router): void
    {
        // ========================================
        // Authentication Endpoints
        // ========================================

        // Login
        $router->post('/auth/login', function ($params, $body) {
            $result = AuthService::login(
                $body['username'] ?? '',
                $body['password'] ?? ''
            );
            return $result;
        }, false);

        // Register
        $router->post('/auth/register', function ($params, $body) {
            return AuthService::register($body);
        }, false);

        // Logout
        $router->post('/auth/logout', function ($params, $body) {
            AuthService::logout();
            return ['success' => true, 'message' => 'Logged out'];
        }, true);

        // Get current user
        $router->get('/auth/me', function ($params, $body) {
            $user = AuthService::user();
            return [
                'success' => true,
                'user' => $user->toArray(),
                'stats' => $user->getStatistics()
            ];
        }, true);

        // Change password
        $router->post('/auth/password', function ($params, $body) {
            return AuthService::changePassword(
                $body['current_password'] ?? '',
                $body['new_password'] ?? ''
            );
        }, true);

        // ========================================
        // API Token Endpoints
        // ========================================

        // List tokens
        $router->get('/auth/tokens', function ($params, $body) {
            return [
                'success' => true,
                'tokens' => AuthService::getApiTokens()
            ];
        }, true);

        // Create token
        $router->post('/auth/tokens', function ($params, $body) {
            return AuthService::generateApiToken(
                $body['name'] ?? 'API Token',
                $body['permissions'] ?? ['read'],
                $body['expires_at'] ?? null
            );
        }, true);

        // Revoke token
        $router->delete('/auth/tokens/{id}', function ($params, $body) {
            $success = AuthService::revokeApiToken((int)$params['id']);
            return ['success' => $success];
        }, true);

        // ========================================
        // Project Endpoints
        // ========================================

        // List projects
        $router->get('/projects', function ($params, $body) {
            $user = AuthService::user();
            $filters = [
                'status' => $_GET['status'] ?? null,
                'search' => $_GET['search'] ?? null,
                'limit' => $_GET['limit'] ?? 50,
                'offset' => $_GET['offset'] ?? 0
            ];

            $projects = Project::allByUser($user->id, $filters);
            $total = Project::countByUser($user->id, $filters);

            return [
                'success' => true,
                'projects' => array_map(fn($p) => $p->toArray(), $projects),
                'total' => $total,
                'limit' => $filters['limit'],
                'offset' => $filters['offset']
            ];
        }, true);

        // Get single project
        $router->get('/projects/{id}', function ($params, $body) {
            $user = AuthService::user();
            $project = Project::findByUser((int)$params['id'], $user->id);

            if (!$project) {
                throw new \Exception('Project not found');
            }

            return [
                'success' => true,
                'project' => $project->toArray(true)
            ];
        }, true);

        // Create project
        $router->post('/projects', function ($params, $body) {
            $user = AuthService::user();
            $body['user_id'] = $user->id;

            $project = Project::create($body);

            return [
                'success' => true,
                'project' => $project->toArray()
            ];
        }, true);

        // Update project
        $router->put('/projects/{id}', function ($params, $body) {
            $user = AuthService::user();
            $project = Project::findByUser((int)$params['id'], $user->id);

            if (!$project) {
                throw new \Exception('Project not found');
            }

            $project->projectNumber = $body['project_number'] ?? $project->projectNumber;
            $project->projectName = $body['project_name'] ?? $project->projectName;
            $project->customerName = $body['customer_name'] ?? $project->customerName;
            $project->location = $body['location'] ?? $project->location;
            $project->description = $body['description'] ?? $project->description;
            $project->status = $body['status'] ?? $project->status;
            $project->save();

            Project::logHistory($project->id, null, $user->id, 'updated', $body);

            return [
                'success' => true,
                'project' => $project->toArray()
            ];
        }, true);

        // Delete project
        $router->delete('/projects/{id}', function ($params, $body) {
            $user = AuthService::user();
            $project = Project::findByUser((int)$params['id'], $user->id);

            if (!$project) {
                throw new \Exception('Project not found');
            }

            $project->delete();

            return ['success' => true, 'message' => 'Project deleted'];
        }, true);

        // Get project history
        $router->get('/projects/{id}/history', function ($params, $body) {
            $user = AuthService::user();
            $project = Project::findByUser((int)$params['id'], $user->id);

            if (!$project) {
                throw new \Exception('Project not found');
            }

            return [
                'success' => true,
                'history' => $project->getHistory()
            ];
        }, true);

        // ========================================
        // Building Endpoints
        // ========================================

        // List buildings in project
        $router->get('/projects/{projectId}/buildings', function ($params, $body) {
            $user = AuthService::user();
            $project = Project::findByUser((int)$params['projectId'], $user->id);

            if (!$project) {
                throw new \Exception('Project not found');
            }

            $buildings = $project->getBuildings();

            return [
                'success' => true,
                'buildings' => array_map(fn($b) => $b->toArray(false), $buildings)
            ];
        }, true);

        // Get single building
        $router->get('/projects/{projectId}/buildings/{id}', function ($params, $body) {
            $user = AuthService::user();
            $project = Project::findByUser((int)$params['projectId'], $user->id);

            if (!$project) {
                throw new \Exception('Project not found');
            }

            $building = ProjectBuilding::findByProject((int)$params['id'], $project->id);

            if (!$building) {
                throw new \Exception('Building not found');
            }

            return [
                'success' => true,
                'building' => $building->toArray()
            ];
        }, true);

        // Create building
        $router->post('/projects/{projectId}/buildings', function ($params, $body) {
            $user = AuthService::user();
            $project = Project::findByUser((int)$params['projectId'], $user->id);

            if (!$project) {
                throw new \Exception('Project not found');
            }

            $building = $project->addBuilding($body);

            return [
                'success' => true,
                'building' => $building->toArray()
            ];
        }, true);

        // Update building
        $router->put('/projects/{projectId}/buildings/{id}', function ($params, $body) {
            $user = AuthService::user();
            $project = Project::findByUser((int)$params['projectId'], $user->id);

            if (!$project) {
                throw new \Exception('Project not found');
            }

            $building = ProjectBuilding::findByProject((int)$params['id'], $project->id);

            if (!$building) {
                throw new \Exception('Building not found');
            }

            $building->buildingNumber = $body['building_number'] ?? $building->buildingNumber;
            $building->buildingName = $body['building_name'] ?? $building->buildingName;
            $building->revisionNumber = $body['revision_number'] ?? $building->revisionNumber;
            $building->estimatedBy = $body['estimated_by'] ?? $building->estimatedBy;

            if (isset($body['input_data'])) {
                $building->inputData = $body['input_data'];
            }

            $building->save();

            Project::logHistory($project->id, $building->id, $user->id, 'building_updated', $body);

            return [
                'success' => true,
                'building' => $building->toArray()
            ];
        }, true);

        // Delete building
        $router->delete('/projects/{projectId}/buildings/{id}', function ($params, $body) {
            $user = AuthService::user();
            $project = Project::findByUser((int)$params['projectId'], $user->id);

            if (!$project) {
                throw new \Exception('Project not found');
            }

            $building = ProjectBuilding::findByProject((int)$params['id'], $project->id);

            if (!$building) {
                throw new \Exception('Building not found');
            }

            Project::logHistory($project->id, $building->id, $user->id, 'building_deleted', [
                'building_name' => $building->buildingName
            ]);

            $building->delete();

            return ['success' => true, 'message' => 'Building deleted'];
        }, true);

        // Duplicate building
        $router->post('/projects/{projectId}/buildings/{id}/duplicate', function ($params, $body) {
            $user = AuthService::user();
            $project = Project::findByUser((int)$params['projectId'], $user->id);

            if (!$project) {
                throw new \Exception('Project not found');
            }

            $building = ProjectBuilding::findByProject((int)$params['id'], $project->id);

            if (!$building) {
                throw new \Exception('Building not found');
            }

            $newBuilding = $building->duplicate();

            Project::logHistory($project->id, $newBuilding->id, $user->id, 'building_duplicated', [
                'source_building' => $building->buildingName
            ]);

            return [
                'success' => true,
                'building' => $newBuilding->toArray()
            ];
        }, true);

        // ========================================
        // Calculation Endpoints
        // ========================================

        // Calculate building
        $router->post('/projects/{projectId}/buildings/{id}/calculate', function ($params, $body) {
            $user = AuthService::user();
            $project = Project::findByUser((int)$params['projectId'], $user->id);

            if (!$project) {
                throw new \Exception('Project not found');
            }

            $building = ProjectBuilding::findByProject((int)$params['id'], $project->id);

            if (!$building) {
                throw new \Exception('Building not found');
            }

            // Use input from body if provided, otherwise use saved input
            $inputData = !empty($body['input_data']) ? $body['input_data'] : $building->inputData;

            // Create Building model for calculation
            $buildingModel = Building::fromArray($inputData);
            $errors = $buildingModel->validate();

            if (!empty($errors)) {
                throw new \Exception('Validation failed: ' . implode(', ', $errors));
            }

            // Run calculation
            $engine = new CalculationEngine();
            $bom = $engine->calculate($buildingModel);

            // Prepare calculated data
            $calculatedData = [
                'items' => $bom->toArray(),
                'summary' => [
                    'totalWeight' => $bom->getTotalWeight(),
                    'totalPrice' => $bom->getTotalPrice(),
                    'itemCount' => $bom->getItemCount()
                ],
                'dimensions' => [
                    'width' => $buildingModel->buildingWidth,
                    'length' => $buildingModel->buildingLength,
                    'backEaveHeight' => $buildingModel->backEaveHeight,
                    'frontEaveHeight' => $buildingModel->frontEaveHeight
                ]
            ];

            // Update building with calculation results
            $building->inputData = $inputData;
            $building->updateCalculatedData($calculatedData);

            Project::logHistory($project->id, $building->id, $user->id, 'calculated', [
                'total_weight' => $building->totalWeight,
                'total_price' => $building->totalPrice
            ]);

            return [
                'success' => true,
                'building' => $building->toArray()
            ];
        }, true);

        // Quick calculate (without saving)
        $router->post('/calculate', function ($params, $body) {
            $buildingModel = Building::fromArray($body);
            $errors = $buildingModel->validate();

            if (!empty($errors)) {
                throw new \Exception('Validation failed: ' . implode(', ', $errors));
            }

            $engine = new CalculationEngine();
            $bom = $engine->calculate($buildingModel);

            return [
                'success' => true,
                'items' => $bom->toArray(),
                'summary' => [
                    'totalWeight' => $bom->getTotalWeight(),
                    'totalPrice' => $bom->getTotalPrice(),
                    'itemCount' => $bom->getItemCount()
                ],
                'dimensions' => [
                    'width' => $buildingModel->buildingWidth,
                    'length' => $buildingModel->buildingLength,
                    'backEaveHeight' => $buildingModel->backEaveHeight,
                    'frontEaveHeight' => $buildingModel->frontEaveHeight
                ]
            ];
        }, true);

        // ========================================
        // Analytics Endpoints
        // ========================================

        // Get dashboard statistics
        $router->get('/analytics/dashboard', function ($params, $body) {
            $user = AuthService::user();
            $stats = $user->getStatistics();

            // Recent projects
            $recentProjects = Project::allByUser($user->id, ['limit' => 5]);

            return [
                'success' => true,
                'stats' => $stats,
                'recentProjects' => array_map(fn($p) => $p->toArray(), $recentProjects)
            ];
        }, true);

        // Get analytics data
        $router->get('/analytics/data', function ($params, $body) {
            $user = AuthService::user();

            // Get aggregated data by month
            $db = \QuickEst\Database\Connection::getInstance();

            // Projects by status
            $stmt = $db->prepare("
                SELECT status, COUNT(*) as count
                FROM projects WHERE user_id = ?
                GROUP BY status
            ");
            $stmt->execute([$user->id]);
            $byStatus = $stmt->fetchAll();

            // Monthly activity
            $stmt = $db->prepare("
                SELECT
                    strftime('%Y-%m', created_at) as month,
                    COUNT(*) as projects,
                    SUM((SELECT COUNT(*) FROM buildings WHERE project_id = p.id)) as buildings
                FROM projects p
                WHERE user_id = ?
                GROUP BY strftime('%Y-%m', created_at)
                ORDER BY month DESC
                LIMIT 12
            ");
            $stmt->execute([$user->id]);
            $monthly = $stmt->fetchAll();

            // Weight by project type
            $stmt = $db->prepare("
                SELECT
                    COALESCE(p.status, 'unknown') as category,
                    SUM(b.total_weight) as total_weight,
                    SUM(b.total_price) as total_price
                FROM buildings b
                JOIN projects p ON b.project_id = p.id
                WHERE p.user_id = ?
                GROUP BY p.status
            ");
            $stmt->execute([$user->id]);
            $byCategory = $stmt->fetchAll();

            return [
                'success' => true,
                'byStatus' => $byStatus,
                'monthly' => array_reverse($monthly),
                'byCategory' => $byCategory
            ];
        }, true);

        // ========================================
        // User Management (Admin only)
        // ========================================

        // List all users
        $router->get('/admin/users', function ($params, $body) {
            if (!AuthService::isAdmin()) {
                throw new \Exception('Admin access required');
            }

            $users = User::all();

            return [
                'success' => true,
                'users' => array_map(fn($u) => $u->toArray(), $users)
            ];
        }, true);

        // Create user
        $router->post('/admin/users', function ($params, $body) {
            if (!AuthService::isAdmin()) {
                throw new \Exception('Admin access required');
            }

            return AuthService::register($body);
        }, true);

        // Update user
        $router->put('/admin/users/{id}', function ($params, $body) {
            if (!AuthService::isAdmin()) {
                throw new \Exception('Admin access required');
            }

            $user = User::find((int)$params['id']);
            if (!$user) {
                throw new \Exception('User not found');
            }

            $user->username = $body['username'] ?? $user->username;
            $user->email = $body['email'] ?? $user->email;
            $user->fullName = $body['full_name'] ?? $user->fullName;
            $user->company = $body['company'] ?? $user->company;
            $user->role = $body['role'] ?? $user->role;
            $user->isActive = $body['is_active'] ?? $user->isActive;
            $user->save();

            if (!empty($body['password'])) {
                $user->updatePassword($body['password']);
            }

            return [
                'success' => true,
                'user' => $user->toArray()
            ];
        }, true);

        // Delete user
        $router->delete('/admin/users/{id}', function ($params, $body) {
            if (!AuthService::isAdmin()) {
                throw new \Exception('Admin access required');
            }

            $user = User::find((int)$params['id']);
            if (!$user) {
                throw new \Exception('User not found');
            }

            // Don't allow deleting self
            if ($user->id === AuthService::user()->id) {
                throw new \Exception('Cannot delete your own account');
            }

            $user->delete();

            return ['success' => true, 'message' => 'User deleted'];
        }, true);
    }
}
