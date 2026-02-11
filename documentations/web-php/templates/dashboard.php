<?php
/**
 * QuickEst - Dashboard
 *
 * Main dashboard showing projects, statistics, and quick actions
 */

use QuickEst\Services\AuthService;
use QuickEst\Models\Project;

$user = AuthService::user();
$stats = $user ? $user->getStatistics() : [];
$recentProjects = $user ? Project::allByUser($user->id, ['limit' => 5]) : [];
?>

<div class="dashboard-container">
    <!-- Welcome Header -->
    <div class="dashboard-header">
        <div class="welcome-section">
            <h1>Welcome, <?= htmlspecialchars($user->fullName ?: $user->username) ?></h1>
            <p>Manage your pre-engineered building estimates</p>
        </div>
        <div class="quick-actions">
            <button class="btn btn-primary" onclick="createNewProject()">
                + New Project
            </button>
            <button class="btn btn-secondary" onclick="showImportDialog()">
                Import
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card" style="border-left-color: #217346;">
            <div class="stat-icon">📁</div>
            <div class="stat-content">
                <div class="stat-value"><?= number_format($stats['project_count'] ?? 0) ?></div>
                <div class="stat-label">Total Projects</div>
            </div>
        </div>
        <div class="stat-card" style="border-left-color: #2196f3;">
            <div class="stat-icon">🏢</div>
            <div class="stat-content">
                <div class="stat-value"><?= number_format($stats['building_count'] ?? 0) ?></div>
                <div class="stat-label">Total Buildings</div>
            </div>
        </div>
        <div class="stat-card" style="border-left-color: #ff9800;">
            <div class="stat-icon">⚖️</div>
            <div class="stat-content">
                <div class="stat-value"><?= number_format($stats['total_weight'] ?? 0, 0) ?> kg</div>
                <div class="stat-label">Total Weight</div>
            </div>
        </div>
        <div class="stat-card" style="border-left-color: #9c27b0;">
            <div class="stat-icon">💰</div>
            <div class="stat-content">
                <div class="stat-value"><?= number_format($stats['total_price'] ?? 0, 0) ?> AED</div>
                <div class="stat-label">Total Value</div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="dashboard-grid">
        <!-- Recent Projects -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3>Recent Projects</h3>
                <a href="?page=projects" class="view-all">View All →</a>
            </div>
            <div class="card-body">
                <?php if (empty($recentProjects)): ?>
                    <div class="empty-state">
                        <p>No projects yet. Create your first project!</p>
                        <button class="btn btn-primary btn-sm" onclick="createNewProject()">Create Project</button>
                    </div>
                <?php else: ?>
                    <div class="project-list">
                        <?php foreach ($recentProjects as $project):
                            $summary = $project->getSummary();
                        ?>
                            <div class="project-item" onclick="openProject(<?= $project->id ?>)">
                                <div class="project-info">
                                    <div class="project-name"><?= htmlspecialchars($project->projectName) ?></div>
                                    <div class="project-meta">
                                        <?= htmlspecialchars($project->customerName ?: 'No customer') ?> •
                                        <?= $summary['building_count'] ?? 0 ?> building(s) •
                                        <?= number_format($summary['total_weight'] ?? 0, 0) ?> kg
                                    </div>
                                </div>
                                <div class="project-status status-<?= $project->status ?>">
                                    <?= ucfirst($project->status) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Start -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3>Quick Start</h3>
            </div>
            <div class="card-body">
                <div class="quick-start-grid">
                    <div class="quick-start-item" onclick="window.location='?page=input'">
                        <div class="qs-icon">📐</div>
                        <div class="qs-title">Quick Estimate</div>
                        <div class="qs-desc">Calculate without saving</div>
                    </div>
                    <div class="quick-start-item" onclick="createNewProject()">
                        <div class="qs-icon">📁</div>
                        <div class="qs-title">New Project</div>
                        <div class="qs-desc">Create a saved project</div>
                    </div>
                    <div class="quick-start-item" onclick="showImportDialog()">
                        <div class="qs-icon">📥</div>
                        <div class="qs-title">Import File</div>
                        <div class="qs-desc">Excel, CSV, or QEP</div>
                    </div>
                    <div class="quick-start-item" onclick="window.location='?page=reports'">
                        <div class="qs-icon">📊</div>
                        <div class="qs-title">Reports</div>
                        <div class="qs-desc">Analytics & history</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activity Chart -->
        <div class="dashboard-card wide">
            <div class="card-header">
                <h3>Activity Overview</h3>
            </div>
            <div class="card-body">
                <div id="activity-chart" style="height: 200px;">
                    <!-- Chart will be rendered here -->
                    <div class="chart-placeholder">
                        <p>Loading activity data...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Project Modal -->
<div id="create-project-modal" class="modal-overlay" style="display: none;">
    <div class="modal-dialog" style="max-width: 500px;">
        <div class="modal-header">
            <h3>Create New Project</h3>
            <button class="modal-close" onclick="closeCreateProjectModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="create-project-form" onsubmit="submitCreateProject(event)">
                <div class="form-group">
                    <label>Project Name *</label>
                    <input type="text" id="new-project-name" required placeholder="e.g., Warehouse Phase 1">
                </div>
                <div class="form-group">
                    <label>Project Number</label>
                    <input type="text" id="new-project-number" placeholder="e.g., HQ-O-25001">
                </div>
                <div class="form-group">
                    <label>Customer Name</label>
                    <input type="text" id="new-customer-name" placeholder="Customer or company name">
                </div>
                <div class="form-group">
                    <label>Location</label>
                    <input type="text" id="new-location" placeholder="Project location">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea id="new-description" rows="3" placeholder="Project notes or description"></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeCreateProjectModal()">Cancel</button>
            <button class="btn btn-primary" onclick="submitCreateProject(event)">Create Project</button>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div id="import-modal" class="modal-overlay" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3>Import File</h3>
            <button class="modal-close" onclick="closeImportDialog()">&times;</button>
        </div>
        <div class="modal-body">
            <p>Import data from Excel, CSV, or QuickEst project files.</p>
            <div class="file-upload-area" id="import-drop-zone">
                <input type="file" id="import-file-input" accept=".xlsx,.xls,.csv,.qep,.json" style="display: none;">
                <div class="drop-zone-content">
                    <span style="font-size: 48px; color: #217346;">📄</span>
                    <p>Drag & drop file here</p>
                    <p style="color: #666; font-size: 12px;">Supports: .xlsx, .csv, .qep, .json</p>
                    <button class="btn btn-primary" onclick="document.getElementById('import-file-input').click()">
                        Browse Files
                    </button>
                </div>
            </div>
            <div id="import-status" style="margin-top: 15px; display: none;"></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeImportDialog()">Cancel</button>
        </div>
    </div>
</div>

<style>
.dashboard-container {
    padding: 20px;
    max-width: 1400px;
    margin: 0 auto;
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.welcome-section h1 {
    color: #217346;
    margin: 0;
    font-size: 24px;
}

.welcome-section p {
    color: #666;
    margin: 5px 0 0 0;
}

.quick-actions {
    display: flex;
    gap: 10px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 25px;
}

.stat-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border-left: 4px solid #217346;
}

.stat-icon {
    font-size: 32px;
}

.stat-value {
    font-size: 24px;
    font-weight: bold;
    color: #333;
}

.stat-label {
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.dashboard-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.dashboard-card.wide {
    grid-column: span 2;
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

.view-all {
    color: #217346;
    text-decoration: none;
    font-size: 13px;
}

.card-body {
    padding: 20px;
}

.empty-state {
    text-align: center;
    padding: 30px;
    color: #666;
}

.project-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.project-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    background: #f8f9fa;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.2s;
}

.project-item:hover {
    background: #e8f5e9;
}

.project-name {
    font-weight: 500;
    color: #333;
}

.project-meta {
    font-size: 12px;
    color: #666;
    margin-top: 3px;
}

.project-status {
    font-size: 11px;
    padding: 4px 8px;
    border-radius: 12px;
    text-transform: uppercase;
}

.status-draft { background: #e0e0e0; color: #666; }
.status-in_progress { background: #fff3e0; color: #e65100; }
.status-completed { background: #e8f5e9; color: #2e7d32; }
.status-archived { background: #f3e5f5; color: #7b1fa2; }

.quick-start-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.quick-start-item {
    padding: 20px;
    text-align: center;
    background: #f8f9fa;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.quick-start-item:hover {
    background: #e8f5e9;
    transform: translateY(-2px);
}

.qs-icon {
    font-size: 32px;
    margin-bottom: 10px;
}

.qs-title {
    font-weight: 500;
    color: #333;
}

.qs-desc {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}

.chart-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #666;
}

/* Responsive */
@media (max-width: 1024px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
    .dashboard-card.wide {
        grid-column: span 1;
    }
}

@media (max-width: 600px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    .dashboard-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
}
</style>

<script>
function createNewProject() {
    document.getElementById('create-project-modal').style.display = 'flex';
}

function closeCreateProjectModal() {
    document.getElementById('create-project-modal').style.display = 'none';
}

async function submitCreateProject(event) {
    event.preventDefault();

    const data = {
        project_name: document.getElementById('new-project-name').value,
        project_number: document.getElementById('new-project-number').value,
        customer_name: document.getElementById('new-customer-name').value,
        location: document.getElementById('new-location').value,
        description: document.getElementById('new-description').value
    };

    try {
        const response = await fetch('?action=create-project', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            window.location.href = '?page=project&id=' + result.project.id;
        } else {
            alert(result.error || 'Failed to create project');
        }
    } catch (error) {
        alert('Connection error');
    }
}

function openProject(projectId) {
    window.location.href = '?page=project&id=' + projectId;
}

function showImportDialog() {
    document.getElementById('import-modal').style.display = 'flex';
    setupImportDropZone();
}

function closeImportDialog() {
    document.getElementById('import-modal').style.display = 'none';
}

function setupImportDropZone() {
    const dropZone = document.getElementById('import-drop-zone');
    const fileInput = document.getElementById('import-file-input');

    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('drag-over');
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('drag-over');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('drag-over');
        if (e.dataTransfer.files.length > 0) {
            handleImportFile(e.dataTransfer.files[0]);
        }
    });

    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            handleImportFile(e.target.files[0]);
        }
    });
}

async function handleImportFile(file) {
    const statusEl = document.getElementById('import-status');
    statusEl.style.display = 'block';
    statusEl.innerHTML = '<span style="color: #2196f3;">Importing...</span>';

    const formData = new FormData();
    formData.append('file', file);

    try {
        const response = await fetch('?action=import', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            statusEl.innerHTML = '<span style="color: #4caf50;">Import successful!</span>';

            // If data was imported, load it into the estimator
            if (result.inputData) {
                sessionStorage.setItem('quickEstInput', JSON.stringify(result.inputData));
                if (result.calculatedData) {
                    sessionStorage.setItem('quickEstData', JSON.stringify(result.calculatedData));
                }
                setTimeout(() => {
                    window.location.href = '?page=input';
                }, 1000);
            }
        } else {
            statusEl.innerHTML = '<span style="color: #f44336;">Error: ' + (result.error || 'Import failed') + '</span>';
        }
    } catch (error) {
        statusEl.innerHTML = '<span style="color: #f44336;">Connection error</span>';
    }
}

// Load activity chart on page load
document.addEventListener('DOMContentLoaded', function() {
    loadActivityChart();
});

async function loadActivityChart() {
    const chartDiv = document.getElementById('activity-chart');

    try {
        const response = await fetch('?action=analytics');
        const result = await response.json();

        if (result.success && result.monthly && result.monthly.length > 0) {
            renderSimpleChart(chartDiv, result.monthly);
        } else {
            chartDiv.innerHTML = '<div class="chart-placeholder"><p>No activity data available yet</p></div>';
        }
    } catch (error) {
        chartDiv.innerHTML = '<div class="chart-placeholder"><p>Could not load chart</p></div>';
    }
}

function renderSimpleChart(container, data) {
    const maxProjects = Math.max(...data.map(d => d.projects || 0), 1);

    let html = '<div style="display: flex; align-items: flex-end; height: 100%; gap: 10px; padding: 10px 0;">';

    data.forEach(item => {
        const height = ((item.projects || 0) / maxProjects * 150) + 20;
        const month = item.month ? item.month.split('-')[1] : '';
        const monthNames = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        html += `
            <div style="flex: 1; display: flex; flex-direction: column; align-items: center;">
                <div style="background: linear-gradient(180deg, #217346 0%, #2e8b57 100%);
                            width: 100%; height: ${height}px; border-radius: 4px 4px 0 0;
                            display: flex; align-items: flex-start; justify-content: center; padding-top: 5px;">
                    <span style="color: white; font-size: 11px; font-weight: bold;">${item.projects || 0}</span>
                </div>
                <div style="font-size: 10px; color: #666; margin-top: 5px;">${monthNames[parseInt(month)] || month}</div>
            </div>
        `;
    });

    html += '</div>';
    container.innerHTML = html;
}
</script>
