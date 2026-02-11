<?php
/**
 * QuickEst - Main Entry Point
 *
 * Pre-Engineered Metal Building Estimation System
 * PHP Web Application
 */

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Autoloader
spl_autoload_register(function ($class) {
    // Convert namespace to path
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

// Initialize authentication
use QuickEst\Services\AuthService;
use QuickEst\Models\Project;
use QuickEst\Models\ProjectBuilding;
use QuickEst\Models\User;
use QuickEst\Services\ExcelImporter;

AuthService::init();

// Simple routing
$page = $_GET['page'] ?? 'input';
$action = $_GET['action'] ?? '';

// Protected pages that require authentication
$protectedPages = ['dashboard', 'projects', 'project', 'building', 'reports', 'settings'];
$authPages = ['login', 'register'];

// Redirect to login for protected pages if not authenticated
if (in_array($page, $protectedPages) && !AuthService::check()) {
    header('Location: ?page=login');
    exit;
}

// Redirect to dashboard if already logged in and trying to access auth pages
if (in_array($page, $authPages) && AuthService::check()) {
    header('Location: ?page=dashboard');
    exit;
}

// ========================================
// AUTHENTICATION ACTIONS
// ========================================

if ($action === 'login') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    $result = AuthService::login($input['username'] ?? '', $input['password'] ?? '');
    echo json_encode($result);
    exit;
}

if ($action === 'register') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    $result = AuthService::register($input);
    echo json_encode($result);
    exit;
}

if ($action === 'logout') {
    AuthService::logout();
    header('Location: ?page=login');
    exit;
}

// ========================================
// PROJECT ACTIONS (Require Auth)
// ========================================

if ($action === 'create-project' && AuthService::check()) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    $input['user_id'] = AuthService::user()->id;

    try {
        $project = Project::create($input);
        echo json_encode(['success' => true, 'project' => $project->toArray()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'update-project' && AuthService::check()) {
    header('Content-Type: application/json');
    $projectId = (int)($_GET['id'] ?? 0);
    $input = json_decode(file_get_contents('php://input'), true);

    try {
        $project = Project::findByUser($projectId, AuthService::user()->id);
        if (!$project) throw new Exception('Project not found');

        $project->projectName = $input['project_name'] ?? $project->projectName;
        $project->projectNumber = $input['project_number'] ?? $project->projectNumber;
        $project->customerName = $input['customer_name'] ?? $project->customerName;
        $project->location = $input['location'] ?? $project->location;
        $project->description = $input['description'] ?? $project->description;
        $project->status = $input['status'] ?? $project->status;
        $project->save();

        echo json_encode(['success' => true, 'project' => $project->toArray()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'delete-project' && AuthService::check()) {
    header('Content-Type: application/json');
    $projectId = (int)($_GET['id'] ?? 0);

    try {
        $project = Project::findByUser($projectId, AuthService::user()->id);
        if (!$project) throw new Exception('Project not found');
        $project->delete();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'project-history' && AuthService::check()) {
    header('Content-Type: application/json');
    $projectId = (int)($_GET['id'] ?? 0);

    try {
        $project = Project::findByUser($projectId, AuthService::user()->id);
        if (!$project) throw new Exception('Project not found');
        echo json_encode(['success' => true, 'history' => $project->getHistory()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ========================================
// BUILDING ACTIONS (Require Auth)
// ========================================

if ($action === 'add-building' && AuthService::check()) {
    header('Content-Type: application/json');
    $projectId = (int)($_GET['project_id'] ?? 0);
    $input = json_decode(file_get_contents('php://input'), true);

    try {
        $project = Project::findByUser($projectId, AuthService::user()->id);
        if (!$project) throw new Exception('Project not found');

        $building = $project->addBuilding($input);
        echo json_encode(['success' => true, 'building' => $building->toArray()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'calculate-building' && AuthService::check()) {
    header('Content-Type: application/json');
    $projectId = (int)($_GET['project_id'] ?? 0);
    $buildingId = (int)($_GET['building_id'] ?? 0);

    try {
        $project = Project::findByUser($projectId, AuthService::user()->id);
        if (!$project) throw new Exception('Project not found');

        $building = ProjectBuilding::findByProject($buildingId, $projectId);
        if (!$building) throw new Exception('Building not found');

        // Run calculation
        $buildingModel = \QuickEst\Models\Building::fromArray($building->inputData);
        $errors = $buildingModel->validate();
        if (!empty($errors)) throw new Exception(implode(', ', $errors));

        $engine = new \QuickEst\Services\CalculationEngine();
        $bom = $engine->calculate($buildingModel);

        $calculatedData = [
            'items' => $bom->toArray(),
            'summary' => [
                'totalWeight' => $bom->getTotalWeight(),
                'totalPrice' => $bom->getTotalPrice(),
                'itemCount' => $bom->getItemCount()
            ],
            'dimensions' => $engine->getDimensions()
        ];

        $building->updateCalculatedData($calculatedData);

        Project::logHistory($projectId, $buildingId, AuthService::user()->id, 'calculated', [
            'weight' => $building->totalWeight,
            'price' => $building->totalPrice
        ]);

        echo json_encode(['success' => true, 'building' => $building->toArray()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'duplicate-building' && AuthService::check()) {
    header('Content-Type: application/json');
    $projectId = (int)($_GET['project_id'] ?? 0);
    $buildingId = (int)($_GET['building_id'] ?? 0);

    try {
        $project = Project::findByUser($projectId, AuthService::user()->id);
        if (!$project) throw new Exception('Project not found');

        $building = ProjectBuilding::findByProject($buildingId, $projectId);
        if (!$building) throw new Exception('Building not found');

        $newBuilding = $building->duplicate();
        echo json_encode(['success' => true, 'building' => $newBuilding->toArray()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'delete-building' && AuthService::check()) {
    header('Content-Type: application/json');
    $projectId = (int)($_GET['project_id'] ?? 0);
    $buildingId = (int)($_GET['building_id'] ?? 0);

    try {
        $project = Project::findByUser($projectId, AuthService::user()->id);
        if (!$project) throw new Exception('Project not found');

        $building = ProjectBuilding::findByProject($buildingId, $projectId);
        if (!$building) throw new Exception('Building not found');

        $building->delete();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ========================================
// IMPORT/EXPORT ACTIONS
// ========================================

if ($action === 'import') {
    header('Content-Type: application/json');

    try {
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('No file uploaded or upload error');
        }

        $importer = new ExcelImporter();
        $result = $importer->import($_FILES['file']['tmp_name']);

        echo json_encode($result);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'export-report' && AuthService::check()) {
    $format = $_GET['format'] ?? 'csv';

    try {
        $db = \QuickEst\Database\Connection::getInstance();
        $userId = AuthService::user()->id;
        $user = AuthService::user();
        $stats = $user->getStatistics();

        // Get all projects with buildings
        $stmt = $db->prepare("
            SELECT p.id, p.project_name, p.project_number, p.customer_name, p.status, p.created_at,
                   COUNT(b.id) as building_count,
                   COALESCE(SUM(b.total_weight), 0) as total_weight,
                   COALESCE(SUM(b.total_price), 0) as total_price,
                   COALESCE(SUM(b.floor_area), 0) as total_area
            FROM projects p
            LEFT JOIN buildings b ON b.project_id = p.id
            WHERE p.user_id = ?
            GROUP BY p.id
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$userId]);
        $projects = $stmt->fetchAll();

        $filename = 'QuickEst_Report_' . date('Y-m-d');

        if ($format === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");

            echo "\xEF\xBB\xBF"; // BOM for Excel UTF-8
            echo "QuickEst - Analytics Report\n";
            echo "Generated: " . date('Y-m-d H:i:s') . "\n";
            echo "User: " . $user->username . "\n\n";

            echo "SUMMARY\n";
            echo "Total Projects," . ($stats['project_count'] ?? 0) . "\n";
            echo "Total Buildings," . ($stats['building_count'] ?? 0) . "\n";
            echo "Total Weight (kg)," . number_format($stats['total_weight'] ?? 0, 2) . "\n";
            echo "Total Value (AED)," . number_format($stats['total_price'] ?? 0, 2) . "\n\n";

            echo "PROJECT DETAILS\n";
            echo "Project Name,Project No,Customer,Status,Buildings,Weight (kg),Value (AED),Area (m2),Created\n";

            foreach ($projects as $p) {
                echo '"' . str_replace('"', '""', $p['project_name']) . '",';
                echo '"' . ($p['project_number'] ?? '') . '",';
                echo '"' . str_replace('"', '""', $p['customer_name'] ?? '') . '",';
                echo $p['status'] . ',';
                echo $p['building_count'] . ',';
                echo number_format($p['total_weight'], 2) . ',';
                echo number_format($p['total_price'], 2) . ',';
                echo number_format($p['total_area'], 2) . ',';
                echo $p['created_at'] . "\n";
            }

            exit;
        }

        if ($format === 'pdf') {
            // Simple HTML that can be printed as PDF
            header('Content-Type: text/html; charset=utf-8');
            ?>
            <!DOCTYPE html>
            <html>
            <head>
                <title>QuickEst Report</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    h1 { color: #217346; }
                    .summary { background: #f5f5f5; padding: 15px; margin: 20px 0; }
                    .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }
                    .stat { text-align: center; }
                    .stat-value { font-size: 24px; font-weight: bold; color: #217346; }
                    .stat-label { font-size: 12px; color: #666; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background: #217346; color: white; }
                    tr:nth-child(even) { background: #f9f9f9; }
                    .footer { margin-top: 30px; font-size: 12px; color: #666; }
                    @media print { body { margin: 0; } }
                </style>
            </head>
            <body>
                <h1>QuickEst Analytics Report</h1>
                <p>Generated: <?= date('Y-m-d H:i:s') ?> | User: <?= htmlspecialchars($user->username) ?></p>

                <div class="summary">
                    <h3>Summary</h3>
                    <div class="summary-grid">
                        <div class="stat">
                            <div class="stat-value"><?= number_format($stats['project_count'] ?? 0) ?></div>
                            <div class="stat-label">Total Projects</div>
                        </div>
                        <div class="stat">
                            <div class="stat-value"><?= number_format($stats['building_count'] ?? 0) ?></div>
                            <div class="stat-label">Total Buildings</div>
                        </div>
                        <div class="stat">
                            <div class="stat-value"><?= number_format($stats['total_weight'] ?? 0, 0) ?></div>
                            <div class="stat-label">Total Weight (kg)</div>
                        </div>
                        <div class="stat">
                            <div class="stat-value"><?= number_format($stats['total_price'] ?? 0, 0) ?></div>
                            <div class="stat-label">Total Value (AED)</div>
                        </div>
                    </div>
                </div>

                <h3>Project Details</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Project Name</th>
                            <th>Project No</th>
                            <th>Customer</th>
                            <th>Status</th>
                            <th>Buildings</th>
                            <th>Weight (kg)</th>
                            <th>Value (AED)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['project_name']) ?></td>
                            <td><?= htmlspecialchars($p['project_number'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($p['customer_name'] ?? '-') ?></td>
                            <td><?= ucfirst(str_replace('_', ' ', $p['status'])) ?></td>
                            <td><?= $p['building_count'] ?></td>
                            <td style="text-align: right;"><?= number_format($p['total_weight'], 2) ?></td>
                            <td style="text-align: right;"><?= number_format($p['total_price'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="footer">
                    <p>QuickEst v2.0 - Pre-Engineered Metal Building Estimation System</p>
                </div>

                <script>window.print();</script>
            </body>
            </html>
            <?php
            exit;
        }

        // JSON format
        header('Content-Type: application/json');
        header("Content-Disposition: attachment; filename=\"{$filename}.json\"");
        echo json_encode([
            'report' => 'QuickEst Analytics',
            'generated' => date('Y-m-d H:i:s'),
            'user' => $user->username,
            'summary' => $stats,
            'projects' => $projects
        ], JSON_PRETTY_PRINT);
        exit;

    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

if ($action === 'analytics' && AuthService::check()) {
    header('Content-Type: application/json');

    try {
        $db = \QuickEst\Database\Connection::getInstance();
        $userId = AuthService::user()->id;

        $stmt = $db->prepare("
            SELECT
                strftime('%Y-%m', p.created_at) as month,
                COUNT(DISTINCT p.id) as projects,
                COUNT(b.id) as buildings
            FROM projects p
            LEFT JOIN buildings b ON b.project_id = p.id
            WHERE p.user_id = ?
            GROUP BY strftime('%Y-%m', p.created_at)
            ORDER BY month DESC
            LIMIT 12
        ");
        $stmt->execute([$userId]);
        $monthly = array_reverse($stmt->fetchAll());

        echo json_encode(['success' => true, 'monthly' => $monthly]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'export-project' && AuthService::check()) {
    $projectId = (int)($_GET['id'] ?? 0);

    try {
        $project = Project::findByUser($projectId, AuthService::user()->id);
        if (!$project) throw new Exception('Project not found');

        $exportData = [
            'version' => '2.0',
            'exportedAt' => date('Y-m-d H:i:s'),
            'project' => $project->toArray(true)
        ];

        $filename = preg_replace('/[^a-z0-9]/i', '_', $project->projectName) . '_' . date('Y-m-d');

        header('Content-Type: application/json');
        header("Content-Disposition: attachment; filename=\"{$filename}.qep\"");
        echo json_encode($exportData, JSON_PRETTY_PRINT);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ========================================
// CALCULATION ACTIONS (Original)
// ========================================

// Handle AJAX requests
if ($action === 'calculate') {
    header('Content-Type: application/json');

    try {
        $input = json_decode(file_get_contents('php://input'), true);

        $building = \QuickEst\Models\Building::fromArray($input);
        $errors = $building->validate();

        if (!empty($errors)) {
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }

        $engine = new \QuickEst\Services\CalculationEngine();
        $bom = $engine->calculate($building);

        echo json_encode([
            'success' => true,
            'dimensions' => $engine->getDimensions(),
            'loads' => $engine->getLoads(),
            'items' => $bom->toArray(),
            'summary' => [
                'totalWeight' => round($bom->getTotalWeight(), 2),
                'totalPrice' => round($bom->getTotalPrice(), 2),
                'itemCount' => $bom->getItemCount(),
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Handle Mezzanine calculation
if ($action === 'calculate-mezzanine') {
    header('Content-Type: application/json');

    try {
        $input = json_decode(file_get_contents('php://input'), true);

        $calculator = new \QuickEst\Services\MezzanineCalculator();
        $bom = $calculator->calculate($input);

        echo json_encode([
            'success' => true,
            'items' => $bom->toArray(),
            'summary' => [
                'totalWeight' => round($bom->getTotalWeight(), 2),
                'totalPrice' => round($bom->getTotalPrice(), 2),
                'itemCount' => $bom->getItemCount(),
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Handle Crane calculation
if ($action === 'calculate-crane') {
    header('Content-Type: application/json');

    try {
        $input = json_decode(file_get_contents('php://input'), true);

        $calculator = new \QuickEst\Services\CraneCalculator();
        $bom = $calculator->calculate($input);

        echo json_encode([
            'success' => true,
            'items' => $bom->toArray(),
            'summary' => [
                'totalWeight' => round($bom->getTotalWeight(), 2),
                'totalPrice' => round($bom->getTotalPrice(), 2),
                'itemCount' => $bom->getItemCount(),
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Handle Accessory calculation
if ($action === 'calculate-accessory') {
    header('Content-Type: application/json');

    try {
        $input = json_decode(file_get_contents('php://input'), true);

        $calculator = new \QuickEst\Services\AccessoryCalculator();
        $bom = $calculator->calculate($input);

        echo json_encode([
            'success' => true,
            'items' => $bom->toArray(),
            'summary' => [
                'totalWeight' => round($bom->getTotalWeight(), 2),
                'totalPrice' => round($bom->getTotalPrice(), 2),
                'itemCount' => $bom->getItemCount(),
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Handle Partition calculation
if ($action === 'calculate-partition') {
    header('Content-Type: application/json');

    try {
        $input = json_decode(file_get_contents('php://input'), true);

        $calculator = new \QuickEst\Services\PartitionCalculator();
        $bom = $calculator->calculate($input);

        echo json_encode([
            'success' => true,
            'items' => $bom->toArray(),
            'summary' => [
                'totalWeight' => round($bom->getTotalWeight(), 2),
                'totalPrice' => round($bom->getTotalPrice(), 2),
                'itemCount' => $bom->getItemCount(),
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Handle Canopy calculation
if ($action === 'calculate-canopy') {
    header('Content-Type: application/json');

    try {
        $input = json_decode(file_get_contents('php://input'), true);

        $calculator = new \QuickEst\Services\CanopyCalculator();
        $bom = $calculator->calculate($input);

        echo json_encode([
            'success' => true,
            'items' => $bom->toArray(),
            'summary' => [
                'totalWeight' => round($bom->getTotalWeight(), 2),
                'totalPrice' => round($bom->getTotalPrice(), 2),
                'itemCount' => $bom->getItemCount(),
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Handle Monitor (Roof Monitor) calculation
if ($action === 'calculate-monitor') {
    header('Content-Type: application/json');

    try {
        $input = json_decode(file_get_contents('php://input'), true);

        $calculator = new \QuickEst\Services\MonitorCalculator();
        $bom = $calculator->calculate($input);

        echo json_encode([
            'success' => true,
            'items' => $bom->toArray(),
            'summary' => [
                'totalWeight' => round($bom->getTotalWeight(), 2),
                'totalPrice' => round($bom->getTotalPrice(), 2),
                'itemCount' => $bom->getItemCount(),
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Handle Liner calculation
if ($action === 'calculate-liner') {
    header('Content-Type: application/json');

    try {
        $input = json_decode(file_get_contents('php://input'), true);

        $calculator = new \QuickEst\Services\LinerCalculator();
        $bom = $calculator->calculate($input);

        echo json_encode([
            'success' => true,
            'items' => $bom->toArray(),
            'summary' => [
                'totalWeight' => round($bom->getTotalWeight(), 2),
                'totalPrice' => round($bom->getTotalPrice(), 2),
                'itemCount' => $bom->getItemCount(),
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Handle export request
if ($action === 'export') {
    $format = $_GET['format'] ?? 'csv';
    $rawData = file_get_contents('php://input');
    $data = json_decode($rawData, true);

    if (!$data || !isset($data['items'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'No data provided']);
        exit;
    }

    $projectInfo = $data['projectInfo'] ?? [];
    $items = $data['items'] ?? [];
    $summary = $data['summary'] ?? [];

    $projectNumber = $projectInfo['projectNumber'] ?? 'Export';
    $buildingName = $projectInfo['buildingName'] ?? 'Building';
    $date = date('Y-m-d');
    $filename = "QuickEst_{$projectNumber}_{$date}";

    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");

        // Output BOM marker for Excel UTF-8 compatibility
        echo "\xEF\xBB\xBF";

        // Project header info
        echo "QuickEst - Bill of Materials Export\n";
        echo "Project: " . ($projectInfo['projectName'] ?? '') . "\n";
        echo "Project No: " . ($projectInfo['projectNumber'] ?? '') . "\n";
        echo "Building: " . ($projectInfo['buildingName'] ?? '') . "\n";
        echo "Customer: " . ($projectInfo['customerName'] ?? '') . "\n";
        echo "Date: {$date}\n";
        echo "\n";

        // Column headers
        echo "Line,DB Code,Sales Code,Cost Code,Description,Size,Unit,Qty,Unit Wt (kg),Total Wt (kg),Mat. Cost (AED),Mfg. Cost (AED),Unit Price (AED),Total Price (AED),Phase No.\n";

        // Data rows
        foreach ($items as $item) {
            if (isset($item['isSeparator']) && $item['isSeparator']) {
                echo str_repeat('-', 50) . "\n";
                continue;
            }

            $row = [
                $item['lineNumber'] ?? '',
                $item['dbCode'] ?? '',
                $item['salesCode'] ?? '',
                $item['costCode'] ?? '',
                '"' . str_replace('"', '""', $item['description'] ?? '') . '"',
                $item['size'] ?? '',
                $item['unit'] ?? '',
                isset($item['quantity']) ? number_format($item['quantity'], 2) : '',
                isset($item['unitWeight']) ? number_format($item['unitWeight'], 4) : '',
                isset($item['totalWeight']) ? number_format($item['totalWeight'], 2) : '',
                isset($item['materialCost']) ? number_format($item['materialCost'], 2) : '',
                isset($item['manufacturingCost']) ? number_format($item['manufacturingCost'], 2) : '',
                isset($item['unitPrice']) ? number_format($item['unitPrice'], 2) : '',
                isset($item['totalPrice']) ? number_format($item['totalPrice'], 2) : '',
                $item['phaseNumber'] ?? ''
            ];
            echo implode(',', $row) . "\n";
        }

        // Summary section
        echo "\n";
        echo "SUMMARY\n";
        echo "Total Weight (kg):," . number_format($summary['totalWeight'] ?? 0, 2) . "\n";
        echo "Total Price (AED):," . number_format($summary['totalPrice'] ?? 0, 2) . "\n";
        echo "Item Count:," . ($summary['itemCount'] ?? 0) . "\n";

        exit;
    }

    // Excel format (XLSX) - using simple HTML table that Excel can open
    if ($format === 'xlsx' || $format === 'excel') {
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}.xls\"");

        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>';
        echo '<h2>QuickEst - Bill of Materials</h2>';
        echo '<table border="1" cellpadding="3" cellspacing="0">';

        // Project info
        echo '<tr><td colspan="15" style="background:#217346;color:white;font-weight:bold;">Project Information</td></tr>';
        echo '<tr><td>Project:</td><td colspan="14">' . htmlspecialchars($projectInfo['projectName'] ?? '') . '</td></tr>';
        echo '<tr><td>Project No:</td><td colspan="14">' . htmlspecialchars($projectInfo['projectNumber'] ?? '') . '</td></tr>';
        echo '<tr><td>Building:</td><td colspan="14">' . htmlspecialchars($projectInfo['buildingName'] ?? '') . '</td></tr>';
        echo '<tr><td>Customer:</td><td colspan="14">' . htmlspecialchars($projectInfo['customerName'] ?? '') . '</td></tr>';
        echo '<tr><td>Date:</td><td colspan="14">' . $date . '</td></tr>';
        echo '<tr><td colspan="15">&nbsp;</td></tr>';

        // Column headers
        echo '<tr style="background:#f0f0f0;font-weight:bold;">';
        echo '<td>Line</td><td>DB Code</td><td>Sales</td><td>Cost Code</td><td>Description</td>';
        echo '<td>Size</td><td>Unit</td><td>Qty</td><td>Unit Wt</td><td>Total Wt</td>';
        echo '<td>Mat. Cost</td><td>Mfg. Cost</td><td>Unit Price</td><td>Total Price</td><td>Phase</td>';
        echo '</tr>';

        // Data rows
        foreach ($items as $item) {
            $isHeader = isset($item['isHeader']) && $item['isHeader'];
            $isSeparator = isset($item['isSeparator']) && $item['isSeparator'];

            $style = '';
            if ($isHeader) $style = 'background:#e8f5e9;font-weight:bold;';
            if ($isSeparator) $style = 'background:#f5f5f5;';

            echo '<tr style="' . $style . '">';
            echo '<td>' . ($item['lineNumber'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($item['dbCode'] ?? '') . '</td>';
            echo '<td>' . ($item['salesCode'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($item['costCode'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($item['description'] ?? '') . '</td>';
            echo '<td>' . ($item['size'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($item['unit'] ?? '') . '</td>';
            echo '<td style="text-align:right;">' . (isset($item['quantity']) ? number_format($item['quantity'], 2) : '') . '</td>';
            echo '<td style="text-align:right;">' . (isset($item['unitWeight']) ? number_format($item['unitWeight'], 4) : '') . '</td>';
            echo '<td style="text-align:right;">' . (isset($item['totalWeight']) ? number_format($item['totalWeight'], 2) : '') . '</td>';
            echo '<td style="text-align:right;">' . (isset($item['materialCost']) ? number_format($item['materialCost'], 2) : '') . '</td>';
            echo '<td style="text-align:right;">' . (isset($item['manufacturingCost']) ? number_format($item['manufacturingCost'], 2) : '') . '</td>';
            echo '<td style="text-align:right;">' . (isset($item['unitPrice']) ? number_format($item['unitPrice'], 2) : '') . '</td>';
            echo '<td style="text-align:right;">' . (isset($item['totalPrice']) ? number_format($item['totalPrice'], 2) : '') . '</td>';
            echo '<td>' . htmlspecialchars($item['phaseNumber'] ?? '') . '</td>';
            echo '</tr>';
        }

        // Summary
        echo '<tr><td colspan="15">&nbsp;</td></tr>';
        echo '<tr style="background:#217346;color:white;font-weight:bold;"><td colspan="15">SUMMARY</td></tr>';
        echo '<tr><td colspan="9" style="text-align:right;font-weight:bold;">Total Weight (kg):</td>';
        echo '<td style="text-align:right;font-weight:bold;">' . number_format($summary['totalWeight'] ?? 0, 2) . '</td>';
        echo '<td colspan="3" style="text-align:right;font-weight:bold;">Total Price (AED):</td>';
        echo '<td style="text-align:right;font-weight:bold;">' . number_format($summary['totalPrice'] ?? 0, 2) . '</td><td></td></tr>';
        echo '<tr><td colspan="9"></td><td></td><td colspan="3" style="text-align:right;">Item Count:</td>';
        echo '<td style="text-align:right;">' . ($summary['itemCount'] ?? 0) . '</td><td></td></tr>';

        echo '</table></body></html>';
        exit;
    }

    // JSON format for API usage
    if ($format === 'json') {
        header('Content-Type: application/json');
        header("Content-Disposition: attachment; filename=\"{$filename}.json\"");
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }

    // Unknown format
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unknown export format: ' . $format]);
    exit;
}

// Page templates
$pageTitle = 'QuickEst - Building Estimation System';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>

    <!-- Handsontable for Excel-like interface -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/handsontable/dist/handsontable.full.min.css">
    <script src="https://cdn.jsdelivr.net/npm/handsontable/dist/handsontable.full.min.js"></script>

    <!-- Custom styles -->
    <link rel="stylesheet" href="css/style.css">

    <!-- Load session data immediately before any templates -->
    <script>
    // Load saved data synchronously before any DOMContentLoaded handlers
    window.quickEstData = null;
    window.quickEstInput = null;
    try {
        const savedData = sessionStorage.getItem('quickEstData');
        const savedInput = sessionStorage.getItem('quickEstInput');
        if (savedData) {
            window.quickEstData = JSON.parse(savedData);
            console.log('Session data loaded:', window.quickEstData?.items?.length || 0, 'items');
        }
        if (savedInput) {
            window.quickEstInput = JSON.parse(savedInput);
        }
    } catch (e) {
        console.log('Error loading session data:', e);
    }
    </script>
</head>
<body>
    <div id="app">
        <!-- Header -->
        <header class="app-header">
            <div class="logo">
                <a href="?page=<?= AuthService::check() ? 'dashboard' : 'input' ?>" style="text-decoration: none; color: inherit;">
                    <h1>QuickEst</h1>
                </a>
                <span class="version">v2.0 PHP</span>
            </div>
            <nav class="main-nav">
                <?php if (AuthService::check()): ?>
                    <!-- Logged-in navigation -->
                    <ul class="sheet-tabs">
                        <li class="tab <?= $page === 'dashboard' ? 'active' : '' ?>">
                            <a href="?page=dashboard">Dashboard</a>
                        </li>
                        <li class="tab <?= $page === 'projects' || $page === 'project' ? 'active' : '' ?>">
                            <a href="?page=projects">Projects</a>
                        </li>
                        <li class="tab <?= in_array($page, ['input', 'detail', 'fcpbs', 'rawmat']) ? 'active' : '' ?>">
                            <a href="?page=input">Estimator</a>
                        </li>
                        <li class="tab <?= $page === 'reports' ? 'active' : '' ?>">
                            <a href="?page=reports">Reports</a>
                        </li>
                    </ul>
                    <?php if (in_array($page, ['input', 'detail', 'fcpbs', 'rawmat'])): ?>
                    <!-- Estimator sub-tabs when logged in -->
                    <ul class="sheet-tabs sub-tabs">
                        <li class="tab <?= $page === 'input' ? 'active' : '' ?>">
                            <a href="?page=input">Input</a>
                        </li>
                        <li class="tab <?= $page === 'detail' ? 'active' : '' ?>">
                            <a href="?page=detail">Detail</a>
                        </li>
                        <li class="tab <?= $page === 'fcpbs' ? 'active' : '' ?>">
                            <a href="?page=fcpbs">FCPBS</a>
                        </li>
                        <li class="tab <?= $page === 'rawmat' ? 'active' : '' ?>">
                            <a href="?page=rawmat">Raw Mat</a>
                        </li>
                    </ul>
                    <?php endif; ?>
                <?php elseif (!in_array($page, ['login'])): ?>
                    <!-- Estimator tabs (when not logged in) -->
                    <ul class="sheet-tabs">
                        <li class="tab <?= $page === 'input' ? 'active' : '' ?>">
                            <a href="?page=input">Input</a>
                        </li>
                        <li class="tab <?= $page === 'detail' ? 'active' : '' ?>">
                            <a href="?page=detail">Detail</a>
                        </li>
                        <li class="tab <?= $page === 'fcpbs' ? 'active' : '' ?>">
                            <a href="?page=fcpbs">FCPBS</a>
                        </li>
                        <li class="tab <?= $page === 'rawmat' ? 'active' : '' ?>">
                            <a href="?page=rawmat">Raw Mat</a>
                        </li>
                    </ul>
                <?php endif; ?>
            </nav>
            <div class="header-actions">
                <?php if (in_array($page, ['input', 'detail', 'fcpbs', 'rawmat'])): ?>
                    <button id="btn-calculate" class="btn btn-primary">Calculate</button>
                    <button id="btn-export" class="btn btn-secondary">Export</button>
                    <button id="btn-print" class="btn btn-secondary" onclick="openPrintView()" title="Print Quotation">Print</button>
                    <button id="btn-save" class="btn btn-secondary" onclick="saveProject()" title="Save Project">Save</button>
                    <button id="btn-load" class="btn btn-secondary" onclick="showLoadProjectDialog()" title="Load Project">Load</button>
                    <button id="btn-preferences" class="btn btn-secondary" onclick="showPreferencesDialog()" title="User Preferences">⚙</button>
                    <button id="btn-new" class="btn btn-warning">New</button>
                <?php endif; ?>

                <?php if (AuthService::check()): ?>
                    <div class="user-menu">
                        <span class="user-name"><?= htmlspecialchars(AuthService::user()->username) ?></span>
                        <a href="?action=logout" class="btn btn-secondary btn-sm">Logout</a>
                    </div>
                <?php elseif ($page !== 'login'): ?>
                    <a href="?page=login" class="btn btn-primary">Sign In</a>
                <?php endif; ?>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="main-content">
            <?php
            switch ($page) {
                case 'login':
                    include BASE_PATH . '/templates/login.php';
                    break;
                case 'dashboard':
                    include BASE_PATH . '/templates/dashboard.php';
                    break;
                case 'projects':
                    include BASE_PATH . '/templates/projects.php';
                    break;
                case 'project':
                    include BASE_PATH . '/templates/project_view.php';
                    break;
                case 'reports':
                    include BASE_PATH . '/templates/reports.php';
                    break;
                case 'input':
                    include BASE_PATH . '/templates/input_form.php';
                    break;
                case 'detail':
                    include BASE_PATH . '/templates/detail_view.php';
                    break;
                case 'fcpbs':
                    include BASE_PATH . '/templates/fcpbs_report.php';
                    break;
                case 'rawmat':
                    include BASE_PATH . '/templates/rawmat_view.php';
                    break;
                default:
                    include BASE_PATH . '/templates/input_form.php';
            }
            ?>
        </main>

        <!-- Status Bar (like Excel) -->
        <footer class="status-bar">
            <div class="status-left">
                <span id="status-message">Ready</span>
            </div>
            <div class="status-center">
                <span id="calc-summary"></span>
            </div>
            <div class="status-right">
                <span id="cell-info"></span>
            </div>
        </footer>
    </div>

    <!-- Application JavaScript -->
    <script src="js/app.js"></script>
</body>
</html>
