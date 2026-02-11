/**
 * QuickEst - Main Application JavaScript
 *
 * Handles form interactions, calculations, and data management
 */

// Global state - initialized in index.php head for early access by templates
// window.quickEstData and window.quickEstInput are set before this script loads
if (typeof window.quickEstData === 'undefined') window.quickEstData = null;
if (typeof window.quickEstInput === 'undefined') window.quickEstInput = null;

document.addEventListener('DOMContentLoaded', function() {
    console.log('QuickEst Application Initialized');

    // Initialize form handlers
    initFormHandlers();

    // Initialize button handlers
    initButtonHandlers();

    // Initialize auto-calculations
    initAutoCalculations();

    // Load saved data if available
    loadSavedData();
});

/**
 * Initialize form input handlers
 */
function initFormHandlers() {
    // Auto-calculate dimensions when spans/bays change
    const spansInput = document.getElementById('spans');
    const baysInput = document.getElementById('bays');

    if (spansInput) {
        spansInput.addEventListener('input', updateDimensionCalculations);
        spansInput.addEventListener('change', updateDimensionCalculations);
    }

    if (baysInput) {
        baysInput.addEventListener('input', updateDimensionCalculations);
        baysInput.addEventListener('change', updateDimensionCalculations);
    }

    // Wind load calculation
    const windSpeedInput = document.getElementById('wind-speed');
    if (windSpeedInput) {
        windSpeedInput.addEventListener('input', updateWindLoad);
    }

    // Slope and peak height calculation
    const slopesInput = document.getElementById('slopes');
    const backEaveInput = document.getElementById('back-eave-height');
    const frontEaveInput = document.getElementById('front-eave-height');

    [slopesInput, backEaveInput, frontEaveInput].forEach(input => {
        if (input) {
            input.addEventListener('input', updatePeakHeight);
        }
    });

    // Initial calculations
    setTimeout(() => {
        updateDimensionCalculations();
        updateWindLoad();
        updatePeakHeight();
    }, 100);
}

/**
 * Initialize button handlers
 */
function initButtonHandlers() {
    // Calculate button
    const calcBtn = document.getElementById('btn-calculate');
    if (calcBtn) {
        calcBtn.addEventListener('click', handleCalculate);
    }

    // Export button
    const exportBtn = document.getElementById('btn-export');
    if (exportBtn) {
        exportBtn.addEventListener('click', handleExport);
    }

    // New button
    const newBtn = document.getElementById('btn-new');
    if (newBtn) {
        newBtn.addEventListener('click', handleNew);
    }

    // Quick action buttons
    const addAreaBtn = document.getElementById('btn-add-area');
    if (addAreaBtn) {
        addAreaBtn.addEventListener('click', () => showSection('area'));
    }

    // Mezzanine buttons
    const addMezzBtn = document.getElementById('btn-add-mezzanine');
    if (addMezzBtn) {
        addMezzBtn.addEventListener('click', () => showSection('mezzanine'));
    }

    const addMezzItemBtn = document.getElementById('btn-add-mezzanine-item');
    if (addMezzItemBtn) {
        addMezzItemBtn.addEventListener('click', handleAddMezzanine);
    }

    const cancelMezzBtn = document.getElementById('btn-cancel-mezzanine');
    if (cancelMezzBtn) {
        cancelMezzBtn.addEventListener('click', () => hideSection('mezzanine'));
    }

    // Crane buttons
    const addCraneBtn = document.getElementById('btn-add-crane');
    if (addCraneBtn) {
        addCraneBtn.addEventListener('click', () => showSection('crane'));
    }

    const addCraneItemBtn = document.getElementById('btn-add-crane-item');
    if (addCraneItemBtn) {
        addCraneItemBtn.addEventListener('click', handleAddCrane);
    }

    const cancelCraneBtn = document.getElementById('btn-cancel-crane');
    if (cancelCraneBtn) {
        cancelCraneBtn.addEventListener('click', () => hideSection('crane'));
    }

    // Accessory buttons
    const addAccBtn = document.getElementById('btn-add-accessory');
    if (addAccBtn) {
        addAccBtn.addEventListener('click', () => showSection('accessory'));
    }

    const addAccItemBtn = document.getElementById('btn-add-accessory-item');
    if (addAccItemBtn) {
        addAccItemBtn.addEventListener('click', handleAddAccessory);
    }

    const cancelAccBtn = document.getElementById('btn-cancel-accessory');
    if (cancelAccBtn) {
        cancelAccBtn.addEventListener('click', () => hideSection('accessory'));
    }

    // Update mezzanine area calculation
    const mezColSpacing = document.getElementById('mez-col-spacing');
    const mezBeamSpacing = document.getElementById('mez-beam-spacing');
    if (mezColSpacing && mezBeamSpacing) {
        mezColSpacing.addEventListener('input', updateMezzanineArea);
        mezBeamSpacing.addEventListener('input', updateMezzanineArea);
    }

    // Partition buttons
    const addPartBtn = document.getElementById('btn-add-partition');
    if (addPartBtn) {
        addPartBtn.addEventListener('click', () => showSection('partition'));
    }

    const addPartItemBtn = document.getElementById('btn-add-partition-item');
    if (addPartItemBtn) {
        addPartItemBtn.addEventListener('click', handleAddPartition);
    }

    const cancelPartBtn = document.getElementById('btn-cancel-partition');
    if (cancelPartBtn) {
        cancelPartBtn.addEventListener('click', () => hideSection('partition'));
    }

    // Canopy buttons
    const addCanopyBtn = document.getElementById('btn-add-canopy');
    if (addCanopyBtn) {
        addCanopyBtn.addEventListener('click', () => showSection('canopy'));
    }

    const addCanopyItemBtn = document.getElementById('btn-add-canopy-item');
    if (addCanopyItemBtn) {
        addCanopyItemBtn.addEventListener('click', handleAddCanopy);
    }

    const cancelCanopyBtn = document.getElementById('btn-cancel-canopy');
    if (cancelCanopyBtn) {
        cancelCanopyBtn.addEventListener('click', () => hideSection('canopy'));
    }

    // Monitor buttons
    const addMonitorBtn = document.getElementById('btn-add-monitor');
    if (addMonitorBtn) {
        addMonitorBtn.addEventListener('click', () => showSection('monitor'));
    }

    const addMonitorItemBtn = document.getElementById('btn-add-monitor-item');
    if (addMonitorItemBtn) {
        addMonitorItemBtn.addEventListener('click', handleAddMonitor);
    }

    const cancelMonitorBtn = document.getElementById('btn-cancel-monitor');
    if (cancelMonitorBtn) {
        cancelMonitorBtn.addEventListener('click', () => hideSection('monitor'));
    }

    // Liner buttons
    const addLinerBtn = document.getElementById('btn-add-liner');
    if (addLinerBtn) {
        addLinerBtn.addEventListener('click', () => showSection('liner'));
    }

    const addLinerItemBtn = document.getElementById('btn-add-liner-item');
    if (addLinerItemBtn) {
        addLinerItemBtn.addEventListener('click', handleAddLiner);
    }

    const cancelLinerBtn = document.getElementById('btn-cancel-liner');
    if (cancelLinerBtn) {
        cancelLinerBtn.addEventListener('click', () => hideSection('liner'));
    }
}

/**
 * Initialize auto-calculations for form fields
 */
function initAutoCalculations() {
    // When roof/wall panel changes, update trim material
    const roofTopSkin = document.getElementById('roof-top-skin');
    const wallTopSkin = document.getElementById('wall-top-skin');
    const trimSizes = document.getElementById('trim-sizes');

    if (roofTopSkin && trimSizes) {
        roofTopSkin.addEventListener('change', function() {
            if (this.value.includes('A7')) {
                trimSizes.value = '0.7 AL';
            } else if (this.value.includes('S5')) {
                trimSizes.value = '0.5 AZ';
            } else if (this.value.includes('S7')) {
                trimSizes.value = '0.7 AZ';
            }
        });
    }
}

/**
 * Parse dimension string (e.g., "2@24" or "24+24")
 */
function parseList(text) {
    if (!text) return [];

    // Normalize separators
    text = text.replace(/[+;\/\\&]/g, ',');
    text = text.replace(/[xX:]/g, '@');

    const parts = text.split(',');
    const result = [];

    parts.forEach(part => {
        part = part.trim();
        if (!part) return;

        if (part.includes('@')) {
            const [count, value] = part.split('@');
            result.push({
                count: parseInt(count) || 1,
                value: parseFloat(value) || 0
            });
        } else {
            result.push({
                count: 1,
                value: parseFloat(part) || 0
            });
        }
    });

    return result;
}

/**
 * Calculate total from parsed list
 */
function calculateTotal(list) {
    return list.reduce((sum, item) => sum + (item.count * item.value), 0);
}

/**
 * Update dimension calculations
 */
function updateDimensionCalculations() {
    const spansText = document.getElementById('spans')?.value || '';
    const baysText = document.getElementById('bays')?.value || '';

    const spans = parseList(spansText);
    const bays = parseList(baysText);

    const width = calculateTotal(spans);
    const length = calculateTotal(bays);

    const calcWidth = document.getElementById('calc-width');
    const calcLength = document.getElementById('calc-length');

    if (calcWidth) calcWidth.value = width.toFixed(2);
    if (calcLength) calcLength.value = length.toFixed(2);

    // Also update peak height
    updatePeakHeight();
}

/**
 * Update wind load calculation
 */
function updateWindLoad() {
    const windSpeed = parseFloat(document.getElementById('wind-speed')?.value) || 0;
    const windLoad = Math.pow(windSpeed, 2) / 20000;

    const calcWindLoad = document.getElementById('calc-wind-load');
    if (calcWindLoad) {
        calcWindLoad.value = windLoad.toFixed(3);
    }
}

/**
 * Update peak height calculation
 */
function updatePeakHeight() {
    const spansText = document.getElementById('spans')?.value || '';
    const spans = parseList(spansText);
    const width = calculateTotal(spans);

    const slopeText = document.getElementById('slopes')?.value || '0.1';
    const slope = parseFloat(slopeText) || 0.1;

    const backEave = parseFloat(document.getElementById('back-eave-height')?.value) || 0;
    const frontEave = parseFloat(document.getElementById('front-eave-height')?.value) || 0;
    const maxEave = Math.max(backEave, frontEave);

    const peakHeight = maxEave + (width / 2 * slope);

    const calcPeakHeight = document.getElementById('calc-peak-height');
    if (calcPeakHeight) {
        calcPeakHeight.value = peakHeight.toFixed(2);
    }
}

/**
 * Collect all form data
 */
function collectFormData() {
    const getValue = (id) => document.getElementById(id)?.value || '';
    const getNumber = (id) => parseFloat(document.getElementById(id)?.value) || 0;

    return {
        // Project Info
        date: getValue('project-date'),
        projectName: getValue('project-name'),
        buildingName: getValue('building-name'),
        customerName: getValue('customer-name'),
        projectNumber: getValue('project-number'),
        buildingNumber: getValue('building-number'),
        revisionNumber: getValue('revision'),
        location: getValue('location'),
        estimatedBy: getValue('estimated-by'),

        // Dimensions
        spans: getValue('spans'),
        bays: getValue('bays'),
        slopes: getValue('slopes'),
        backEaveHeight: getNumber('back-eave-height'),
        frontEaveHeight: getNumber('front-eave-height'),

        // Structural
        frameType: getValue('frame-type'),
        baseType: getValue('base-type'),
        minThickness: getNumber('min-thickness'),
        doubleWelded: getValue('double-welded'),
        leftEndwallType: getValue('left-endwall-type'),
        rightEndwallType: getValue('right-endwall-type'),
        bracingType: getValue('bracing-type'),
        buFinish: getValue('bu-finish'),
        cfFinish: getValue('cf-finish'),
        purlinProfile: getValue('purlin-profile'),

        // Loads
        deadLoad: getNumber('dead-load'),
        liveLoadPurlin: getNumber('live-load-purlin'),
        liveLoadFrame: getNumber('live-load-frame'),
        additionalLoad: getNumber('additional-load'),
        windSpeed: getNumber('wind-speed'),

        // Eave Conditions
        backEaveCondition: getValue('back-eave-condition'),
        frontEaveCondition: getValue('front-eave-condition'),

        // Roof Sheeting
        roofPanelProfile: getValue('roof-panel-profile'),
        roofTopSkin: getValue('roof-top-skin'),
        roofCore: getValue('roof-core'),
        roofBotSkin: getValue('roof-bot-skin'),

        // Wall Sheeting
        wallTopSkin: getValue('wall-top-skin'),
        wallCore: getValue('wall-core'),
        wallBotSkin: getValue('wall-bot-skin'),
        trimSizes: getValue('trim-sizes'),

        // Freight
        freightDestination: getValue('freight-destination')
    };
}

/**
 * Handle Calculate button click
 */
async function handleCalculate() {
    console.log('Calculating...');

    // Show loading
    showLoading(true);
    updateStatus('Calculating...');

    try {
        const formData = collectFormData();

        // Debug: log the collected form data
        console.log('Form data collected:', {
            spans: formData.spans,
            bays: formData.bays,
            backEaveHeight: formData.backEaveHeight,
            frontEaveHeight: formData.frontEaveHeight,
            windSpeed: formData.windSpeed
        });

        window.quickEstInput = formData;

        // Send to server for calculation
        const response = await fetch('?action=calculate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });

        const result = await response.json();

        if (result.success) {
            window.quickEstData = result;

            // Save to session storage
            sessionStorage.setItem('quickEstData', JSON.stringify(result));
            sessionStorage.setItem('quickEstInput', JSON.stringify(formData));

            // Update summary panel
            updateSummaryPanel(result);

            // Update status
            updateStatus(`Calculation complete: ${result.summary.itemCount} items, ${result.summary.totalWeight.toFixed(0)} kg`);

            // Show success message
            showMessage('Calculation completed successfully!', 'success');
        } else {
            console.error('Calculation errors:', result.errors);
            showMessage('Calculation failed: ' + (result.errors?.join(', ') || result.error), 'error');
            updateStatus('Calculation failed');
        }
    } catch (error) {
        console.error('Calculation error:', error);
        showMessage('Error during calculation: ' + error.message, 'error');
        updateStatus('Error');
    } finally {
        showLoading(false);
    }
}

/**
 * Handle Export button click - shows export options
 */
function handleExport() {
    if (!window.quickEstData) {
        showMessage('No data to export. Please calculate first.', 'warning');
        return;
    }

    // Show export format dialog
    showExportDialog();
}

/**
 * Show export format selection dialog
 */
function showExportDialog() {
    // Remove existing dialog if any
    const existing = document.getElementById('export-dialog');
    if (existing) existing.remove();

    const dialog = document.createElement('div');
    dialog.id = 'export-dialog';
    dialog.className = 'modal-overlay';
    dialog.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-header">
                <h3>Export Bill of Materials</h3>
                <button class="modal-close" onclick="closeExportDialog()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Select export format:</p>
                <div class="export-options">
                    <button class="export-btn" onclick="doExport('csv')">
                        <span class="export-icon">CSV</span>
                        <span class="export-label">CSV File</span>
                        <span class="export-desc">Comma-separated values</span>
                    </button>
                    <button class="export-btn" onclick="doExport('xlsx')">
                        <span class="export-icon">XLS</span>
                        <span class="export-label">Excel File</span>
                        <span class="export-desc">XLS format for Excel</span>
                    </button>
                    <button class="export-btn" onclick="doExport('json')">
                        <span class="export-icon">{ }</span>
                        <span class="export-label">JSON File</span>
                        <span class="export-desc">For API/integration</span>
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeExportDialog()">Cancel</button>
            </div>
        </div>
    `;
    document.body.appendChild(dialog);
}

/**
 * Close export dialog
 */
function closeExportDialog() {
    const dialog = document.getElementById('export-dialog');
    if (dialog) dialog.remove();
}

/**
 * Perform export in specified format
 */
async function doExport(format) {
    closeExportDialog();
    showLoading(true);
    updateStatus(`Exporting as ${format.toUpperCase()}...`);

    try {
        // Gather project info
        const projectInfo = {
            projectName: document.getElementById('project-name')?.value || '',
            projectNumber: document.getElementById('project-number')?.value || 'HQ-O-00000',
            buildingName: document.getElementById('building-name')?.value || '',
            buildingNumber: document.getElementById('building-number')?.value || '1',
            customerName: document.getElementById('customer-name')?.value || '',
            location: document.getElementById('location')?.value || '',
            estimatedBy: document.getElementById('estimated-by')?.value || '',
            date: document.getElementById('project-date')?.value || new Date().toISOString().split('T')[0]
        };

        const exportData = {
            projectInfo: projectInfo,
            items: window.quickEstData.items || [],
            summary: window.quickEstData.summary || {},
            dimensions: window.quickEstData.dimensions || {},
            loads: window.quickEstData.loads || {}
        };

        const response = await fetch(`?action=export&format=${format}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(exportData)
        });

        if (!response.ok) {
            throw new Error('Export failed');
        }

        // Get the blob and download
        const blob = await response.blob();
        const contentDisposition = response.headers.get('Content-Disposition');
        let filename = `QuickEst_Export.${format === 'xlsx' ? 'xls' : format}`;

        if (contentDisposition) {
            const match = contentDisposition.match(/filename="(.+)"/);
            if (match) filename = match[1];
        }

        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);

        showMessage(`Export to ${format.toUpperCase()} completed!`, 'success');
        updateStatus('Export complete');
    } catch (error) {
        console.error('Export error:', error);
        showMessage('Export failed: ' + error.message, 'error');
        updateStatus('Export failed');
    } finally {
        showLoading(false);
    }
}

/**
 * Handle New button click
 */
function handleNew() {
    if (window.quickEstData) {
        if (!confirm('This will clear all current data. Are you sure?')) {
            return;
        }
    }

    // Clear data
    window.quickEstData = null;
    window.quickEstInput = null;
    sessionStorage.removeItem('quickEstData');
    sessionStorage.removeItem('quickEstInput');

    // Reset form
    const form = document.getElementById('input-form');
    if (form) {
        const inputs = form.querySelectorAll('input:not([type="date"]), select');
        inputs.forEach(input => {
            if (input.dataset.default) {
                input.value = input.dataset.default;
            }
        });
    }

    // Hide summary
    const summaryPanel = document.getElementById('summary-panel');
    if (summaryPanel) {
        summaryPanel.style.display = 'none';
    }

    updateStatus('Ready');
    showMessage('New estimate started', 'info');
}

/**
 * Update summary panel
 */
function updateSummaryPanel(data) {
    const panel = document.getElementById('summary-panel');
    if (!panel) return;

    panel.style.display = 'block';

    const dims = data.dimensions || {};
    const summary = data.summary || {};

    document.getElementById('sum-area').textContent =
        ((dims.width || 0) * (dims.length || 0)).toFixed(0) + ' m²';
    document.getElementById('sum-roof').textContent =
        (data.dimensions?.rafterLength * 2 * dims.length || 0).toFixed(0) + ' m²';
    document.getElementById('sum-wall').textContent =
        ((dims.backEaveHeight + dims.frontEaveHeight) * dims.length || 0).toFixed(0) + ' m²';
    document.getElementById('sum-weight').textContent =
        (summary.totalWeight || 0).toFixed(0) + ' kg';
    document.getElementById('sum-items').textContent = summary.itemCount || 0;
    document.getElementById('sum-price').textContent =
        (summary.totalPrice || 0).toLocaleString('en-AE', { style: 'currency', currency: 'AED' });
}

/**
 * Show/hide loading overlay
 */
function showLoading(show) {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
        overlay.classList.toggle('visible', show);
    }
}

/**
 * Update status bar message
 */
function updateStatus(message) {
    const statusEl = document.getElementById('status-message');
    if (statusEl) {
        statusEl.textContent = message;
    }
}

/**
 * Show a message to the user
 */
function showMessage(message, type = 'info') {
    // Simple alert for now, could be replaced with toast notifications
    console.log(`[${type.toUpperCase()}] ${message}`);

    if (type === 'error') {
        alert('Error: ' + message);
    }
}

/**
 * Load saved data from session storage and restore UI
 * Note: Data is already loaded synchronously at script start, this restores form values
 */
function loadSavedData() {
    try {
        // Data is already loaded into window.quickEstData and window.quickEstInput
        // This function now just restores form values and updates UI

        if (window.quickEstInput) {
            // Restore form values
            const input = window.quickEstInput;
            Object.keys(input).forEach(key => {
                const element = document.getElementById(key) ||
                               document.getElementById(key.replace(/([A-Z])/g, '-$1').toLowerCase());
                if (element) {
                    element.value = input[key];
                }
            });
        }

        if (window.quickEstData) {
            updateSummaryPanel(window.quickEstData);
            updateStatus('Data loaded from previous session');
            console.log('UI updated with saved data');
        }
    } catch (e) {
        console.log('Error restoring UI from saved data:', e);
    }
}

/**
 * Show a specific section
 */
function showSection(section) {
    // Update active button
    document.querySelectorAll('.quick-actions button').forEach(btn => {
        btn.classList.remove('active');
    });

    const activeBtn = document.getElementById(`btn-add-${section}`);
    if (activeBtn) {
        activeBtn.classList.add('active');
    }

    // Hide all additional sections first
    document.querySelectorAll('.additional-section').forEach(sec => {
        sec.style.display = 'none';
    });

    // Show the requested section
    const sectionEl = document.getElementById(`${section}-section`);
    if (sectionEl) {
        sectionEl.style.display = 'block';
        sectionEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    console.log('Showing section:', section);
}

/**
 * Hide a specific section
 */
function hideSection(section) {
    const sectionEl = document.getElementById(`${section}-section`);
    if (sectionEl) {
        sectionEl.style.display = 'none';
    }

    // Reactivate area button
    document.querySelectorAll('.quick-actions button').forEach(btn => {
        btn.classList.remove('active');
    });
    const areaBtn = document.getElementById('btn-add-area');
    if (areaBtn) {
        areaBtn.classList.add('active');
    }
}

/**
 * Update mezzanine calculated area
 */
function updateMezzanineArea() {
    const colSpText = document.getElementById('mez-col-spacing')?.value || '';
    const beamSpText = document.getElementById('mez-beam-spacing')?.value || '';

    const colSpacing = parseList(colSpText);
    const beamSpacing = parseList(beamSpText);

    const width = calculateTotal(colSpacing);
    const length = calculateTotal(beamSpacing);
    const area = width * length;

    const calcArea = document.getElementById('mez-calc-area');
    if (calcArea) {
        calcArea.value = `${width.toFixed(1)}m x ${length.toFixed(1)}m = ${area.toFixed(1)} m²`;
    }
}

/**
 * Handle Add Mezzanine button click
 */
async function handleAddMezzanine() {
    console.log('Adding mezzanine...');
    showLoading(true);
    updateStatus('Adding mezzanine...');

    try {
        const mezzanineData = {
            description: document.getElementById('mez-description')?.value || 'Mezzanine',
            salesCode: 1,
            colSpacing: document.getElementById('mez-col-spacing')?.value || '2@6',
            beamSpacing: document.getElementById('mez-beam-spacing')?.value || '3@4',
            joistSpacing: document.getElementById('mez-joist-spacing')?.value || '1@1.5',
            clearHeight: parseFloat(document.getElementById('mez-clear-height')?.value) || 3.0,
            doubleWelded: document.getElementById('mez-double-welded')?.value || 'No',
            deckType: document.getElementById('mez-deck-type')?.value || 'Deck-0.75',
            nStairs: parseInt(document.getElementById('mez-stairs')?.value) || 1,
            deadLoad: parseFloat(document.getElementById('mez-dead-load')?.value) || 0.5,
            liveLoad: parseFloat(document.getElementById('mez-live-load')?.value) || 5.0,
            additionalLoad: parseFloat(document.getElementById('mez-additional-load')?.value) || 0,
            minThickness: parseFloat(document.getElementById('mez-min-thickness')?.value) || 6
        };

        const response = await fetch('?action=calculate-mezzanine', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(mezzanineData)
        });

        const result = await response.json();

        if (result.success) {
            // Merge with existing data
            mergeCalculationResult(result, 'Mezzanine');
            hideSection('mezzanine');
            showMessage('Mezzanine added successfully!', 'success');
            updateStatus(`Mezzanine added: ${result.summary.itemCount} items, ${result.summary.totalWeight.toFixed(0)} kg`);
        } else {
            showMessage('Failed to add mezzanine: ' + (result.error || 'Unknown error'), 'error');
            updateStatus('Mezzanine calculation failed');
        }
    } catch (error) {
        console.error('Mezzanine error:', error);
        showMessage('Error adding mezzanine: ' + error.message, 'error');
        updateStatus('Error');
    } finally {
        showLoading(false);
    }
}

/**
 * Handle Add Crane button click
 */
async function handleAddCrane() {
    console.log('Adding crane...');
    showLoading(true);
    updateStatus('Adding crane runway...');

    try {
        const craneData = {
            description: document.getElementById('crane-description')?.value || 'EOT Crane',
            salesCode: 1,
            capacity: parseFloat(document.getElementById('crane-capacity')?.value) || 5,
            duty: document.getElementById('crane-duty')?.value || 'M',
            railCenters: parseFloat(document.getElementById('crane-rail-centers')?.value) || 18,
            craneRun: document.getElementById('crane-run')?.value || '6@6'
        };

        const response = await fetch('?action=calculate-crane', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(craneData)
        });

        const result = await response.json();

        if (result.success) {
            mergeCalculationResult(result, 'Crane');
            hideSection('crane');
            showMessage('Crane runway added successfully!', 'success');
            updateStatus(`Crane added: ${result.summary.itemCount} items, ${result.summary.totalWeight.toFixed(0)} kg`);
        } else {
            showMessage('Failed to add crane: ' + (result.error || 'Unknown error'), 'error');
            updateStatus('Crane calculation failed');
        }
    } catch (error) {
        console.error('Crane error:', error);
        showMessage('Error adding crane: ' + error.message, 'error');
        updateStatus('Error');
    } finally {
        showLoading(false);
    }
}

/**
 * Handle Add Accessory button click
 */
async function handleAddAccessory() {
    console.log('Adding accessories...');
    showLoading(true);
    updateStatus('Adding accessories...');

    try {
        // Collect accessory items
        const items = [];
        for (let i = 1; i <= 5; i++) {
            const itemDesc = document.getElementById(`acc-item-${i}`)?.value || '';
            const itemQty = parseInt(document.getElementById(`acc-qty-${i}`)?.value) || 0;
            if (itemDesc && itemQty > 0) {
                items.push({ description: itemDesc, quantity: itemQty });
            }
        }

        if (items.length === 0) {
            showMessage('Please select at least one accessory item', 'warning');
            showLoading(false);
            return;
        }

        const accessoryData = {
            description: document.getElementById('acc-description')?.value || 'Accessories',
            salesCode: 1,
            items: items,
            wallTopSkin: document.getElementById('wall-top-skin')?.value || 'S5OW',
            wallCore: document.getElementById('wall-core')?.value || '-',
            wallBotSkin: document.getElementById('wall-bot-skin')?.value || '-'
        };

        const response = await fetch('?action=calculate-accessory', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(accessoryData)
        });

        const result = await response.json();

        if (result.success) {
            mergeCalculationResult(result, 'Accessory');
            hideSection('accessory');
            showMessage('Accessories added successfully!', 'success');
            updateStatus(`Accessories added: ${result.summary.itemCount} items, ${result.summary.totalWeight.toFixed(0)} kg`);
        } else {
            showMessage('Failed to add accessories: ' + (result.error || 'Unknown error'), 'error');
            updateStatus('Accessory calculation failed');
        }
    } catch (error) {
        console.error('Accessory error:', error);
        showMessage('Error adding accessories: ' + error.message, 'error');
        updateStatus('Error');
    } finally {
        showLoading(false);
    }
}

/**
 * Merge calculation result with existing data
 */
function mergeCalculationResult(result, source) {
    if (!window.quickEstData) {
        window.quickEstData = {
            items: [],
            summary: { totalWeight: 0, totalPrice: 0, itemCount: 0 }
        };
    }

    // Add source tag to items
    const taggedItems = result.items.map(item => ({
        ...item,
        source: source
    }));

    // Append items
    window.quickEstData.items = [...window.quickEstData.items, ...taggedItems];

    // Update summary
    window.quickEstData.summary.totalWeight += result.summary.totalWeight;
    window.quickEstData.summary.totalPrice += result.summary.totalPrice;
    window.quickEstData.summary.itemCount += result.summary.itemCount;

    // Save to session storage
    sessionStorage.setItem('quickEstData', JSON.stringify(window.quickEstData));

    // Update UI
    updateSummaryPanel(window.quickEstData);
}

/**
 * Handle Add Partition button click
 */
async function handleAddPartition() {
    console.log('Adding partition...');
    showLoading(true);
    updateStatus('Adding partition...');

    try {
        const partitionData = {
            description: document.getElementById('part-description')?.value || 'Internal Partition',
            salesCode: 1,
            location: document.getElementById('part-location')?.value || 'Internal',
            length: document.getElementById('part-length')?.value || '6@6',
            height: parseFloat(document.getElementById('part-height')?.value) || 6.0,
            girtSpacing: parseFloat(document.getElementById('part-girt-spacing')?.value) || 1.5,
            columnSpacing: parseFloat(document.getElementById('part-col-spacing')?.value) || 6.0,
            windLoad: parseFloat(document.getElementById('calc-wind-load')?.value) || 0.5,
            topSkin: document.getElementById('part-top-skin')?.value || 'S5OW',
            core: document.getElementById('part-core')?.value || '-',
            botSkin: document.getElementById('part-bot-skin')?.value || '-'
        };

        const response = await fetch('?action=calculate-partition', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(partitionData)
        });

        const result = await response.json();

        if (result.success) {
            mergeCalculationResult(result, 'Partition');
            hideSection('partition');
            showMessage('Partition added successfully!', 'success');
            updateStatus(`Partition added: ${result.summary.itemCount} items, ${result.summary.totalWeight.toFixed(0)} kg`);
        } else {
            showMessage('Failed to add partition: ' + (result.error || 'Unknown error'), 'error');
            updateStatus('Partition calculation failed');
        }
    } catch (error) {
        console.error('Partition error:', error);
        showMessage('Error adding partition: ' + error.message, 'error');
        updateStatus('Error');
    } finally {
        showLoading(false);
    }
}

/**
 * Handle Add Canopy button click
 */
async function handleAddCanopy() {
    console.log('Adding canopy...');
    showLoading(true);
    updateStatus('Adding canopy...');

    try {
        const canopyData = {
            description: document.getElementById('canopy-description')?.value || 'Canopy',
            salesCode: 1,
            type: document.getElementById('canopy-type')?.value || 'Canopy',
            location: document.getElementById('canopy-location')?.value || 'Front',
            width: parseFloat(document.getElementById('canopy-width')?.value) || 3.0,
            length: document.getElementById('canopy-length')?.value || '6@6',
            eaveHeight: parseFloat(document.getElementById('canopy-eave-height')?.value) || 6.0,
            slope: parseFloat(document.getElementById('canopy-slope')?.value) || 0.1,
            deadLoad: parseFloat(document.getElementById('canopy-dead-load')?.value) || 0.1,
            liveLoad: parseFloat(document.getElementById('canopy-live-load')?.value) || 0.5,
            windLoad: parseFloat(document.getElementById('calc-wind-load')?.value) || 0.5,
            roofTopSkin: document.getElementById('canopy-roof-skin')?.value || 'S5OW',
            roofCore: document.getElementById('canopy-roof-core')?.value || '-',
            roofBotSkin: document.getElementById('canopy-roof-bot-skin')?.value || '-'
        };

        const response = await fetch('?action=calculate-canopy', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(canopyData)
        });

        const result = await response.json();

        if (result.success) {
            mergeCalculationResult(result, 'Canopy');
            hideSection('canopy');
            showMessage('Canopy added successfully!', 'success');
            updateStatus(`Canopy added: ${result.summary.itemCount} items, ${result.summary.totalWeight.toFixed(0)} kg`);
        } else {
            showMessage('Failed to add canopy: ' + (result.error || 'Unknown error'), 'error');
            updateStatus('Canopy calculation failed');
        }
    } catch (error) {
        console.error('Canopy error:', error);
        showMessage('Error adding canopy: ' + error.message, 'error');
        updateStatus('Error');
    } finally {
        showLoading(false);
    }
}

/**
 * Handle Add Monitor (Roof Monitor) button click
 */
async function handleAddMonitor() {
    console.log('Adding roof monitor...');
    showLoading(true);
    updateStatus('Adding roof monitor...');

    try {
        const monitorData = {
            description: document.getElementById('monitor-description')?.value || 'Roof Monitor',
            salesCode: 1,
            type: document.getElementById('monitor-type')?.value || 'Curve-CF',
            width: parseFloat(document.getElementById('monitor-width')?.value) || 3.0,
            height: parseFloat(document.getElementById('monitor-height')?.value) || 1.5,
            length: document.getElementById('monitor-length')?.value || '6@6',
            purlinSpacing: parseFloat(document.getElementById('monitor-purlin-spacing')?.value) || 1.2,
            deadLoad: parseFloat(document.getElementById('monitor-dead-load')?.value) || 0.1,
            liveLoad: parseFloat(document.getElementById('monitor-live-load')?.value) || 0.5,
            windLoad: parseFloat(document.getElementById('calc-wind-load')?.value) || 0.5,
            roofTopSkin: document.getElementById('monitor-roof-skin')?.value || 'S5OW',
            roofCore: document.getElementById('monitor-roof-core')?.value || '-',
            roofBotSkin: document.getElementById('monitor-roof-bot-skin')?.value || '-',
            wallTopSkin: document.getElementById('monitor-wall-skin')?.value || 'S5OW',
            wallCore: document.getElementById('monitor-wall-core')?.value || '-',
            wallBotSkin: document.getElementById('monitor-wall-bot-skin')?.value || '-'
        };

        const response = await fetch('?action=calculate-monitor', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(monitorData)
        });

        const result = await response.json();

        if (result.success) {
            mergeCalculationResult(result, 'Monitor');
            hideSection('monitor');
            showMessage('Roof monitor added successfully!', 'success');
            updateStatus(`Monitor added: ${result.summary.itemCount} items, ${result.summary.totalWeight.toFixed(0)} kg`);
        } else {
            showMessage('Failed to add monitor: ' + (result.error || 'Unknown error'), 'error');
            updateStatus('Monitor calculation failed');
        }
    } catch (error) {
        console.error('Monitor error:', error);
        showMessage('Error adding monitor: ' + error.message, 'error');
        updateStatus('Error');
    } finally {
        showLoading(false);
    }
}

/**
 * Handle Add Liner button click
 */
async function handleAddLiner() {
    console.log('Adding liner...');
    showLoading(true);
    updateStatus('Adding liner...');

    try {
        // Get building dimensions from main form
        const buildingWidth = parseFloat(document.getElementById('calc-width')?.value) || 24;
        const buildingLength = parseFloat(document.getElementById('calc-length')?.value) || 36;
        const backEaveHeight = parseFloat(document.getElementById('back-eave-height')?.value) || 8;
        const frontEaveHeight = parseFloat(document.getElementById('front-eave-height')?.value) || 8;

        const linerData = {
            description: document.getElementById('liner-description')?.value || 'Liner',
            salesCode: 1,
            type: document.getElementById('liner-type')?.value || 'Both',
            roofLinerType: document.getElementById('liner-roof-material')?.value || 'S5OW',
            wallLinerType: document.getElementById('liner-wall-material')?.value || 'S5OW',
            buildingWidth: buildingWidth,
            buildingLength: buildingLength,
            backEaveHeight: backEaveHeight,
            frontEaveHeight: frontEaveHeight,
            roofArea: parseFloat(document.getElementById('liner-roof-area')?.value) || 0,
            wallArea: parseFloat(document.getElementById('liner-wall-area')?.value) || 0,
            roofOpenings: parseFloat(document.getElementById('liner-roof-openings')?.value) || 0,
            wallOpenings: parseFloat(document.getElementById('liner-wall-openings')?.value) || 0
        };

        const response = await fetch('?action=calculate-liner', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(linerData)
        });

        const result = await response.json();

        if (result.success) {
            mergeCalculationResult(result, 'Liner');
            hideSection('liner');
            showMessage('Liner added successfully!', 'success');
            updateStatus(`Liner added: ${result.summary.itemCount} items, ${result.summary.totalWeight.toFixed(0)} kg`);
        } else {
            showMessage('Failed to add liner: ' + (result.error || 'Unknown error'), 'error');
            updateStatus('Liner calculation failed');
        }
    } catch (error) {
        console.error('Liner error:', error);
        showMessage('Error adding liner: ' + error.message, 'error');
        updateStatus('Error');
    } finally {
        showLoading(false);
    }
}

// ============================================
// SAVE/LOAD PROJECT FUNCTIONALITY
// ============================================

/**
 * Save current project to a JSON file
 */
function saveProject() {
    if (!window.quickEstData && !window.quickEstInput) {
        showMessage('No project data to save. Please enter data or calculate first.', 'warning');
        return;
    }

    const projectData = {
        version: '2.0',
        savedAt: new Date().toISOString(),
        input: window.quickEstInput || collectFormData(),
        calculated: window.quickEstData || null,
        preferences: loadUserPreferences()
    };

    const projectName = document.getElementById('project-name')?.value || 'QuickEst_Project';
    const projectNumber = document.getElementById('project-number')?.value || 'HQ-O-00000';
    const filename = `${projectName.replace(/[^a-z0-9]/gi, '_')}_${projectNumber}_${new Date().toISOString().slice(0, 10)}.qep`;

    const blob = new Blob([JSON.stringify(projectData, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);

    showMessage(`Project saved as ${filename}`, 'success');
    updateStatus('Project saved');
}

/**
 * Show load project dialog
 */
function showLoadProjectDialog() {
    const existing = document.getElementById('load-project-dialog');
    if (existing) existing.remove();

    const dialog = document.createElement('div');
    dialog.id = 'load-project-dialog';
    dialog.className = 'modal-overlay';
    dialog.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-header">
                <h3>Load Project</h3>
                <button class="modal-close" onclick="closeLoadProjectDialog()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Select a QuickEst project file (.qep or .json):</p>
                <div class="file-upload-area" id="file-drop-zone">
                    <input type="file" id="project-file-input" accept=".qep,.json" style="display: none;">
                    <div class="drop-zone-content">
                        <span style="font-size: 48px; color: #217346;">📁</span>
                        <p>Drag & drop project file here</p>
                        <p style="color: #666; font-size: 12px;">or</p>
                        <button class="btn btn-primary" onclick="document.getElementById('project-file-input').click()">
                            Browse Files
                        </button>
                    </div>
                </div>
                <div id="load-project-status" style="margin-top: 15px; display: none;"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeLoadProjectDialog()">Cancel</button>
            </div>
        </div>
    `;
    document.body.appendChild(dialog);

    // Setup file input handler
    const fileInput = document.getElementById('project-file-input');
    fileInput.addEventListener('change', handleProjectFileSelect);

    // Setup drag and drop
    const dropZone = document.getElementById('file-drop-zone');
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
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            loadProjectFile(files[0]);
        }
    });
}

/**
 * Close load project dialog
 */
function closeLoadProjectDialog() {
    const dialog = document.getElementById('load-project-dialog');
    if (dialog) dialog.remove();
}

/**
 * Handle file selection for project load
 */
function handleProjectFileSelect(event) {
    const file = event.target.files[0];
    if (file) {
        loadProjectFile(file);
    }
}

/**
 * Load project from file
 */
function loadProjectFile(file) {
    const statusEl = document.getElementById('load-project-status');
    statusEl.style.display = 'block';
    statusEl.innerHTML = '<span style="color: #2196f3;">Loading...</span>';

    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            const projectData = JSON.parse(e.target.result);

            if (!projectData.input && !projectData.calculated) {
                throw new Error('Invalid project file format');
            }

            // Restore input data
            if (projectData.input) {
                window.quickEstInput = projectData.input;
                restoreFormData(projectData.input);
            }

            // Restore calculated data
            if (projectData.calculated) {
                window.quickEstData = projectData.calculated;
                sessionStorage.setItem('quickEstData', JSON.stringify(projectData.calculated));
                updateSummaryPanel(projectData.calculated);
            }

            // Restore preferences if present
            if (projectData.preferences) {
                saveUserPreferences(projectData.preferences);
            }

            closeLoadProjectDialog();
            showMessage('Project loaded successfully!', 'success');
            updateStatus(`Project loaded: ${file.name}`);

        } catch (error) {
            statusEl.innerHTML = `<span style="color: #f44336;">Error: ${error.message}</span>`;
        }
    };
    reader.onerror = function() {
        statusEl.innerHTML = '<span style="color: #f44336;">Error reading file</span>';
    };
    reader.readAsText(file);
}

/**
 * Restore form data from loaded project
 */
function restoreFormData(input) {
    const fieldMappings = {
        'date': 'project-date',
        'projectName': 'project-name',
        'buildingName': 'building-name',
        'customerName': 'customer-name',
        'projectNumber': 'project-number',
        'buildingNumber': 'building-number',
        'revisionNumber': 'revision',
        'location': 'location',
        'estimatedBy': 'estimated-by',
        'spans': 'spans',
        'bays': 'bays',
        'slopes': 'slopes',
        'backEaveHeight': 'back-eave-height',
        'frontEaveHeight': 'front-eave-height',
        'frameType': 'frame-type',
        'baseType': 'base-type',
        'minThickness': 'min-thickness',
        'doubleWelded': 'double-welded',
        'leftEndwallType': 'left-endwall-type',
        'rightEndwallType': 'right-endwall-type',
        'bracingType': 'bracing-type',
        'buFinish': 'bu-finish',
        'cfFinish': 'cf-finish',
        'purlinProfile': 'purlin-profile',
        'deadLoad': 'dead-load',
        'liveLoadPurlin': 'live-load-purlin',
        'liveLoadFrame': 'live-load-frame',
        'additionalLoad': 'additional-load',
        'windSpeed': 'wind-speed',
        'backEaveCondition': 'back-eave-condition',
        'frontEaveCondition': 'front-eave-condition',
        'roofPanelProfile': 'roof-panel-profile',
        'roofTopSkin': 'roof-top-skin',
        'roofCore': 'roof-core',
        'roofBotSkin': 'roof-bot-skin',
        'wallTopSkin': 'wall-top-skin',
        'wallCore': 'wall-core',
        'wallBotSkin': 'wall-bot-skin',
        'trimSizes': 'trim-sizes',
        'freightDestination': 'freight-destination'
    };

    Object.entries(fieldMappings).forEach(([key, elementId]) => {
        const element = document.getElementById(elementId);
        if (element && input[key] !== undefined) {
            element.value = input[key];
        }
    });

    // Trigger recalculations
    setTimeout(() => {
        updateDimensionCalculations();
        updateWindLoad();
        updatePeakHeight();
    }, 100);
}

// ============================================
// USER PREFERENCES FUNCTIONALITY
// ============================================

/**
 * Load user preferences from localStorage
 */
function loadUserPreferences() {
    try {
        const prefs = localStorage.getItem('quickEstPreferences');
        return prefs ? JSON.parse(prefs) : getDefaultPreferences();
    } catch (e) {
        return getDefaultPreferences();
    }
}

/**
 * Save user preferences to localStorage
 */
function saveUserPreferences(prefs) {
    try {
        localStorage.setItem('quickEstPreferences', JSON.stringify(prefs));
    } catch (e) {
        console.error('Failed to save preferences:', e);
    }
}

/**
 * Get default user preferences
 */
function getDefaultPreferences() {
    return {
        companyName: 'Mammut Building Systems',
        companyAddress: 'P.O. Box 8216, Sharjah, UAE',
        companyPhone: '+971 6 534 3030',
        companyEmail: 'sales@mammutbuildings.com',
        defaultEstimatedBy: '',
        defaultLocation: 'UAE',
        currency: 'AED',
        weightUnit: 'kg',
        dimensionUnit: 'm',
        autoSave: true,
        theme: 'light'
    };
}

/**
 * Show preferences dialog
 */
function showPreferencesDialog() {
    const existing = document.getElementById('preferences-dialog');
    if (existing) existing.remove();

    const prefs = loadUserPreferences();

    const dialog = document.createElement('div');
    dialog.id = 'preferences-dialog';
    dialog.className = 'modal-overlay';
    dialog.innerHTML = `
        <div class="modal-dialog" style="max-width: 500px;">
            <div class="modal-header">
                <h3>User Preferences</h3>
                <button class="modal-close" onclick="closePreferencesDialog()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="pref-section">
                    <h4 style="color: #217346; margin-bottom: 10px; border-bottom: 1px solid #ddd; padding-bottom: 5px;">Company Information</h4>
                    <div class="form-row">
                        <label>Company Name:</label>
                        <input type="text" id="pref-company-name" value="${prefs.companyName || ''}" class="form-control">
                    </div>
                    <div class="form-row">
                        <label>Address:</label>
                        <input type="text" id="pref-company-address" value="${prefs.companyAddress || ''}" class="form-control">
                    </div>
                    <div class="form-row">
                        <label>Phone:</label>
                        <input type="text" id="pref-company-phone" value="${prefs.companyPhone || ''}" class="form-control">
                    </div>
                    <div class="form-row">
                        <label>Email:</label>
                        <input type="text" id="pref-company-email" value="${prefs.companyEmail || ''}" class="form-control">
                    </div>
                </div>

                <div class="pref-section" style="margin-top: 20px;">
                    <h4 style="color: #217346; margin-bottom: 10px; border-bottom: 1px solid #ddd; padding-bottom: 5px;">Default Values</h4>
                    <div class="form-row">
                        <label>Default Estimated By:</label>
                        <input type="text" id="pref-estimated-by" value="${prefs.defaultEstimatedBy || ''}" class="form-control">
                    </div>
                    <div class="form-row">
                        <label>Default Location:</label>
                        <input type="text" id="pref-location" value="${prefs.defaultLocation || ''}" class="form-control">
                    </div>
                </div>

                <div class="pref-section" style="margin-top: 20px;">
                    <h4 style="color: #217346; margin-bottom: 10px; border-bottom: 1px solid #ddd; padding-bottom: 5px;">Display Settings</h4>
                    <div class="form-row">
                        <label>Currency:</label>
                        <select id="pref-currency" class="form-control">
                            <option value="AED" ${prefs.currency === 'AED' ? 'selected' : ''}>AED (UAE Dirham)</option>
                            <option value="USD" ${prefs.currency === 'USD' ? 'selected' : ''}>USD (US Dollar)</option>
                            <option value="EUR" ${prefs.currency === 'EUR' ? 'selected' : ''}>EUR (Euro)</option>
                            <option value="SAR" ${prefs.currency === 'SAR' ? 'selected' : ''}>SAR (Saudi Riyal)</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label>
                            <input type="checkbox" id="pref-autosave" ${prefs.autoSave ? 'checked' : ''}>
                            Auto-save to session storage
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closePreferencesDialog()">Cancel</button>
                <button class="btn btn-primary" onclick="savePreferencesFromDialog()">Save Preferences</button>
            </div>
        </div>
    `;
    document.body.appendChild(dialog);
}

/**
 * Close preferences dialog
 */
function closePreferencesDialog() {
    const dialog = document.getElementById('preferences-dialog');
    if (dialog) dialog.remove();
}

/**
 * Save preferences from dialog
 */
function savePreferencesFromDialog() {
    const prefs = {
        companyName: document.getElementById('pref-company-name')?.value || '',
        companyAddress: document.getElementById('pref-company-address')?.value || '',
        companyPhone: document.getElementById('pref-company-phone')?.value || '',
        companyEmail: document.getElementById('pref-company-email')?.value || '',
        defaultEstimatedBy: document.getElementById('pref-estimated-by')?.value || '',
        defaultLocation: document.getElementById('pref-location')?.value || '',
        currency: document.getElementById('pref-currency')?.value || 'AED',
        autoSave: document.getElementById('pref-autosave')?.checked ?? true
    };

    saveUserPreferences(prefs);

    // Apply default values to form if empty
    const estimatedByField = document.getElementById('estimated-by');
    if (estimatedByField && !estimatedByField.value && prefs.defaultEstimatedBy) {
        estimatedByField.value = prefs.defaultEstimatedBy;
    }

    const locationField = document.getElementById('location');
    if (locationField && !locationField.value && prefs.defaultLocation) {
        locationField.value = prefs.defaultLocation;
    }

    closePreferencesDialog();
    showMessage('Preferences saved successfully!', 'success');
}

/**
 * Apply user preferences on page load
 */
function applyUserPreferences() {
    const prefs = loadUserPreferences();

    // Apply default values to empty fields
    const estimatedByField = document.getElementById('estimated-by');
    if (estimatedByField && !estimatedByField.value && prefs.defaultEstimatedBy) {
        estimatedByField.value = prefs.defaultEstimatedBy;
    }

    const locationField = document.getElementById('location');
    if (locationField && !locationField.value && prefs.defaultLocation) {
        locationField.value = prefs.defaultLocation;
    }
}

// ============================================
// PRINT VIEW FUNCTIONALITY
// ============================================

/**
 * Open print view in new window
 */
function openPrintView() {
    if (!window.quickEstData) {
        showMessage('No data to print. Please calculate first.', 'warning');
        return;
    }

    const prefs = loadUserPreferences();
    const projectInfo = window.quickEstInput || collectFormData();
    const data = window.quickEstData;
    const dims = data.dimensions || {};
    const summary = data.summary || {};

    // Generate print HTML
    const printHtml = generatePrintHTML(prefs, projectInfo, data, dims, summary);

    // Open in new window
    const printWindow = window.open('', '_blank', 'width=900,height=700');
    printWindow.document.write(printHtml);
    printWindow.document.close();
}

/**
 * Generate print-ready HTML
 */
function generatePrintHTML(prefs, projectInfo, data, dims, summary) {
    const currency = prefs.currency || 'AED';
    const items = data.items || [];

    // Group items by sales code for organized display
    const groupedItems = {};
    items.forEach(item => {
        if (item.isHeader || item.isSeparator) return;
        const salesCode = item.salesCode || 99;
        if (!groupedItems[salesCode]) {
            groupedItems[salesCode] = [];
        }
        groupedItems[salesCode].push(item);
    });

    const salesCodeNames = {
        1: 'Primary Framing',
        2: 'Secondary Framing',
        3: 'Roof Sheeting',
        4: 'Wall Sheeting',
        5: 'Trim & Flashing',
        6: 'Fasteners',
        7: 'Doors & Windows',
        8: 'Ventilation',
        9: 'Insulation',
        10: 'Gutters & Downspouts',
        11: 'Crane System',
        12: 'Mezzanine',
        13: 'Accessories',
        99: 'Other'
    };

    let itemsHTML = '';
    let grandTotal = 0;
    let grandWeight = 0;

    Object.entries(groupedItems).forEach(([code, categoryItems]) => {
        const categoryName = salesCodeNames[code] || `Category ${code}`;
        let categoryTotal = 0;
        let categoryWeight = 0;

        itemsHTML += `
            <tr class="category-header">
                <td colspan="7" style="background: #217346; color: white; font-weight: bold; padding: 8px;">
                    ${categoryName}
                </td>
            </tr>
        `;

        categoryItems.forEach((item, idx) => {
            const price = parseFloat(item.totalPrice) || 0;
            const weight = parseFloat(item.totalWeight) || 0;
            categoryTotal += price;
            categoryWeight += weight;

            itemsHTML += `
                <tr>
                    <td style="padding: 6px;">${item.dbCode || ''}</td>
                    <td style="padding: 6px;">${item.description || ''}</td>
                    <td style="padding: 6px; text-align: center;">${item.unit || ''}</td>
                    <td style="padding: 6px; text-align: right;">${item.quantity ? parseFloat(item.quantity).toFixed(2) : ''}</td>
                    <td style="padding: 6px; text-align: right;">${weight.toFixed(2)}</td>
                    <td style="padding: 6px; text-align: right;">${price.toFixed(2)}</td>
                </tr>
            `;
        });

        itemsHTML += `
            <tr style="background: #f0f0f0; font-weight: bold;">
                <td colspan="4" style="text-align: right; padding: 6px;">Subtotal:</td>
                <td style="text-align: right; padding: 6px;">${categoryWeight.toFixed(2)}</td>
                <td style="text-align: right; padding: 6px;">${categoryTotal.toFixed(2)}</td>
            </tr>
        `;

        grandTotal += categoryTotal;
        grandWeight += categoryWeight;
    });

    return `
<!DOCTYPE html>
<html>
<head>
    <title>QuickEst Quotation - ${projectInfo.projectName || 'Project'}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; line-height: 1.4; padding: 20px; }
        .header { display: flex; justify-content: space-between; border-bottom: 3px solid #217346; padding-bottom: 15px; margin-bottom: 20px; }
        .company-info h1 { color: #217346; font-size: 24px; margin-bottom: 5px; }
        .company-info p { color: #666; font-size: 10px; }
        .quote-info { text-align: right; }
        .quote-info h2 { color: #333; font-size: 18px; margin-bottom: 5px; }
        .project-details { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .detail-box { background: #f8f8f8; padding: 15px; border-left: 4px solid #217346; }
        .detail-box h3 { color: #217346; margin-bottom: 10px; font-size: 12px; }
        .detail-row { display: flex; justify-content: space-between; margin-bottom: 3px; }
        .detail-label { color: #666; }
        .detail-value { font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #217346; color: white; padding: 8px; text-align: left; font-size: 10px; }
        td { border-bottom: 1px solid #ddd; padding: 6px; }
        .totals-section { display: flex; justify-content: flex-end; margin-top: 20px; }
        .totals-box { width: 300px; }
        .total-row { display: flex; justify-content: space-between; padding: 8px; border-bottom: 1px solid #ddd; }
        .grand-total { background: #217346; color: white; font-size: 14px; font-weight: bold; }
        .footer { margin-top: 30px; border-top: 1px solid #ddd; padding-top: 15px; }
        .footer h4 { color: #217346; margin-bottom: 10px; }
        .footer ul { font-size: 10px; color: #666; padding-left: 20px; }
        .print-button { position: fixed; top: 10px; right: 10px; padding: 10px 20px; background: #217346; color: white; border: none; cursor: pointer; font-size: 14px; }
        @media print {
            .print-button { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <button class="print-button" onclick="window.print()">Print / Save PDF</button>

    <div class="header">
        <div class="company-info">
            <h1>${prefs.companyName || 'Mammut Building Systems'}</h1>
            <p>${prefs.companyAddress || 'P.O. Box 8216, Sharjah, UAE'}</p>
            <p>Tel: ${prefs.companyPhone || '+971 6 534 3030'} | Email: ${prefs.companyEmail || 'sales@mammutbuildings.com'}</p>
        </div>
        <div class="quote-info">
            <h2>QUOTATION</h2>
            <p><strong>Project No:</strong> ${projectInfo.projectNumber || 'HQ-O-00000'}</p>
            <p><strong>Building No:</strong> ${projectInfo.buildingNumber || '1'}</p>
            <p><strong>Revision:</strong> ${projectInfo.revisionNumber || '00'}</p>
            <p><strong>Date:</strong> ${projectInfo.date || new Date().toLocaleDateString()}</p>
        </div>
    </div>

    <div class="project-details">
        <div class="detail-box">
            <h3>PROJECT INFORMATION</h3>
            <div class="detail-row"><span class="detail-label">Project:</span><span class="detail-value">${projectInfo.projectName || '-'}</span></div>
            <div class="detail-row"><span class="detail-label">Building:</span><span class="detail-value">${projectInfo.buildingName || '-'}</span></div>
            <div class="detail-row"><span class="detail-label">Customer:</span><span class="detail-value">${projectInfo.customerName || '-'}</span></div>
            <div class="detail-row"><span class="detail-label">Location:</span><span class="detail-value">${projectInfo.location || '-'}</span></div>
            <div class="detail-row"><span class="detail-label">Estimated By:</span><span class="detail-value">${projectInfo.estimatedBy || '-'}</span></div>
        </div>
        <div class="detail-box">
            <h3>BUILDING DIMENSIONS</h3>
            <div class="detail-row"><span class="detail-label">Width:</span><span class="detail-value">${(dims.width || 0).toFixed(1)} m</span></div>
            <div class="detail-row"><span class="detail-label">Length:</span><span class="detail-value">${(dims.length || 0).toFixed(1)} m</span></div>
            <div class="detail-row"><span class="detail-label">Eave Height:</span><span class="detail-value">${(dims.backEaveHeight || 0).toFixed(1)} m</span></div>
            <div class="detail-row"><span class="detail-label">Floor Area:</span><span class="detail-value">${((dims.width || 0) * (dims.length || 0)).toFixed(0)} m²</span></div>
        </div>
    </div>

    <h3 style="color: #217346; margin-bottom: 10px;">BILL OF MATERIALS</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 80px;">Code</th>
                <th>Description</th>
                <th style="width: 50px;">Unit</th>
                <th style="width: 70px; text-align: right;">Qty</th>
                <th style="width: 80px; text-align: right;">Weight (kg)</th>
                <th style="width: 90px; text-align: right;">Price (${currency})</th>
            </tr>
        </thead>
        <tbody>
            ${itemsHTML}
        </tbody>
    </table>

    <div class="totals-section">
        <div class="totals-box">
            <div class="total-row">
                <span>Total Weight:</span>
                <span><strong>${grandWeight.toLocaleString('en-US', {maximumFractionDigits: 2})} kg</strong></span>
            </div>
            <div class="total-row">
                <span>Weight per m²:</span>
                <span>${((dims.width || 1) * (dims.length || 1) > 0 ? (grandWeight / ((dims.width || 1) * (dims.length || 1))).toFixed(2) : '-')} kg/m²</span>
            </div>
            <div class="total-row grand-total">
                <span>TOTAL PRICE:</span>
                <span>${grandTotal.toLocaleString('en-US', {maximumFractionDigits: 2})} ${currency}</span>
            </div>
            <div class="total-row">
                <span>Price per m²:</span>
                <span>${((dims.width || 1) * (dims.length || 1) > 0 ? (grandTotal / ((dims.width || 1) * (dims.length || 1))).toFixed(2) : '-')} ${currency}/m²</span>
            </div>
        </div>
    </div>

    <div class="footer">
        <h4>Terms & Conditions</h4>
        <ul>
            <li>All prices are in ${currency} and Ex-Works ${prefs.companyName || 'Mammut'} Factory</li>
            <li>This quotation is valid for 30 days from the date shown above</li>
            <li>Erection and foundation works are not included unless specified</li>
            <li>Subject to final engineering approval</li>
            <li>Payment terms: As per contract agreement</li>
            <li>Delivery: Subject to confirmation at time of order</li>
        </ul>
    </div>

    <script>
        // Auto-focus print dialog
        setTimeout(() => { window.focus(); }, 500);
    </script>
</body>
</html>
    `;
}

// Apply preferences on load
document.addEventListener('DOMContentLoaded', function() {
    applyUserPreferences();
});
