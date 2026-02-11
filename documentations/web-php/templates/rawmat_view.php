<?php
/**
 * QuickEst - Raw Material List View
 *
 * Displays grouped raw materials for procurement
 * Replicates the RawMat sheet with consolidated material requirements
 */
?>

<div class="spreadsheet-container">
    <div id="rawmat-report" style="padding: 20px; max-width: 1400px; margin: 0 auto;">

        <!-- Header -->
        <div style="text-align: center; margin-bottom: 20px;">
            <h2 style="color: #217346; margin-bottom: 5px;">Raw Material Requirements</h2>
            <p style="font-size: 12px; color: #666;">
                Project: <span id="rm-project">-</span> |
                Building: <span id="rm-building">-</span> |
                Date: <span id="rm-date">-</span>
            </p>
        </div>

        <!-- Summary Cards -->
        <div id="rm-summary-cards" style="display: none; margin-bottom: 25px;">
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px;">
                <div style="background: linear-gradient(135deg, #217346 0%, #2e8b57 100%); padding: 20px; border-radius: 8px; color: white;">
                    <div style="font-size: 11px; text-transform: uppercase; opacity: 0.9;">Total Items</div>
                    <div style="font-size: 28px; font-weight: bold;" id="rm-total-items">0</div>
                </div>
                <div style="background: linear-gradient(135deg, #2196f3 0%, #1976d2 100%); padding: 20px; border-radius: 8px; color: white;">
                    <div style="font-size: 11px; text-transform: uppercase; opacity: 0.9;">Unique Materials</div>
                    <div style="font-size: 28px; font-weight: bold;" id="rm-unique-count">0</div>
                </div>
                <div style="background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%); padding: 20px; border-radius: 8px; color: white;">
                    <div style="font-size: 11px; text-transform: uppercase; opacity: 0.9;">Total Weight</div>
                    <div style="font-size: 28px; font-weight: bold;" id="rm-weight-summary">0 kg</div>
                </div>
                <div style="background: linear-gradient(135deg, #9c27b0 0%, #7b1fa2 100%); padding: 20px; border-radius: 8px; color: white;">
                    <div style="font-size: 11px; text-transform: uppercase; opacity: 0.9;">Categories</div>
                    <div style="font-size: 28px; font-weight: bold;" id="rm-category-count">0</div>
                </div>
            </div>
        </div>

        <!-- Filter/Search Bar -->
        <div style="margin-bottom: 15px; display: flex; gap: 10px; align-items: center;">
            <input type="text" id="rm-search" placeholder="Search materials..."
                style="flex: 1; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;">
            <select id="rm-category-filter" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;">
                <option value="all">All Categories</option>
            </select>
            <button onclick="exportRawMaterials()" style="padding: 8px 16px; background: #217346; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 13px;">
                Export CSV
            </button>
        </div>

        <!-- Raw Material Table -->
        <table class="detail-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #217346; color: white;">
                    <th style="width: 50px; padding: 10px;">No.</th>
                    <th style="width: 100px; padding: 10px;">DB Code</th>
                    <th style="width: 80px; padding: 10px;">Cost Code</th>
                    <th style="padding: 10px;">Description</th>
                    <th style="width: 60px; padding: 10px;">Unit</th>
                    <th style="width: 90px; text-align: right; padding: 10px;">Quantity</th>
                    <th style="width: 90px; text-align: right; padding: 10px;">Unit Wt</th>
                    <th style="width: 100px; text-align: right; padding: 10px;">Total Weight</th>
                    <th style="width: 80px; padding: 10px;">Source</th>
                </tr>
            </thead>
            <tbody id="rawmat-body">
                <tr>
                    <td colspan="9" style="text-align: center; padding: 40px; color: #666;">
                        No data available. Please calculate from the Input tab first.
                    </td>
                </tr>
            </tbody>
            <tfoot id="rawmat-footer" style="display: none;">
                <tr style="background: #f0f0f0; font-weight: bold;">
                    <td colspan="7" style="text-align: right; padding: 12px;">TOTAL WEIGHT:</td>
                    <td id="rm-total-weight" style="text-align: right; padding: 12px; font-size: 14px;">-</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <!-- Material Summary by Category -->
        <div style="margin-top: 30px;">
            <h3 style="color: #217346; border-bottom: 2px solid #217346; padding-bottom: 5px; margin-bottom: 15px;">
                Material Breakdown by Category
            </h3>
            <div id="category-summary" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px;">
                <!-- Will be populated by JavaScript -->
            </div>
        </div>

        <!-- Weight Distribution Chart (simple bar representation) -->
        <div style="margin-top: 30px;">
            <h3 style="color: #217346; border-bottom: 2px solid #217346; padding-bottom: 5px; margin-bottom: 15px;">
                Weight Distribution
            </h3>
            <div id="weight-distribution" style="background: #f8f8f8; padding: 20px; border-radius: 4px;">
                <!-- Will be populated by JavaScript -->
            </div>
        </div>

    </div>
</div>

<script>
// Store aggregated data globally for filtering/export
let rawMaterialsData = [];
let totalItemCount = 0;

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('rm-date').textContent = new Date().toLocaleDateString();

    const data = window.quickEstData;
    if (!data || !data.items || data.items.length === 0) {
        return;
    }

    // Show summary cards
    document.getElementById('rm-summary-cards').style.display = 'block';

    // Project info
    const projectInfo = window.quickEstInput || {};
    document.getElementById('rm-project').textContent = projectInfo.projectName || '-';
    document.getElementById('rm-building').textContent = projectInfo.buildingName || '-';

    // Count total items before aggregation
    totalItemCount = data.items.filter(i => !i.isHeader && !i.isSeparator && i.dbCode).length;

    // Aggregate items by code (consolidate duplicates)
    const aggregated = {};
    data.items.forEach(item => {
        if (item.isHeader || item.isSeparator || !item.dbCode) return;

        const key = item.dbCode;
        if (!aggregated[key]) {
            aggregated[key] = {
                code: item.dbCode,
                costCode: item.costCode || '',
                description: item.description,
                unit: item.unit,
                unitWeight: parseFloat(item.unitWeight) || 0,
                totalUnits: 0,
                totalWeight: 0,
                sources: new Set(),
                category: categorizeItem(item.dbCode)
            };
        }
        aggregated[key].totalUnits += parseFloat(item.quantity) || 0;
        aggregated[key].totalWeight += parseFloat(item.totalWeight) || 0;
        if (item.source) aggregated[key].sources.add(item.source);
    });

    // Convert to array and sort by category then code
    rawMaterialsData = Object.values(aggregated).map(item => ({
        ...item,
        sources: Array.from(item.sources).join(', ')
    })).sort((a, b) => {
        if (a.category !== b.category) return a.category.localeCompare(b.category);
        return a.code.localeCompare(b.code);
    });

    // Populate category filter
    const categoryFilter = document.getElementById('rm-category-filter');
    const uniqueCategories = [...new Set(rawMaterialsData.map(i => i.category))];
    uniqueCategories.forEach(cat => {
        const option = document.createElement('option');
        option.value = cat;
        option.textContent = cat;
        categoryFilter.appendChild(option);
    });

    // Render table
    renderRawMaterialsTable(rawMaterialsData);

    // Calculate totals for summary cards
    const totalWeight = rawMaterialsData.reduce((sum, i) => sum + i.totalWeight, 0);
    document.getElementById('rm-total-items').textContent = totalItemCount;
    document.getElementById('rm-unique-count').textContent = rawMaterialsData.length;
    document.getElementById('rm-weight-summary').textContent = totalWeight.toLocaleString('en-US', {maximumFractionDigits: 0}) + ' kg';
    document.getElementById('rm-category-count').textContent = uniqueCategories.length;

    // Build category summary
    buildCategorySummary(rawMaterialsData);

    // Build weight distribution
    buildWeightDistribution(rawMaterialsData);

    // Setup search and filter
    document.getElementById('rm-search').addEventListener('input', filterTable);
    document.getElementById('rm-category-filter').addEventListener('change', filterTable);

    // Update status bar
    document.getElementById('status-message').textContent =
        `Raw Materials | ${rawMaterialsData.length} unique items | ${totalWeight.toLocaleString('en-US', {maximumFractionDigits: 0})} kg total`;
});

function categorizeItem(code) {
    code = (code || '').toUpperCase();

    if (code.startsWith('BU') || code.startsWith('HR') || code.startsWith('CON') || code.startsWith('PL')) {
        return 'Primary Steel';
    }
    if (code.startsWith('Z') || code.startsWith('C') || code.includes('PURLIN') || code.includes('GIRT') || code.startsWith('EAV') || code.startsWith('BASE')) {
        return 'Secondary Steel';
    }
    if (code.match(/^S[57]/) || code.match(/^A[57]/) || code.includes('CORE') || code.includes('SHEET')) {
        return 'Roof/Wall Sheeting';
    }
    if (code.startsWith('HSB') || code.startsWith('AB') || code.includes('BOLT') || code.match(/^[CS]S[24]/)) {
        return 'Fasteners & Bolts';
    }
    if (code.includes('TRIM') || code.includes('FLASH') || code.startsWith('RC') || code.startsWith('WC')) {
        return 'Trim & Flashing';
    }
    if (code.includes('DOOR') || code.includes('WINDOW') || code.includes('LOUVER')) {
        return 'Doors & Windows';
    }
    if (code.includes('GUTTER') || code.includes('DS') || code.includes('DOWNSPOUT')) {
        return 'Gutters & Downspouts';
    }
    if (code.includes('CRANE') || code.includes('RUNWAY') || code.startsWith('CR')) {
        return 'Crane Components';
    }
    if (code.includes('MEZZ') || code.startsWith('MZ')) {
        return 'Mezzanine';
    }
    if (code.includes('LINER') || code.includes('PU')) {
        return 'Liner Panels';
    }
    return 'Other Components';
}

function renderRawMaterialsTable(items) {
    const tbody = document.getElementById('rawmat-body');
    tbody.innerHTML = '';

    if (items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 40px; color: #666;">No matching materials found.</td></tr>';
        document.getElementById('rawmat-footer').style.display = 'none';
        return;
    }

    let totalWeight = 0;
    let currentCategory = '';
    let rowNum = 0;

    items.forEach(item => {
        // Category header if changed
        if (item.category !== currentCategory) {
            currentCategory = item.category;
            const headerRow = document.createElement('tr');
            headerRow.className = 'category-header';
            headerRow.innerHTML = `
                <td colspan="9" style="background: #e8f5e9; color: #217346; font-weight: bold; padding: 10px; font-size: 12px; border-left: 4px solid #217346;">
                    ${currentCategory}
                </td>
            `;
            tbody.appendChild(headerRow);
        }

        rowNum++;
        totalWeight += item.totalWeight;

        const tr = document.createElement('tr');
        tr.className = 'material-row';
        tr.innerHTML = `
            <td style="padding: 8px;">${rowNum}</td>
            <td style="padding: 8px; font-family: monospace; font-weight: 500;">${item.code}</td>
            <td style="padding: 8px;">${item.costCode}</td>
            <td style="padding: 8px;">${item.description}</td>
            <td style="padding: 8px; text-align: center;">${item.unit}</td>
            <td style="padding: 8px; text-align: right;">${item.totalUnits.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            <td style="padding: 8px; text-align: right;">${item.unitWeight.toFixed(4)}</td>
            <td style="padding: 8px; text-align: right; font-weight: 500;">${item.totalWeight.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            <td style="padding: 8px; font-size: 11px; color: #666;">${item.sources}</td>
        `;
        tbody.appendChild(tr);
    });

    // Show footer
    document.getElementById('rawmat-footer').style.display = '';
    document.getElementById('rm-total-weight').textContent = totalWeight.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' kg';
}

function filterTable() {
    const searchTerm = document.getElementById('rm-search').value.toLowerCase();
    const categoryFilter = document.getElementById('rm-category-filter').value;

    const filtered = rawMaterialsData.filter(item => {
        const matchesSearch = !searchTerm ||
            item.code.toLowerCase().includes(searchTerm) ||
            item.description.toLowerCase().includes(searchTerm) ||
            item.costCode.toLowerCase().includes(searchTerm);

        const matchesCategory = categoryFilter === 'all' || item.category === categoryFilter;

        return matchesSearch && matchesCategory;
    });

    renderRawMaterialsTable(filtered);
}

function buildCategorySummary(items) {
    const categories = {};
    let totalWeight = 0;

    items.forEach(item => {
        if (!categories[item.category]) {
            categories[item.category] = { weight: 0, count: 0 };
        }
        categories[item.category].weight += item.totalWeight;
        categories[item.category].count++;
        totalWeight += item.totalWeight;
    });

    const summaryDiv = document.getElementById('category-summary');
    summaryDiv.innerHTML = '';

    const colors = ['#217346', '#2196f3', '#ff9800', '#9c27b0', '#e91e63', '#00bcd4', '#795548', '#607d8b'];
    let colorIdx = 0;

    Object.entries(categories)
        .sort((a, b) => b[1].weight - a[1].weight)
        .forEach(([cat, data]) => {
            const pct = totalWeight > 0 ? (data.weight / totalWeight * 100).toFixed(1) : 0;
            const color = colors[colorIdx % colors.length];
            colorIdx++;

            summaryDiv.innerHTML += `
                <div style="background: white; padding: 15px; border-left: 4px solid ${color}; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div style="font-size: 12px; color: #666; margin-bottom: 5px;">${cat}</div>
                    <div style="font-size: 22px; font-weight: bold; color: #333;">${data.weight.toLocaleString('en-US', {maximumFractionDigits: 0})} kg</div>
                    <div style="font-size: 11px; color: #888;">${data.count} items | ${pct}%</div>
                </div>
            `;
        });
}

function buildWeightDistribution(items) {
    const categories = {};
    let totalWeight = 0;

    items.forEach(item => {
        if (!categories[item.category]) {
            categories[item.category] = 0;
        }
        categories[item.category] += item.totalWeight;
        totalWeight += item.totalWeight;
    });

    const distDiv = document.getElementById('weight-distribution');
    distDiv.innerHTML = '';

    const colors = ['#217346', '#2196f3', '#ff9800', '#9c27b0', '#e91e63', '#00bcd4', '#795548', '#607d8b'];
    let colorIdx = 0;

    Object.entries(categories)
        .sort((a, b) => b[1] - a[1])
        .forEach(([cat, weight]) => {
            const pct = totalWeight > 0 ? (weight / totalWeight * 100) : 0;
            const color = colors[colorIdx % colors.length];
            colorIdx++;

            distDiv.innerHTML += `
                <div style="margin-bottom: 12px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span style="font-size: 12px; color: #333;">${cat}</span>
                        <span style="font-size: 12px; font-weight: 500;">${weight.toLocaleString('en-US', {maximumFractionDigits: 0})} kg (${pct.toFixed(1)}%)</span>
                    </div>
                    <div style="background: #e0e0e0; height: 20px; border-radius: 4px; overflow: hidden;">
                        <div style="background: ${color}; height: 100%; width: ${pct}%; transition: width 0.3s;"></div>
                    </div>
                </div>
            `;
        });
}

function exportRawMaterials() {
    if (rawMaterialsData.length === 0) {
        alert('No data to export');
        return;
    }

    // Build CSV content
    const headers = ['No.', 'DB Code', 'Cost Code', 'Description', 'Unit', 'Quantity', 'Unit Weight', 'Total Weight', 'Category', 'Sources'];
    const rows = rawMaterialsData.map((item, idx) => [
        idx + 1,
        item.code,
        item.costCode,
        '"' + (item.description || '').replace(/"/g, '""') + '"',
        item.unit,
        item.totalUnits.toFixed(2),
        item.unitWeight.toFixed(4),
        item.totalWeight.toFixed(2),
        item.category,
        '"' + item.sources + '"'
    ]);

    let csv = '\uFEFF'; // UTF-8 BOM
    csv += headers.join(',') + '\n';
    rows.forEach(row => {
        csv += row.join(',') + '\n';
    });

    // Download
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'raw_materials_' + new Date().toISOString().slice(0, 10) + '.csv';
    a.click();
    URL.revokeObjectURL(url);
}
</script>
