<?php
/**
 * QuickEst - Reports & Analytics Dashboard
 */

use QuickEst\Services\AuthService;
use QuickEst\Models\Project;
use QuickEst\Database\Connection;

$user = AuthService::user();
$stats = $user ? $user->getStatistics() : [];

// Get analytics data
$db = Connection::getInstance();

// Projects by status
$stmt = $db->prepare("SELECT status, COUNT(*) as count FROM projects WHERE user_id = ? GROUP BY status");
$stmt->execute([$user->id]);
$byStatus = $stmt->fetchAll();

// Monthly data
$stmt = $db->prepare("
    SELECT
        strftime('%Y-%m', p.created_at) as month,
        COUNT(DISTINCT p.id) as projects,
        COUNT(b.id) as buildings,
        COALESCE(SUM(b.total_weight), 0) as weight,
        COALESCE(SUM(b.total_price), 0) as price
    FROM projects p
    LEFT JOIN buildings b ON b.project_id = p.id
    WHERE p.user_id = ?
    GROUP BY strftime('%Y-%m', p.created_at)
    ORDER BY month DESC
    LIMIT 12
");
$stmt->execute([$user->id]);
$monthlyData = array_reverse($stmt->fetchAll());

// Top projects by value
$stmt = $db->prepare("
    SELECT p.id, p.project_name, p.customer_name,
           COUNT(b.id) as building_count,
           COALESCE(SUM(b.total_weight), 0) as total_weight,
           COALESCE(SUM(b.total_price), 0) as total_price
    FROM projects p
    LEFT JOIN buildings b ON b.project_id = p.id
    WHERE p.user_id = ?
    GROUP BY p.id
    ORDER BY total_price DESC
    LIMIT 10
");
$stmt->execute([$user->id]);
$topProjects = $stmt->fetchAll();

// Recent calculations
$stmt = $db->prepare("
    SELECT b.id, b.building_name, b.total_weight, b.total_price, b.calculated_at,
           p.project_name
    FROM buildings b
    JOIN projects p ON b.project_id = p.id
    WHERE p.user_id = ? AND b.calculated_at IS NOT NULL
    ORDER BY b.calculated_at DESC
    LIMIT 10
");
$stmt->execute([$user->id]);
$recentCalculations = $stmt->fetchAll();
?>

<div class="reports-container">
    <div class="reports-header">
        <h1>Reports & Analytics</h1>
        <div class="header-actions">
            <button class="btn btn-secondary" onclick="exportReport('csv')">Export CSV</button>
            <button class="btn btn-secondary" onclick="exportReport('pdf')">Export PDF</button>
        </div>
    </div>

    <!-- Overview Stats -->
    <div class="overview-stats">
        <div class="stat-card large">
            <div class="stat-main">
                <div class="stat-value"><?= number_format($stats['project_count'] ?? 0) ?></div>
                <div class="stat-label">Total Projects</div>
            </div>
            <div class="stat-chart" id="projects-sparkline"></div>
        </div>
        <div class="stat-card large">
            <div class="stat-main">
                <div class="stat-value"><?= number_format($stats['building_count'] ?? 0) ?></div>
                <div class="stat-label">Total Buildings</div>
            </div>
            <div class="stat-chart" id="buildings-sparkline"></div>
        </div>
        <div class="stat-card large">
            <div class="stat-main">
                <div class="stat-value"><?= number_format($stats['total_weight'] ?? 0, 0) ?></div>
                <div class="stat-label">Total Weight (kg)</div>
            </div>
            <div class="stat-trend">
                <?php
                $avgWeight = $stats['building_count'] > 0 ? ($stats['total_weight'] / $stats['building_count']) : 0;
                ?>
                Avg: <?= number_format($avgWeight, 0) ?> kg/building
            </div>
        </div>
        <div class="stat-card large">
            <div class="stat-main">
                <div class="stat-value"><?= number_format($stats['total_price'] ?? 0, 0) ?></div>
                <div class="stat-label">Total Value (AED)</div>
            </div>
            <div class="stat-trend">
                <?php
                $avgPrice = $stats['building_count'] > 0 ? ($stats['total_price'] / $stats['building_count']) : 0;
                ?>
                Avg: <?= number_format($avgPrice, 0) ?> AED/building
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="charts-row">
        <!-- Monthly Activity Chart -->
        <div class="chart-card wide">
            <div class="card-header">
                <h3>Monthly Activity</h3>
                <div class="chart-legend">
                    <span class="legend-item"><span class="dot projects"></span> Projects</span>
                    <span class="legend-item"><span class="dot buildings"></span> Buildings</span>
                </div>
            </div>
            <div class="card-body">
                <div id="monthly-chart" class="chart-container"></div>
            </div>
        </div>

        <!-- Status Breakdown -->
        <div class="chart-card">
            <div class="card-header">
                <h3>Project Status</h3>
            </div>
            <div class="card-body">
                <div id="status-chart" class="chart-container">
                    <?php if (empty($byStatus)): ?>
                        <p class="no-data">No data available</p>
                    <?php else: ?>
                        <div class="status-bars">
                            <?php
                            $total = array_sum(array_column($byStatus, 'count'));
                            $colors = [
                                'draft' => '#9e9e9e',
                                'in_progress' => '#ff9800',
                                'completed' => '#4caf50',
                                'archived' => '#9c27b0'
                            ];
                            foreach ($byStatus as $item):
                                $pct = $total > 0 ? ($item['count'] / $total * 100) : 0;
                                $color = $colors[$item['status']] ?? '#666';
                            ?>
                                <div class="status-bar-item">
                                    <div class="status-label">
                                        <span><?= ucfirst(str_replace('_', ' ', $item['status'])) ?></span>
                                        <span><?= $item['count'] ?> (<?= number_format($pct, 1) ?>%)</span>
                                    </div>
                                    <div class="status-bar">
                                        <div class="status-bar-fill" style="width: <?= $pct ?>%; background: <?= $color ?>"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Tables Row -->
    <div class="tables-row">
        <!-- Top Projects -->
        <div class="data-card">
            <div class="card-header">
                <h3>Top Projects by Value</h3>
            </div>
            <div class="card-body">
                <?php if (empty($topProjects)): ?>
                    <p class="no-data">No projects yet</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>Buildings</th>
                                <th>Weight</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topProjects as $project): ?>
                                <tr onclick="window.location='?page=project&id=<?= $project['id'] ?>'" style="cursor: pointer;">
                                    <td>
                                        <div class="project-cell">
                                            <span class="project-name"><?= htmlspecialchars($project['project_name']) ?></span>
                                            <span class="customer-name"><?= htmlspecialchars($project['customer_name'] ?: '-') ?></span>
                                        </div>
                                    </td>
                                    <td><?= $project['building_count'] ?></td>
                                    <td><?= number_format($project['total_weight'], 0) ?> kg</td>
                                    <td><strong><?= number_format($project['total_price'], 0) ?> AED</strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Calculations -->
        <div class="data-card">
            <div class="card-header">
                <h3>Recent Calculations</h3>
            </div>
            <div class="card-body">
                <?php if (empty($recentCalculations)): ?>
                    <p class="no-data">No calculations yet</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Building</th>
                                <th>Weight</th>
                                <th>Price</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentCalculations as $calc): ?>
                                <tr>
                                    <td>
                                        <div class="project-cell">
                                            <span class="project-name"><?= htmlspecialchars($calc['building_name'] ?: 'Building') ?></span>
                                            <span class="customer-name"><?= htmlspecialchars($calc['project_name']) ?></span>
                                        </div>
                                    </td>
                                    <td><?= number_format($calc['total_weight'], 0) ?> kg</td>
                                    <td><?= number_format($calc['total_price'], 0) ?> AED</td>
                                    <td><?= date('M j', strtotime($calc['calculated_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Price Analysis -->
    <div class="analysis-section">
        <div class="card-header">
            <h3>Price & Weight Analysis</h3>
        </div>
        <div class="analysis-grid">
            <?php
            // Calculate averages
            $totalArea = 0;
            $stmt = $db->prepare("
                SELECT COALESCE(SUM(b.floor_area), 0) as total_area
                FROM buildings b
                JOIN projects p ON b.project_id = p.id
                WHERE p.user_id = ?
            ");
            $stmt->execute([$user->id]);
            $totalArea = $stmt->fetch()['total_area'] ?? 1;

            $pricePerSqm = $totalArea > 0 ? ($stats['total_price'] / $totalArea) : 0;
            $weightPerSqm = $totalArea > 0 ? ($stats['total_weight'] / $totalArea) : 0;
            $pricePerKg = $stats['total_weight'] > 0 ? ($stats['total_price'] / $stats['total_weight']) : 0;
            ?>
            <div class="analysis-card">
                <div class="analysis-icon">📐</div>
                <div class="analysis-value"><?= number_format($pricePerSqm, 2) ?></div>
                <div class="analysis-label">AED/m² (Avg Price)</div>
            </div>
            <div class="analysis-card">
                <div class="analysis-icon">⚖️</div>
                <div class="analysis-value"><?= number_format($weightPerSqm, 2) ?></div>
                <div class="analysis-label">kg/m² (Avg Weight)</div>
            </div>
            <div class="analysis-card">
                <div class="analysis-icon">💰</div>
                <div class="analysis-value"><?= number_format($pricePerKg, 2) ?></div>
                <div class="analysis-label">AED/kg (Avg Price)</div>
            </div>
            <div class="analysis-card">
                <div class="analysis-icon">📊</div>
                <div class="analysis-value"><?= number_format($totalArea, 0) ?></div>
                <div class="analysis-label">Total m² Estimated</div>
            </div>
        </div>
    </div>
</div>

<style>
.reports-container {
    padding: 20px;
    max-width: 1400px;
    margin: 0 auto;
}

.reports-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.reports-header h1 {
    color: #217346;
    margin: 0;
}

.header-actions {
    display: flex;
    gap: 10px;
}

.overview-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 25px;
}

.stat-card.large {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.stat-main {
    margin-bottom: 10px;
}

.stat-card.large .stat-value {
    font-size: 28px;
    font-weight: bold;
    color: #217346;
}

.stat-card.large .stat-label {
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
}

.stat-trend {
    font-size: 12px;
    color: #888;
}

.charts-row {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
    margin-bottom: 25px;
}

.chart-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.chart-card.wide {
    grid-column: span 1;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
}

.card-header h3 {
    margin: 0;
    font-size: 16px;
    color: #333;
}

.chart-legend {
    display: flex;
    gap: 15px;
    font-size: 12px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 5px;
}

.dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.dot.projects { background: #217346; }
.dot.buildings { background: #2196f3; }

.card-body {
    padding: 20px;
}

.chart-container {
    min-height: 200px;
}

.no-data {
    text-align: center;
    color: #999;
    padding: 40px;
}

.status-bars {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.status-bar-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.status-label {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
}

.status-bar {
    height: 24px;
    background: #f0f0f0;
    border-radius: 4px;
    overflow: hidden;
}

.status-bar-fill {
    height: 100%;
    border-radius: 4px;
    transition: width 0.3s;
}

.tables-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 25px;
}

.data-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    text-align: left;
    padding: 10px;
    font-size: 11px;
    text-transform: uppercase;
    color: #666;
    border-bottom: 1px solid #eee;
}

.data-table td {
    padding: 12px 10px;
    border-bottom: 1px solid #f5f5f5;
    font-size: 13px;
}

.data-table tr:hover {
    background: #f9f9f9;
}

.project-cell {
    display: flex;
    flex-direction: column;
}

.project-cell .project-name {
    font-weight: 500;
    color: #333;
}

.project-cell .customer-name {
    font-size: 11px;
    color: #999;
}

.analysis-section {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    padding: 20px;
}

.analysis-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-top: 15px;
}

.analysis-card {
    text-align: center;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.analysis-icon {
    font-size: 28px;
    margin-bottom: 10px;
}

.analysis-value {
    font-size: 24px;
    font-weight: bold;
    color: #217346;
}

.analysis-label {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}

@media (max-width: 1024px) {
    .overview-stats,
    .analysis-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .charts-row,
    .tables-row {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 600px) {
    .overview-stats,
    .analysis-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Monthly chart data
const monthlyData = <?= json_encode($monthlyData) ?>;

document.addEventListener('DOMContentLoaded', function() {
    renderMonthlyChart();
});

function renderMonthlyChart() {
    const container = document.getElementById('monthly-chart');

    if (!monthlyData || monthlyData.length === 0) {
        container.innerHTML = '<p class="no-data">No data available</p>';
        return;
    }

    const maxProjects = Math.max(...monthlyData.map(d => d.projects || 0), 1);
    const maxBuildings = Math.max(...monthlyData.map(d => d.buildings || 0), 1);
    const maxValue = Math.max(maxProjects, maxBuildings);

    let html = '<div style="display: flex; align-items: flex-end; height: 180px; gap: 8px; padding: 10px 0;">';

    monthlyData.forEach(item => {
        const projectHeight = (item.projects / maxValue * 150) + 10;
        const buildingHeight = (item.buildings / maxValue * 150) + 10;
        const monthParts = (item.month || '').split('-');
        const monthNames = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const monthLabel = monthNames[parseInt(monthParts[1])] || monthParts[1];

        html += `
            <div style="flex: 1; display: flex; flex-direction: column; align-items: center;">
                <div style="display: flex; gap: 3px; align-items: flex-end; height: 150px;">
                    <div style="background: #217346; width: 12px; height: ${projectHeight}px; border-radius: 2px 2px 0 0;"
                         title="Projects: ${item.projects}"></div>
                    <div style="background: #2196f3; width: 12px; height: ${buildingHeight}px; border-radius: 2px 2px 0 0;"
                         title="Buildings: ${item.buildings}"></div>
                </div>
                <div style="font-size: 10px; color: #666; margin-top: 5px;">${monthLabel}</div>
            </div>
        `;
    });

    html += '</div>';
    container.innerHTML = html;
}

function exportReport(format) {
    window.location.href = '?action=export-report&format=' + format;
}
</script>
