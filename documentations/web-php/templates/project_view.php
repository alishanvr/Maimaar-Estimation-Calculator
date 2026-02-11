<?php
/**
 * QuickEst - Project View (Multi-Building Support)
 */

use QuickEst\Services\AuthService;
use QuickEst\Models\Project;

$user = AuthService::user();
$projectId = (int)($_GET['id'] ?? 0);
$project = $projectId ? Project::findByUser($projectId, $user->id) : null;

if (!$project) {
    echo '<div class="error-state"><h2>Project Not Found</h2><a href="?page=projects" class="btn btn-primary">Back to Projects</a></div>';
    return;
}

$buildings = $project->getBuildings();
$summary = $project->getSummary();
?>

<div class="project-view-container">
    <!-- Project Header -->
    <div class="project-view-header">
        <div class="header-left">
            <a href="?page=projects" class="back-link">← Back to Projects</a>
            <h1><?= htmlspecialchars($project->projectName) ?></h1>
            <div class="project-meta-info">
                <span class="project-num"><?= htmlspecialchars($project->projectNumber ?: 'No Number') ?></span>
                <span class="separator">•</span>
                <span><?= htmlspecialchars($project->customerName ?: 'No Customer') ?></span>
                <span class="separator">•</span>
                <span class="status-badge status-<?= $project->status ?>"><?= ucfirst(str_replace('_', ' ', $project->status)) ?></span>
            </div>
        </div>
        <div class="header-actions">
            <button class="btn btn-secondary" onclick="editProject()">Edit Project</button>
            <button class="btn btn-primary" onclick="addBuilding()">+ Add Building</button>
        </div>
    </div>

    <!-- Project Summary Cards -->
    <div class="project-summary-grid">
        <div class="summary-card">
            <div class="summary-icon">🏢</div>
            <div class="summary-content">
                <div class="summary-value"><?= $summary['building_count'] ?? 0 ?></div>
                <div class="summary-label">Buildings</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon">📐</div>
            <div class="summary-content">
                <div class="summary-value"><?= number_format($summary['total_area'] ?? 0, 0) ?> m²</div>
                <div class="summary-label">Total Area</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon">⚖️</div>
            <div class="summary-content">
                <div class="summary-value"><?= number_format($summary['total_weight'] ?? 0, 0) ?> kg</div>
                <div class="summary-label">Total Weight</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon">💰</div>
            <div class="summary-content">
                <div class="summary-value"><?= number_format($summary['total_price'] ?? 0, 0) ?> AED</div>
                <div class="summary-label">Total Value</div>
            </div>
        </div>
    </div>

    <!-- Buildings List -->
    <div class="buildings-section">
        <div class="section-header">
            <h2>Buildings</h2>
            <div class="section-actions">
                <button class="btn btn-sm btn-secondary" onclick="exportAllBuildings()">Export All</button>
            </div>
        </div>

        <?php if (empty($buildings)): ?>
            <div class="empty-buildings">
                <div class="empty-icon">🏗️</div>
                <h3>No Buildings Yet</h3>
                <p>Add your first building to this project</p>
                <button class="btn btn-primary" onclick="addBuilding()">Add Building</button>
            </div>
        <?php else: ?>
            <div class="buildings-table-container">
                <table class="buildings-table">
                    <thead>
                        <tr>
                            <th>Building</th>
                            <th>Dimensions</th>
                            <th>Area</th>
                            <th>Weight</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($buildings as $building):
                            $dims = $building->inputData;
                            $width = 0;
                            $length = 0;
                            // Parse dimensions from spans/bays
                            if (!empty($dims['spans'])) {
                                $parts = explode('@', $dims['spans']);
                                if (count($parts) == 2) $width = (float)$parts[0] * (float)$parts[1];
                            }
                            if (!empty($dims['bays'])) {
                                $parts = explode('@', $dims['bays']);
                                if (count($parts) == 2) $length = (float)$parts[0] * (float)$parts[1];
                            }
                        ?>
                            <tr onclick="openBuilding(<?= $project->id ?>, <?= $building->id ?>)" style="cursor: pointer;">
                                <td>
                                    <div class="building-name"><?= htmlspecialchars($building->buildingName ?: 'Building ' . $building->buildingNumber) ?></div>
                                    <div class="building-number">Rev. <?= htmlspecialchars($building->revisionNumber) ?></div>
                                </td>
                                <td>
                                    <?php if ($width && $length): ?>
                                        <?= number_format($width, 1) ?> × <?= number_format($length, 1) ?> m
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= number_format($building->floorArea, 0) ?> m²</td>
                                <td>
                                    <?php if ($building->totalWeight > 0): ?>
                                        <?= number_format($building->totalWeight, 0) ?> kg
                                    <?php else: ?>
                                        <span class="not-calculated" title="Click Calculate to get weight">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($building->totalPrice > 0): ?>
                                        <?= number_format($building->totalPrice, 0) ?> AED
                                    <?php else: ?>
                                        <span class="not-calculated" title="Click Calculate to get price">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= $building->status ?>">
                                        <?= ucfirst($building->status) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons" onclick="event.stopPropagation();">
                                        <button class="btn-icon" onclick="editBuilding(<?= $building->id ?>)" title="Edit">✏️</button>
                                        <button class="btn-icon" onclick="calculateBuilding(<?= $building->id ?>)" title="Calculate">📊</button>
                                        <button class="btn-icon" onclick="duplicateBuilding(<?= $building->id ?>)" title="Duplicate">📋</button>
                                        <button class="btn-icon" onclick="deleteBuilding(<?= $building->id ?>)" title="Delete">🗑️</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="totals-row">
                            <td colspan="2"><strong>Project Total</strong></td>
                            <td><strong><?= number_format($summary['total_area'] ?? 0, 0) ?> m²</strong></td>
                            <td><strong><?= number_format($summary['total_weight'] ?? 0, 0) ?> kg</strong></td>
                            <td><strong><?= number_format($summary['total_price'] ?? 0, 0) ?> AED</strong></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Project Info & History -->
    <div class="project-info-grid">
        <div class="info-card">
            <h3>Project Details</h3>
            <div class="info-row">
                <span class="info-label">Location:</span>
                <span class="info-value"><?= htmlspecialchars($project->location ?: '-') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Created:</span>
                <span class="info-value"><?= date('M j, Y', strtotime($project->createdAt)) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Last Updated:</span>
                <span class="info-value"><?= date('M j, Y H:i', strtotime($project->updatedAt)) ?></span>
            </div>
            <?php if ($project->description): ?>
                <div class="info-row">
                    <span class="info-label">Description:</span>
                    <span class="info-value"><?= nl2br(htmlspecialchars($project->description)) ?></span>
                </div>
            <?php endif; ?>
        </div>
        <div class="info-card">
            <h3>Recent Activity</h3>
            <div class="activity-list" id="activity-list">
                <p class="loading-text">Loading activity...</p>
            </div>
        </div>
    </div>
</div>

<!-- Add Building Modal -->
<div id="add-building-modal" class="modal-overlay" style="display: none;">
    <div class="modal-dialog" style="max-width: 600px;">
        <div class="modal-header">
            <h3>Add Building</h3>
            <button class="modal-close" onclick="closeAddBuildingModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="add-building-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>Building Name</label>
                        <input type="text" id="bld-name" placeholder="e.g., Main Warehouse">
                    </div>
                    <div class="form-group">
                        <label>Building Number</label>
                        <input type="text" id="bld-number" placeholder="e.g., 1">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Spans (e.g., 2@24)</label>
                        <input type="text" id="bld-spans" value="1@24" placeholder="Count@Width">
                    </div>
                    <div class="form-group">
                        <label>Bays (e.g., 6@6)</label>
                        <input type="text" id="bld-bays" value="6@6" placeholder="Count@Length">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Back Eave Height (m)</label>
                        <input type="number" id="bld-beh" value="8" step="0.1">
                    </div>
                    <div class="form-group">
                        <label>Front Eave Height (m)</label>
                        <input type="number" id="bld-feh" value="8" step="0.1">
                    </div>
                </div>
                <div class="form-group">
                    <label>Estimated By</label>
                    <input type="text" id="bld-estimated-by" value="<?= htmlspecialchars($user->fullName) ?>">
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="bld-auto-calculate" checked>
                        Calculate immediately after adding
                    </label>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeAddBuildingModal()">Cancel</button>
            <button class="btn btn-primary" onclick="submitAddBuilding()">Add Building</button>
        </div>
    </div>
</div>

<!-- Edit Project Modal -->
<div id="edit-project-modal" class="modal-overlay" style="display: none;">
    <div class="modal-dialog" style="max-width: 500px;">
        <div class="modal-header">
            <h3>Edit Project</h3>
            <button class="modal-close" onclick="closeEditProjectModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="edit-project-form">
                <div class="form-group">
                    <label>Project Name *</label>
                    <input type="text" id="edit-project-name" value="<?= htmlspecialchars($project->projectName) ?>" required>
                </div>
                <div class="form-group">
                    <label>Project Number</label>
                    <input type="text" id="edit-project-number" value="<?= htmlspecialchars($project->projectNumber) ?>">
                </div>
                <div class="form-group">
                    <label>Customer Name</label>
                    <input type="text" id="edit-customer-name" value="<?= htmlspecialchars($project->customerName) ?>">
                </div>
                <div class="form-group">
                    <label>Location</label>
                    <input type="text" id="edit-location" value="<?= htmlspecialchars($project->location) ?>">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select id="edit-status">
                        <option value="draft" <?= $project->status === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="in_progress" <?= $project->status === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                        <option value="completed" <?= $project->status === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="archived" <?= $project->status === 'archived' ? 'selected' : '' ?>>Archived</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea id="edit-description" rows="3"><?= htmlspecialchars($project->description) ?></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeEditProjectModal()">Cancel</button>
            <button class="btn btn-primary" onclick="submitEditProject()">Save Changes</button>
        </div>
    </div>
</div>

<style>
.project-view-container {
    padding: 20px;
    max-width: 1400px;
    margin: 0 auto;
}

.project-view-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 2px solid #217346;
}

.back-link {
    color: #666;
    text-decoration: none;
    font-size: 13px;
    display: block;
    margin-bottom: 8px;
}

.back-link:hover {
    color: #217346;
}

.project-view-header h1 {
    margin: 0 0 10px 0;
    color: #333;
}

.project-meta-info {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    color: #666;
}

.project-num {
    font-family: monospace;
    color: #217346;
    font-weight: 500;
}

.separator {
    color: #ccc;
}

.header-actions {
    display: flex;
    gap: 10px;
}

.project-summary-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.summary-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.summary-icon {
    font-size: 32px;
}

.summary-value {
    font-size: 22px;
    font-weight: bold;
    color: #333;
}

.summary-label {
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
}

.buildings-section {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 30px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
}

.section-header h2 {
    margin: 0;
    font-size: 18px;
    color: #333;
}

.empty-buildings {
    text-align: center;
    padding: 50px 20px;
}

.empty-icon {
    font-size: 48px;
    margin-bottom: 15px;
}

.buildings-table-container {
    overflow-x: auto;
}

.buildings-table {
    width: 100%;
    border-collapse: collapse;
}

.buildings-table th {
    background: #f8f9fa;
    padding: 12px 15px;
    text-align: left;
    font-size: 12px;
    text-transform: uppercase;
    color: #666;
    border-bottom: 1px solid #eee;
}

.buildings-table td {
    padding: 15px;
    border-bottom: 1px solid #eee;
}

.buildings-table tr:hover {
    background: #f8f9fa;
}

.building-name {
    font-weight: 500;
    color: #333;
}

.building-number {
    font-size: 12px;
    color: #666;
}

.text-muted {
    color: #999;
}

.action-buttons {
    display: flex;
    gap: 5px;
}

.btn-icon {
    background: none;
    border: 1px solid #ddd;
    padding: 5px 8px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
}

.btn-icon:hover {
    background: #f5f5f5;
}

.totals-row {
    background: #e8f5e9;
}

.totals-row td {
    border-bottom: none;
}

.project-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.info-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.info-card h3 {
    margin: 0 0 15px 0;
    font-size: 16px;
    color: #333;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.info-row {
    display: flex;
    margin-bottom: 10px;
}

.info-label {
    width: 120px;
    color: #666;
    font-size: 13px;
}

.info-value {
    flex: 1;
    color: #333;
    font-size: 13px;
}

.activity-list {
    max-height: 300px;
    overflow-y: auto;
}

.activity-item {
    padding: 10px 0;
    border-bottom: 1px solid #eee;
    font-size: 13px;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-action {
    color: #333;
}

.activity-time {
    color: #999;
    font-size: 11px;
    margin-top: 3px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-weight: normal;
}

.checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.not-calculated {
    color: #999;
    font-style: italic;
    cursor: help;
}

@media (max-width: 1024px) {
    .project-summary-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .project-info-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 600px) {
    .project-summary-grid {
        grid-template-columns: 1fr;
    }
    .project-view-header {
        flex-direction: column;
        gap: 15px;
    }
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
const projectId = <?= $project->id ?>;

// Load activity on page load
document.addEventListener('DOMContentLoaded', loadActivity);

async function loadActivity() {
    try {
        const response = await fetch('?action=project-history&id=' + projectId);
        const result = await response.json();

        const container = document.getElementById('activity-list');

        if (result.success && result.history && result.history.length > 0) {
            container.innerHTML = result.history.slice(0, 10).map(item => `
                <div class="activity-item">
                    <div class="activity-action">
                        <strong>${item.username || 'User'}</strong> ${formatAction(item.action)}
                    </div>
                    <div class="activity-time">${formatDate(item.created_at)}</div>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<p class="text-muted">No activity yet</p>';
        }
    } catch (error) {
        document.getElementById('activity-list').innerHTML = '<p class="text-muted">Could not load activity</p>';
    }
}

function formatAction(action) {
    const actions = {
        'created': 'created this project',
        'updated': 'updated project details',
        'building_added': 'added a new building',
        'building_updated': 'updated a building',
        'building_deleted': 'deleted a building',
        'building_duplicated': 'duplicated a building',
        'calculated': 'ran calculation'
    };
    return actions[action] || action;
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
}

function addBuilding() {
    document.getElementById('add-building-modal').style.display = 'flex';
}

function closeAddBuildingModal() {
    document.getElementById('add-building-modal').style.display = 'none';
}

async function submitAddBuilding() {
    const autoCalculate = document.getElementById('bld-auto-calculate').checked;
    const data = {
        building_name: document.getElementById('bld-name').value,
        building_number: document.getElementById('bld-number').value,
        estimated_by: document.getElementById('bld-estimated-by').value,
        input_data: {
            spans: document.getElementById('bld-spans').value,
            bays: document.getElementById('bld-bays').value,
            backEaveHeight: parseFloat(document.getElementById('bld-beh').value) || 8,
            frontEaveHeight: parseFloat(document.getElementById('bld-feh').value) || 8,
            slopes: '1@0.1',
            windSpeed: 130
        }
    };

    try {
        const response = await fetch('?action=add-building&project_id=' + projectId, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            // If auto-calculate is checked, run calculation
            if (autoCalculate && result.building && result.building.id) {
                const calcResponse = await fetch('?action=calculate-building&project_id=' + projectId + '&building_id=' + result.building.id, {
                    method: 'POST'
                });
                const calcResult = await calcResponse.json();
                if (!calcResult.success) {
                    console.warn('Auto-calculation failed:', calcResult.error);
                }
            }
            window.location.reload();
        } else {
            alert(result.error || 'Failed to add building');
        }
    } catch (error) {
        alert('Connection error');
    }
}

function editProject() {
    document.getElementById('edit-project-modal').style.display = 'flex';
}

function closeEditProjectModal() {
    document.getElementById('edit-project-modal').style.display = 'none';
}

async function submitEditProject() {
    const data = {
        project_name: document.getElementById('edit-project-name').value,
        project_number: document.getElementById('edit-project-number').value,
        customer_name: document.getElementById('edit-customer-name').value,
        location: document.getElementById('edit-location').value,
        status: document.getElementById('edit-status').value,
        description: document.getElementById('edit-description').value
    };

    try {
        const response = await fetch('?action=update-project&id=' + projectId, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            window.location.reload();
        } else {
            alert(result.error || 'Failed to update project');
        }
    } catch (error) {
        alert('Connection error');
    }
}

function openBuilding(projectId, buildingId) {
    window.location.href = '?page=building&project_id=' + projectId + '&id=' + buildingId;
}

function editBuilding(buildingId) {
    window.location.href = '?page=building&project_id=' + projectId + '&id=' + buildingId + '&mode=edit';
}

async function calculateBuilding(buildingId) {
    if (!confirm('Run calculation for this building?')) return;

    // Show loading state
    const btn = event.target.closest('.btn-icon');
    const originalText = btn.innerHTML;
    btn.innerHTML = '⏳';
    btn.disabled = true;

    try {
        const response = await fetch('?action=calculate-building&project_id=' + projectId + '&building_id=' + buildingId, {
            method: 'POST'
        });

        const result = await response.json();

        if (result.success) {
            alert('Calculation completed!\n\nWeight: ' + result.building.totalWeight.toLocaleString() + ' kg\nPrice: ' + result.building.totalPrice.toLocaleString() + ' AED');
            window.location.reload();
        } else {
            alert('Calculation Error:\n\n' + (result.error || 'Unknown error'));
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    } catch (error) {
        alert('Connection error: ' + error.message);
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

async function duplicateBuilding(buildingId) {
    try {
        const response = await fetch('?action=duplicate-building&project_id=' + projectId + '&building_id=' + buildingId, {
            method: 'POST'
        });

        const result = await response.json();

        if (result.success) {
            window.location.reload();
        } else {
            alert(result.error || 'Failed to duplicate');
        }
    } catch (error) {
        alert('Connection error');
    }
}

async function deleteBuilding(buildingId) {
    if (!confirm('Delete this building? This cannot be undone.')) return;

    try {
        const response = await fetch('?action=delete-building&project_id=' + projectId + '&building_id=' + buildingId, {
            method: 'POST'
        });

        const result = await response.json();

        if (result.success) {
            window.location.reload();
        } else {
            alert(result.error || 'Failed to delete');
        }
    } catch (error) {
        alert('Connection error');
    }
}

function exportAllBuildings() {
    window.location.href = '?action=export-project&id=' + projectId;
}
</script>
