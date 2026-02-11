<?php
/**
 * QuickEst - FCPBS Report Template
 *
 * Full Cost Price Breakdown Summary
 * Replicates the FCPBS sheet layout
 */
?>

<div class="spreadsheet-container">
    <div id="fcpbs-report" style="padding: 20px; max-width: 1200px; margin: 0 auto;">

        <!-- Report Header -->
        <div class="fcpbs-header" style="text-align: center; margin-bottom: 30px;">
            <h2 style="color: #217346; margin-bottom: 5px;">FULL COST PRICE BREAKDOWN SUMMARY</h2>
            <p style="color: #666; font-size: 12px;">Generated: <span id="report-date"></span></p>
        </div>

        <!-- Project Info -->
        <div class="fcpbs-project-info" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; background: #f8f8f8; padding: 15px; border: 1px solid #ddd;">
            <div>
                <table style="width: 100%; font-size: 12px;">
                    <tr>
                        <td style="font-weight: bold; width: 120px;">Project:</td>
                        <td id="fcpbs-project"></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Building:</td>
                        <td id="fcpbs-building"></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Customer:</td>
                        <td id="fcpbs-customer"></td>
                    </tr>
                </table>
            </div>
            <div>
                <table style="width: 100%; font-size: 12px;">
                    <tr>
                        <td style="font-weight: bold; width: 120px;">Project No:</td>
                        <td id="fcpbs-project-no"></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Building No:</td>
                        <td id="fcpbs-building-no"></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Revision:</td>
                        <td id="fcpbs-revision"></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Building Dimensions Summary -->
        <div class="fcpbs-dimensions" style="margin-bottom: 30px;">
            <h3 style="color: #217346; border-bottom: 2px solid #217346; padding-bottom: 5px; margin-bottom: 15px;">
                Building Dimensions
            </h3>
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px;">
                <div class="dim-box" style="background: #f0f8f0; padding: 15px; text-align: center; border: 1px solid #217346;">
                    <div style="font-size: 11px; color: #666;">Width</div>
                    <div style="font-size: 24px; font-weight: bold; color: #217346;" id="fcpbs-width">-</div>
                    <div style="font-size: 11px; color: #666;">meters</div>
                </div>
                <div class="dim-box" style="background: #f0f8f0; padding: 15px; text-align: center; border: 1px solid #217346;">
                    <div style="font-size: 11px; color: #666;">Length</div>
                    <div style="font-size: 24px; font-weight: bold; color: #217346;" id="fcpbs-length">-</div>
                    <div style="font-size: 11px; color: #666;">meters</div>
                </div>
                <div class="dim-box" style="background: #f0f8f0; padding: 15px; text-align: center; border: 1px solid #217346;">
                    <div style="font-size: 11px; color: #666;">Eave Height</div>
                    <div style="font-size: 24px; font-weight: bold; color: #217346;" id="fcpbs-eave">-</div>
                    <div style="font-size: 11px; color: #666;">meters</div>
                </div>
                <div class="dim-box" style="background: #f0f8f0; padding: 15px; text-align: center; border: 1px solid #217346;">
                    <div style="font-size: 11px; color: #666;">Floor Area</div>
                    <div style="font-size: 24px; font-weight: bold; color: #217346;" id="fcpbs-area">-</div>
                    <div style="font-size: 11px; color: #666;">m²</div>
                </div>
            </div>
        </div>

        <!-- Cost Summary Table -->
        <div class="fcpbs-costs" style="margin-bottom: 30px;">
            <h3 style="color: #217346; border-bottom: 2px solid #217346; padding-bottom: 5px; margin-bottom: 15px;">
                Cost Summary
            </h3>

            <table id="fcpbs-table" class="detail-table" style="width: 100%;">
                <thead>
                    <tr style="background: #217346; color: white;">
                        <th style="width: 50px;">No.</th>
                        <th>Description</th>
                        <th style="width: 100px; text-align: right;">Quantity</th>
                        <th style="width: 60px;">Unit</th>
                        <th style="width: 100px; text-align: right;">Weight (kg)</th>
                        <th style="width: 120px; text-align: right;">Material (AED)</th>
                        <th style="width: 120px; text-align: right;">Manuf. (AED)</th>
                        <th style="width: 120px; text-align: right;">Total (AED)</th>
                    </tr>
                </thead>
                <tbody id="fcpbs-body">
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px; color: #666;">
                            No data available. Please calculate from the Input tab first.
                        </td>
                    </tr>
                </tbody>
                <tfoot id="fcpbs-footer" style="display: none;">
                    <tr style="background: #f0f0f0; font-weight: bold;">
                        <td colspan="4" style="text-align: right; padding: 10px;">SUB TOTAL:</td>
                        <td id="fcpbs-total-weight" style="text-align: right;">-</td>
                        <td id="fcpbs-total-material" style="text-align: right;">-</td>
                        <td id="fcpbs-total-manuf" style="text-align: right;">-</td>
                        <td id="fcpbs-total-price" style="text-align: right;">-</td>
                    </tr>
                    <tr style="background: #217346; color: white; font-weight: bold; font-size: 14px;">
                        <td colspan="7" style="text-align: right; padding: 12px;">GRAND TOTAL (AED):</td>
                        <td id="fcpbs-grand-total" style="text-align: right; padding: 12px;">-</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Key Metrics -->
        <div class="fcpbs-metrics" style="margin-bottom: 30px;">
            <h3 style="color: #217346; border-bottom: 2px solid #217346; padding-bottom: 5px; margin-bottom: 15px;">
                Key Metrics
            </h3>

            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                <div style="background: #fff3cd; padding: 20px; border-left: 4px solid #ffc107;">
                    <div style="font-size: 12px; color: #666;">Price per m²</div>
                    <div style="font-size: 28px; font-weight: bold; color: #333;" id="fcpbs-price-sqm">-</div>
                    <div style="font-size: 11px; color: #666;">AED/m²</div>
                </div>
                <div style="background: #d4edda; padding: 20px; border-left: 4px solid #28a745;">
                    <div style="font-size: 12px; color: #666;">Weight per m²</div>
                    <div style="font-size: 28px; font-weight: bold; color: #333;" id="fcpbs-weight-sqm">-</div>
                    <div style="font-size: 11px; color: #666;">kg/m²</div>
                </div>
                <div style="background: #cce5ff; padding: 20px; border-left: 4px solid #007bff;">
                    <div style="font-size: 12px; color: #666;">Price per kg</div>
                    <div style="font-size: 28px; font-weight: bold; color: #333;" id="fcpbs-price-kg">-</div>
                    <div style="font-size: 11px; color: #666;">AED/kg</div>
                </div>
            </div>
        </div>

        <!-- Notes Section -->
        <div class="fcpbs-notes" style="margin-top: 30px; border-top: 1px solid #ddd; padding-top: 20px;">
            <h4 style="color: #666; margin-bottom: 10px;">Notes & Exclusions:</h4>
            <ul style="font-size: 11px; color: #666; padding-left: 20px;">
                <li>All prices are in AED (UAE Dirhams)</li>
                <li>Prices are Ex-Works Mammut Factory</li>
                <li>Erection and foundation works are not included unless specified</li>
                <li>Subject to final engineering approval</li>
                <li>Validity: 30 days from date of quotation</li>
            </ul>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set report date
    document.getElementById('report-date').textContent = new Date().toLocaleDateString();

    // Check if we have data
    const data = window.quickEstData;
    if (!data || !data.items || data.items.length === 0) {
        return;
    }

    // Populate project info
    const projectInfo = window.quickEstInput || {};
    document.getElementById('fcpbs-project').textContent = projectInfo.projectName || '-';
    document.getElementById('fcpbs-building').textContent = projectInfo.buildingName || '-';
    document.getElementById('fcpbs-customer').textContent = projectInfo.customerName || '-';
    document.getElementById('fcpbs-project-no').textContent = projectInfo.projectNumber || '-';
    document.getElementById('fcpbs-building-no').textContent = projectInfo.buildingNumber || '-';
    document.getElementById('fcpbs-revision').textContent = projectInfo.revisionNumber || '00';

    // Populate dimensions
    const dims = data.dimensions || {};
    document.getElementById('fcpbs-width').textContent = (dims.width || 0).toFixed(1);
    document.getElementById('fcpbs-length').textContent = (dims.length || 0).toFixed(1);
    document.getElementById('fcpbs-eave').textContent = (dims.backEaveHeight || 0).toFixed(1);
    document.getElementById('fcpbs-area').textContent = ((dims.width || 0) * (dims.length || 0)).toFixed(0);

    // Define sales code categories for grouping (matches Excel FCPBS structure)
    const salesCategories = {
        1: { name: 'PRIMARY FRAMING', codes: [1], items: [], weight: 0, material: 0, manuf: 0, price: 0 },
        2: { name: 'SECONDARY FRAMING', codes: [2], items: [], weight: 0, material: 0, manuf: 0, price: 0 },
        3: { name: 'ROOF SHEETING', codes: [3], items: [], weight: 0, material: 0, manuf: 0, price: 0 },
        4: { name: 'WALL SHEETING', codes: [4], items: [], weight: 0, material: 0, manuf: 0, price: 0 },
        5: { name: 'TRIM & FLASHING', codes: [5], items: [], weight: 0, material: 0, manuf: 0, price: 0 },
        6: { name: 'FASTENERS', codes: [6], items: [], weight: 0, material: 0, manuf: 0, price: 0 },
        7: { name: 'DOORS & WINDOWS', codes: [7], items: [], weight: 0, material: 0, manuf: 0, price: 0 },
        8: { name: 'VENTILATION', codes: [8], items: [], weight: 0, material: 0, manuf: 0, price: 0 },
        9: { name: 'INSULATION', codes: [9], items: [], weight: 0, material: 0, manuf: 0, price: 0 },
        10: { name: 'GUTTERS & DOWNSPOUTS', codes: [10], items: [], weight: 0, material: 0, manuf: 0, price: 0 },
        11: { name: 'CRANE SYSTEM', codes: [11], items: [], weight: 0, material: 0, manuf: 0, price: 0 },
        12: { name: 'MEZZANINE', codes: [12], items: [], weight: 0, material: 0, manuf: 0, price: 0 },
        13: { name: 'ACCESSORIES', codes: [13, 14, 15], items: [], weight: 0, material: 0, manuf: 0, price: 0 },
        99: { name: 'OTHER', codes: [], items: [], weight: 0, material: 0, manuf: 0, price: 0 }
    };

    // Group items by sales code category
    data.items.forEach(item => {
        if (item.isHeader || item.isSeparator || !item.dbCode) return;

        const salesCode = parseInt(item.salesCode) || 99;
        let category = null;

        // Find matching category
        for (const [key, cat] of Object.entries(salesCategories)) {
            if (cat.codes.includes(salesCode)) {
                category = cat;
                break;
            }
        }

        // Default to OTHER if no match
        if (!category) {
            category = salesCategories[99];
        }

        const weight = parseFloat(item.totalWeight) || 0;
        const qty = parseFloat(item.quantity) || 0;
        const matCost = (parseFloat(item.materialCost) || 0) * qty;
        const manufCost = (parseFloat(item.manufacturingCost) || 0) * qty;
        const price = parseFloat(item.totalPrice) || 0;

        category.items.push({
            ...item,
            weight,
            matCost,
            manufCost,
            price
        });

        category.weight += weight;
        category.material += matCost;
        category.manuf += manufCost;
        category.price += price;
    });

    // Build table grouped by category
    const tbody = document.getElementById('fcpbs-body');
    tbody.innerHTML = '';

    let totalWeight = 0;
    let totalMaterial = 0;
    let totalManuf = 0;
    let totalPrice = 0;
    let categoryNum = 0;

    Object.entries(salesCategories).forEach(([key, category]) => {
        if (category.items.length === 0) return;

        categoryNum++;

        // Category header row
        const headerRow = document.createElement('tr');
        headerRow.className = 'category-header';
        headerRow.innerHTML = `
            <td colspan="8" style="background: #217346; color: white; font-weight: bold; padding: 10px; font-size: 13px;">
                ${categoryNum}. ${category.name}
            </td>
        `;
        tbody.appendChild(headerRow);

        // Item rows
        let itemNum = 0;
        category.items.forEach(item => {
            itemNum++;
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="padding-left: 20px;">${categoryNum}.${itemNum}</td>
                <td>${item.description || item.dbCode}</td>
                <td style="text-align: right;">${item.quantity ? parseFloat(item.quantity).toFixed(2) : '-'}</td>
                <td>${item.unit || ''}</td>
                <td style="text-align: right;">${item.weight.toFixed(2)}</td>
                <td style="text-align: right;">${item.matCost.toFixed(2)}</td>
                <td style="text-align: right;">${item.manufCost.toFixed(2)}</td>
                <td style="text-align: right;">${item.price.toFixed(2)}</td>
            `;
            tbody.appendChild(tr);
        });

        // Category subtotal row
        const subtotalRow = document.createElement('tr');
        subtotalRow.className = 'category-subtotal';
        subtotalRow.innerHTML = `
            <td colspan="4" style="text-align: right; background: #e8f5e9; font-weight: 600; padding: 8px;">
                ${category.name} Subtotal:
            </td>
            <td style="text-align: right; background: #e8f5e9; font-weight: 600;">${category.weight.toFixed(2)}</td>
            <td style="text-align: right; background: #e8f5e9; font-weight: 600;">${category.material.toFixed(2)}</td>
            <td style="text-align: right; background: #e8f5e9; font-weight: 600;">${category.manuf.toFixed(2)}</td>
            <td style="text-align: right; background: #e8f5e9; font-weight: 600;">${category.price.toFixed(2)}</td>
        `;
        tbody.appendChild(subtotalRow);

        // Add spacing row
        const spacerRow = document.createElement('tr');
        spacerRow.innerHTML = '<td colspan="8" style="height: 8px; border: none;"></td>';
        tbody.appendChild(spacerRow);

        totalWeight += category.weight;
        totalMaterial += category.material;
        totalManuf += category.manuf;
        totalPrice += category.price;
    });

    // Show footer and populate totals
    document.getElementById('fcpbs-footer').style.display = '';
    document.getElementById('fcpbs-total-weight').textContent = totalWeight.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('fcpbs-total-material').textContent = totalMaterial.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('fcpbs-total-manuf').textContent = totalManuf.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('fcpbs-total-price').textContent = totalPrice.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('fcpbs-grand-total').textContent = totalPrice.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

    // Calculate metrics
    const area = (dims.width || 1) * (dims.length || 1);
    document.getElementById('fcpbs-price-sqm').textContent = (totalPrice / area).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('fcpbs-weight-sqm').textContent = (totalWeight / area).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('fcpbs-price-kg').textContent = totalWeight > 0 ? (totalPrice / totalWeight).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '-';

    // Update status bar
    document.getElementById('status-message').textContent =
        `FCPBS Report | ${categoryNum} categories | ${totalWeight.toLocaleString('en-US', {maximumFractionDigits: 0})} kg | ${totalPrice.toLocaleString('en-US', {maximumFractionDigits: 0})} AED`;
});
</script>
