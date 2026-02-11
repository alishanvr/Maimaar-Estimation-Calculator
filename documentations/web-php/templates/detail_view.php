<?php
/**
 * QuickEst - Detail View Template
 *
 * Excel-like BOM detail view using Handsontable
 * Replicates the Detail sheet layout with full pricing
 */
?>

<div class="spreadsheet-container">
    <!-- Summary Bar -->
    <div id="detail-summary-bar" class="detail-summary-bar" style="display: none;">
        <div class="summary-stats">
            <div class="stat-item">
                <span class="stat-label">Items:</span>
                <span class="stat-value" id="detail-item-count">0</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Total Weight:</span>
                <span class="stat-value" id="detail-total-weight">0 kg</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Material Cost:</span>
                <span class="stat-value" id="detail-material-cost">0 AED</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Total Price:</span>
                <span class="stat-value highlight" id="detail-total-price">0 AED</span>
            </div>
        </div>
        <div class="summary-actions">
            <button class="btn btn-sm" onclick="filterBySource('all')">All Items</button>
            <button class="btn btn-sm" onclick="filterBySource('Building')">Building</button>
            <button class="btn btn-sm" onclick="filterBySource('Mezzanine')">Mezzanine</button>
            <button class="btn btn-sm" onclick="filterBySource('Crane')">Crane</button>
            <button class="btn btn-sm" onclick="filterBySource('Liner')">Liner</button>
        </div>
    </div>

    <!-- Handsontable container for Detail view -->
    <div id="detail-grid" style="width: 100%; height: calc(100vh - 200px); overflow: hidden;"></div>
</div>

<style>
.detail-summary-bar {
    background: linear-gradient(to bottom, #f8f9fa 0%, #e9ecef 100%);
    padding: 10px 16px;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.summary-stats {
    display: flex;
    gap: 24px;
}

.stat-item {
    display: flex;
    align-items: baseline;
    gap: 6px;
}

.stat-label {
    font-size: 11px;
    color: #666;
    text-transform: uppercase;
}

.stat-value {
    font-size: 14px;
    font-weight: 600;
    color: #333;
}

.stat-value.highlight {
    color: #217346;
    font-size: 16px;
}

.summary-actions {
    display: flex;
    gap: 6px;
}

.btn-sm {
    padding: 4px 10px;
    font-size: 11px;
    border: 1px solid #ccc;
    background: white;
    border-radius: 3px;
    cursor: pointer;
}

.btn-sm:hover {
    background: #e9ecef;
}

.btn-sm.active {
    background: #217346;
    color: white;
    border-color: #217346;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check if we have calculated data
    const bomData = window.quickEstData?.items || [];

    if (bomData.length === 0) {
        document.getElementById('detail-grid').innerHTML =
            '<div style="padding: 40px; text-align: center; color: #666;">' +
            '<h3>No Data Available</h3>' +
            '<p>Please go to the Input tab and click "Calculate" to generate the Bill of Materials.</p>' +
            '<p style="margin-top: 20px;">' +
            '<a href="?page=input" class="btn btn-primary">Go to Input</a>' +
            '</p>' +
            '</div>';
        return;
    }

    // Show summary bar
    document.getElementById('detail-summary-bar').style.display = 'flex';

    // Store original data for filtering
    window.originalBomData = [...bomData];

    // Transform BOM data for Handsontable
    const tableData = bomData.map((item, index) => {
        return [
            item.lineNumber,
            item.dbCode,
            item.salesCode,
            item.costCode,
            item.description,
            item.size || '',
            item.unit,
            item.quantity ? parseFloat(item.quantity) : '',
            item.unitWeight ? parseFloat(item.unitWeight) : '',
            item.totalWeight ? parseFloat(item.totalWeight) : '',
            item.materialCost ? parseFloat(item.materialCost) : '',
            item.manufacturingCost ? parseFloat(item.manufacturingCost) : '',
            item.unitPrice ? parseFloat(item.unitPrice) : '',
            item.totalPrice ? parseFloat(item.totalPrice) : '',
            item.phaseNumber || '',
            item.source || '' // Hidden column for filtering
        ];
    });

    // Create Handsontable instance
    const container = document.getElementById('detail-grid');
    const hot = new Handsontable(container, {
        data: tableData,
        colHeaders: [
            'No.', 'DB Code', 'Sales', 'Cost Code', 'Description',
            'Size', 'Unit', 'Qty', 'Unit Wt (kg)', 'Total Wt (kg)',
            'Mat. Cost', 'Mfg. Cost', 'Unit Price', 'Total Price', 'Phase', 'Source'
        ],
        columns: [
            { data: 0, type: 'numeric', width: 40, readOnly: true },
            { data: 1, type: 'text', width: 80 },
            { data: 2, type: 'numeric', width: 50 },
            { data: 3, type: 'text', width: 70 },
            { data: 4, type: 'text', width: 260 },
            { data: 5, type: 'text', width: 55 },
            { data: 6, type: 'text', width: 45 },
            { data: 7, type: 'numeric', width: 65, numericFormat: { pattern: '0.00' } },
            { data: 8, type: 'numeric', width: 70, numericFormat: { pattern: '0.0000' } },
            { data: 9, type: 'numeric', width: 80, numericFormat: { pattern: '#,##0.00' } },
            { data: 10, type: 'numeric', width: 80, numericFormat: { pattern: '#,##0.00' } },
            { data: 11, type: 'numeric', width: 75, numericFormat: { pattern: '#,##0.00' } },
            { data: 12, type: 'numeric', width: 80, numericFormat: { pattern: '#,##0.00' } },
            { data: 13, type: 'numeric', width: 95, numericFormat: { pattern: '#,##0.00' } },
            { data: 14, type: 'text', width: 90 },
            { data: 15, type: 'text', width: 80 } // Source column
        ],
        rowHeaders: true,
        stretchH: 'last',
        height: '100%',
        licenseKey: 'non-commercial-and-evaluation',
        contextMenu: true,
        copyPaste: true,
        manualColumnResize: true,
        manualRowResize: true,
        filters: true,
        dropdownMenu: true,
        fixedColumnsLeft: 1,
        fixedRowsTop: 0,
        hiddenColumns: {
            columns: [15], // Hide source column by default
            indicators: false
        },

        // Cell styling
        cells: function(row, col, prop) {
            const cellProperties = {};
            const rowData = bomData[row];

            if (rowData) {
                if (rowData.isHeader) {
                    cellProperties.className = 'header-row';
                    cellProperties.readOnly = true;
                } else if (rowData.isSeparator) {
                    cellProperties.className = 'separator-row';
                    cellProperties.readOnly = true;
                }

                // Right align numeric columns
                if (col >= 7 && col <= 13) {
                    cellProperties.className = (cellProperties.className || '') + ' htRight';
                }

                // Highlight total weight and total price columns
                if (col === 9) {
                    cellProperties.className = (cellProperties.className || '') + ' weight-column';
                }
                if (col === 13) {
                    cellProperties.className = (cellProperties.className || '') + ' price-column';
                }

                // Color code based on source
                if (rowData.source) {
                    const sourceColors = {
                        'Building': '#e8f5e9',
                        'Mezzanine': '#e3f2fd',
                        'Crane': '#ffebee',
                        'Partition': '#f3e5f5',
                        'Canopy': '#fff3e0',
                        'Monitor': '#e0f2f1',
                        'Liner': '#e8eaf6',
                        'Accessory': '#fce4ec'
                    };
                    if (sourceColors[rowData.source] && col === 4) {
                        cellProperties.renderer = function(instance, td, row, col, prop, value, cellProperties) {
                            Handsontable.renderers.TextRenderer.apply(this, arguments);
                            td.style.borderLeft = '3px solid ' + (sourceColors[rowData.source] || '#ccc');
                        };
                    }
                }
            }

            return cellProperties;
        },

        // After render, update summary
        afterRender: function() {
            updateDetailSummary();
        }
    });

    // Function to update summary
    function updateDetailSummary() {
        let totalWeight = 0;
        let totalPrice = 0;
        let materialCost = 0;
        let itemCount = 0;

        bomData.forEach(item => {
            if (!item.isHeader && !item.isSeparator) {
                totalWeight += parseFloat(item.totalWeight) || 0;
                totalPrice += parseFloat(item.totalPrice) || 0;
                materialCost += (parseFloat(item.materialCost) || 0) * (parseFloat(item.quantity) || 0);
                itemCount++;
            }
        });

        document.getElementById('detail-item-count').textContent = itemCount;
        document.getElementById('detail-total-weight').textContent = totalWeight.toLocaleString('en-US', {maximumFractionDigits: 2}) + ' kg';
        document.getElementById('detail-material-cost').textContent = materialCost.toLocaleString('en-US', {maximumFractionDigits: 2}) + ' AED';
        document.getElementById('detail-total-price').textContent = totalPrice.toLocaleString('en-US', {maximumFractionDigits: 2}) + ' AED';

        // Also update status bar
        document.getElementById('status-message').textContent =
            `Detail View | ${itemCount} items | ${totalWeight.toLocaleString('en-US', {maximumFractionDigits: 0})} kg | ${totalPrice.toLocaleString('en-US', {maximumFractionDigits: 0})} AED`;
    }

    // Initial summary update
    updateDetailSummary();

    // Export to global for other functions
    window.detailGrid = hot;
});

// Filter by source function
function filterBySource(source) {
    if (!window.originalBomData || !window.detailGrid) return;

    // Update button states
    document.querySelectorAll('.summary-actions .btn-sm').forEach(btn => {
        btn.classList.remove('active');
        if (btn.textContent.includes(source) || (source === 'all' && btn.textContent === 'All Items')) {
            btn.classList.add('active');
        }
    });

    // Filter data
    let filteredData;
    if (source === 'all') {
        filteredData = window.originalBomData;
    } else {
        filteredData = window.originalBomData.filter(item =>
            item.source === source || item.isHeader || item.isSeparator
        );
    }

    // Update global data
    window.quickEstData.items = filteredData;

    // Reload the page section (simple approach)
    location.reload();
}
</script>
