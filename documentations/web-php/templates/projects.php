<?php
/**
 * QuickEst - Projects List Page
 */

use QuickEst\Services\AuthService;
use QuickEst\Models\Project;

$user = AuthService::user();
$filters = [
    'status' => $_GET['status'] ?? null,
    'search' => $_GET['search'] ?? null,
    'limit' => 20,
    'offset' => ($_GET['page_num'] ?? 1) - 1
];
$filters['offset'] *= $filters['limit'];

$projects = $user ? Project::allByUser($user->id, $filters) : [];
$totalProjects = $user ? Project::countByUser($user->id, $filters) : 0;
$totalPages = ceil($totalProjects / $filters['limit']);
$currentPage = ($_GET['page_num'] ?? 1);
?>

<div class="projects-container">
    <div class="projects-header">
        <h1>My Projects</h1>
        <button class="btn btn-primary" onclick="createNewProject()">+ New Project</button>
    </div>

    <!-- Filters -->
    <div class="filters-bar">
        <div class="search-box">
            <input type="text" id="search-input" placeholder="Search projects..."
                   value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
                   onkeypress="if(event.key==='Enter')applyFilters()">
            <button class="btn btn-sm" onclick="applyFilters()">Search</button>
        </div>
        <div class="status-filters">
            <button class="filter-btn <?= empty($filters['status']) ? 'active' : '' ?>"
                    onclick="filterByStatus('')">All</button>
            <button class="filter-btn <?= $filters['status'] === 'draft' ? 'active' : '' ?>"
                    onclick="filterByStatus('draft')">Draft</button>
            <button class="filter-btn <?= $filters['status'] === 'in_progress' ? 'active' : '' ?>"
                    onclick="filterByStatus('in_progress')">In Progress</button>
            <button class="filter-btn <?= $filters['status'] === 'completed' ? 'active' : '' ?>"
                    onclick="filterByStatus('completed')">Completed</button>
            <button class="filter-btn <?= $filters['status'] === 'archived' ? 'active' : '' ?>"
                    onclick="filterByStatus('archived')">Archived</button>
        </div>
    </div>

    <!-- Projects Grid -->
    <?php if (empty($projects)): ?>
        <div class="empty-state">
            <div class="empty-icon">📁</div>
            <h3>No Projects Found</h3>
            <p><?= empty($filters['search']) && empty($filters['status'])
                ? 'Create your first project to get started'
                : 'No projects match your filters' ?></p>
            <?php if (empty($filters['search']) && empty($filters['status'])): ?>
                <button class="btn btn-primary" onclick="createNewProject()">Create Project</button>
            <?php else: ?>
                <button class="btn btn-secondary" onclick="window.location='?page=projects'">Clear Filters</button>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="projects-grid">
            <?php foreach ($projects as $project):
                $summary = $project->getSummary();
            ?>
                <div class="project-card">
                    <div class="project-card-header">
                        <div class="project-status-badge status-<?= $project->status ?>">
                            <?= ucfirst(str_replace('_', ' ', $project->status)) ?>
                        </div>
                        <div class="project-actions">
                            <button class="btn-icon" onclick="event.stopPropagation(); showProjectMenu(<?= $project->id ?>)" title="More actions">⋮</button>
                        </div>
                    </div>
                    <div class="project-card-body" onclick="openProject(<?= $project->id ?>)">
                        <h3><?= htmlspecialchars($project->projectName) ?></h3>
                        <p class="project-number"><?= htmlspecialchars($project->projectNumber ?: 'No project number') ?></p>
                        <p class="customer-name"><?= htmlspecialchars($project->customerName ?: 'No customer') ?></p>
                    </div>
                    <div class="project-card-footer">
                        <div class="project-stats">
                            <span title="Buildings">🏢 <?= $summary['building_count'] ?? 0 ?></span>
                            <span title="Total Weight">⚖️ <?= number_format($summary['total_weight'] ?? 0, 0) ?> kg</span>
                        </div>
                        <div class="project-date">
                            <?= date('M j, Y', strtotime($project->updatedAt)) ?>
                        </div>
                    </div>

                    <!-- Dropdown Menu -->
                    <div id="menu-<?= $project->id ?>" class="project-dropdown-menu" style="display: none;">
                        <a href="?page=project&id=<?= $project->id ?>">Open</a>
                        <a href="#" onclick="duplicateProject(<?= $project->id ?>)">Duplicate</a>
                        <a href="#" onclick="exportProject(<?= $project->id ?>)">Export</a>
                        <hr>
                        <a href="#" onclick="deleteProject(<?= $project->id ?>)" class="danger">Delete</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($currentPage > 1): ?>
                    <a href="?page=projects&page_num=<?= $currentPage - 1 ?>&status=<?= $filters['status'] ?>&search=<?= urlencode($filters['search'] ?? '') ?>" class="page-btn">← Previous</a>
                <?php endif; ?>

                <span class="page-info">Page <?= $currentPage ?> of <?= $totalPages ?></span>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="?page=projects&page_num=<?= $currentPage + 1 ?>&status=<?= $filters['status'] ?>&search=<?= urlencode($filters['search'] ?? '') ?>" class="page-btn">Next →</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Create Project Modal (same as dashboard) -->
<div id="create-project-modal" class="modal-overlay" style="display: none;">
    <div class="modal-dialog" style="max-width: 500px;">
        <div class="modal-header">
            <h3>Create New Project</h3>
            <button class="modal-close" onclick="closeCreateProjectModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="create-project-form">
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
                    <textarea id="new-description" rows="3" placeholder="Project notes"></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeCreateProjectModal()">Cancel</button>
            <button class="btn btn-primary" onclick="submitCreateProject()">Create Project</button>
        </div>
    </div>
</div>

<style>
.projects-container {
    padding: 20px;
    max-width: 1400px;
    margin: 0 auto;
}

.projects-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.projects-header h1 {
    color: #217346;
    margin: 0;
}

.filters-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 10px;
}

.search-box {
    display: flex;
    gap: 8px;
}

.search-box input {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    width: 250px;
}

.status-filters {
    display: flex;
    gap: 5px;
}

.filter-btn {
    padding: 6px 12px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
    transition: all 0.2s;
}

.filter-btn:hover {
    background: #f5f5f5;
}

.filter-btn.active {
    background: #217346;
    color: white;
    border-color: #217346;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.empty-icon {
    font-size: 64px;
    margin-bottom: 20px;
}

.empty-state h3 {
    color: #333;
    margin: 0 0 10px 0;
}

.empty-state p {
    color: #666;
    margin: 0 0 20px 0;
}

.projects-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.project-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
    position: relative;
    transition: transform 0.2s, box-shadow 0.2s;
}

.project-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

.project-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    background: #f8f9fa;
}

.project-status-badge {
    font-size: 11px;
    padding: 3px 8px;
    border-radius: 10px;
    text-transform: uppercase;
}

.btn-icon {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    padding: 5px;
    color: #666;
}

.project-card-body {
    padding: 15px;
    cursor: pointer;
}

.project-card-body h3 {
    margin: 0 0 8px 0;
    color: #333;
    font-size: 16px;
}

.project-number {
    font-family: monospace;
    color: #217346;
    font-size: 13px;
    margin: 0 0 5px 0;
}

.customer-name {
    color: #666;
    font-size: 13px;
    margin: 0;
}

.project-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    border-top: 1px solid #eee;
    font-size: 12px;
}

.project-stats {
    display: flex;
    gap: 15px;
    color: #666;
}

.project-date {
    color: #999;
}

.project-dropdown-menu {
    position: absolute;
    top: 40px;
    right: 10px;
    background: white;
    border-radius: 4px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.15);
    z-index: 100;
    min-width: 120px;
}

.project-dropdown-menu a {
    display: block;
    padding: 8px 12px;
    color: #333;
    text-decoration: none;
    font-size: 13px;
}

.project-dropdown-menu a:hover {
    background: #f5f5f5;
}

.project-dropdown-menu a.danger {
    color: #dc2626;
}

.project-dropdown-menu hr {
    margin: 5px 0;
    border: none;
    border-top: 1px solid #eee;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 20px;
    margin-top: 30px;
    padding: 20px 0;
}

.page-btn {
    padding: 8px 16px;
    background: #217346;
    color: white;
    text-decoration: none;
    border-radius: 4px;
}

.page-info {
    color: #666;
}
</style>

<script>
function createNewProject() {
    document.getElementById('create-project-modal').style.display = 'flex';
}

function closeCreateProjectModal() {
    document.getElementById('create-project-modal').style.display = 'none';
}

async function submitCreateProject() {
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

function openProject(id) {
    window.location.href = '?page=project&id=' + id;
}

function showProjectMenu(id) {
    // Hide all other menus
    document.querySelectorAll('.project-dropdown-menu').forEach(m => m.style.display = 'none');
    // Show this menu
    const menu = document.getElementById('menu-' + id);
    menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
}

// Close menus when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.project-actions')) {
        document.querySelectorAll('.project-dropdown-menu').forEach(m => m.style.display = 'none');
    }
});

async function deleteProject(id) {
    if (!confirm('Are you sure you want to delete this project? This cannot be undone.')) {
        return;
    }

    try {
        const response = await fetch('?action=delete-project&id=' + id, { method: 'POST' });
        const result = await response.json();

        if (result.success) {
            window.location.reload();
        } else {
            alert(result.error || 'Failed to delete project');
        }
    } catch (error) {
        alert('Connection error');
    }
}

async function duplicateProject(id) {
    try {
        const response = await fetch('?action=duplicate-project&id=' + id, { method: 'POST' });
        const result = await response.json();

        if (result.success) {
            window.location.href = '?page=project&id=' + result.project.id;
        } else {
            alert(result.error || 'Failed to duplicate project');
        }
    } catch (error) {
        alert('Connection error');
    }
}

function exportProject(id) {
    window.location.href = '?action=export-project&id=' + id;
}

function applyFilters() {
    const search = document.getElementById('search-input').value;
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('search', search);
    currentUrl.searchParams.set('page_num', '1');
    window.location.href = currentUrl.toString();
}

function filterByStatus(status) {
    const currentUrl = new URL(window.location);
    if (status) {
        currentUrl.searchParams.set('status', status);
    } else {
        currentUrl.searchParams.delete('status');
    }
    currentUrl.searchParams.set('page_num', '1');
    window.location.href = currentUrl.toString();
}
</script>
