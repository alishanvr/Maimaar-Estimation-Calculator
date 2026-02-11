<?php
/**
 * QuickEst - Input Form Template
 *
 * Excel-like input form for building specifications
 * Replicates the Input sheet layout
 */
?>

<!-- Quick Actions Bar -->
<div class="quick-actions">
    <button id="btn-add-area" class="active" data-tooltip="Add Building Area">Add Area</button>
    <button id="btn-add-mezzanine" data-tooltip="Add Mezzanine">Add Mezzanine</button>
    <button id="btn-add-crane" data-tooltip="Add Crane">Add Crane</button>
    <button id="btn-add-partition" data-tooltip="Add Partition">Add Partition</button>
    <button id="btn-add-canopy" data-tooltip="Add Canopy">Add Canopy</button>
    <button id="btn-add-liner" data-tooltip="Add Liner">Add Liner</button>
    <button id="btn-add-accessory" data-tooltip="Add Accessory">Add Accessory</button>
</div>

<div class="spreadsheet-container">
    <!-- Project Information Header -->
    <div class="project-header">
        <div class="info-cell">
            <div class="info-label">Date</div>
            <input type="date" id="project-date" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="info-cell">
            <div class="info-label">Building Name</div>
            <input type="text" id="building-name" placeholder="Enter building name...">
        </div>
        <div class="info-cell">
            <div class="info-label">Revision</div>
            <input type="text" id="revision" value="00">
        </div>
        <div class="info-cell">
            <div class="info-label">Project Name</div>
            <input type="text" id="project-name" placeholder="Enter project name...">
        </div>
        <div class="info-cell">
            <div class="info-label">Project No.</div>
            <input type="text" id="project-number" placeholder="HQ-O-">
        </div>
        <div class="info-cell">
            <div class="info-label">Building No.</div>
            <input type="text" id="building-number" value="1">
        </div>
        <div class="info-cell">
            <div class="info-label">Customer Name</div>
            <input type="text" id="customer-name" placeholder="Enter customer name...">
        </div>
        <div class="info-cell">
            <div class="info-label">Location</div>
            <select id="location">
                <option value="UAE">UAE</option>
                <option value="Saudi Arabia">Saudi Arabia</option>
                <option value="Qatar">Qatar</option>
                <option value="Kuwait">Kuwait</option>
                <option value="Bahrain">Bahrain</option>
                <option value="Oman">Oman</option>
                <option value="Other">Other</option>
            </select>
        </div>
        <div class="info-cell">
            <div class="info-label">Estimated By</div>
            <input type="text" id="estimated-by" placeholder="Your name...">
        </div>
    </div>

    <!-- Main Input Form -->
    <div class="excel-form" id="input-form">

        <!-- BUILDING DIMENSIONS -->
        <div class="section-divider">Building Dimensions</div>

        <div class="form-grid">
            <div class="form-row">
                <div class="form-label">Spans (Width)</div>
                <div class="form-input">
                    <input type="text" id="spans" placeholder="e.g., 2@24 or 24+24" value="1@24">
                </div>
                <div class="form-label">Bays (Length)</div>
                <div class="form-input">
                    <input type="text" id="bays" placeholder="e.g., 6@6 or 6+6+6+6+6+6" value="6@6">
                </div>
            </div>

            <div class="form-row">
                <div class="form-label">Building Width (m)</div>
                <div class="form-input readonly">
                    <input type="text" id="calc-width" readonly>
                </div>
                <div class="form-label">Building Length (m)</div>
                <div class="form-input readonly">
                    <input type="text" id="calc-length" readonly>
                </div>
            </div>

            <div class="form-row">
                <div class="form-label">Back Eave Height (m)</div>
                <div class="form-input">
                    <input type="number" id="back-eave-height" step="0.1" value="8">
                </div>
                <div class="form-label">Front Eave Height (m)</div>
                <div class="form-input">
                    <input type="number" id="front-eave-height" step="0.1" value="8">
                </div>
            </div>

            <div class="form-row">
                <div class="form-label">Roof Slope</div>
                <div class="form-input">
                    <input type="text" id="slopes" placeholder="e.g., 0.1 or 1@0.1" value="0.1">
                </div>
                <div class="form-label">Peak Height (m)</div>
                <div class="form-input readonly">
                    <input type="text" id="calc-peak-height" readonly>
                </div>
            </div>
        </div>

        <!-- STRUCTURAL OPTIONS -->
        <div class="section-divider">Structural Options</div>

        <div class="form-grid">
            <div class="form-row">
                <div class="form-label">Frame Type</div>
                <div class="form-input">
                    <select id="frame-type">
                        <option value="Clear Span">Clear Span</option>
                        <option value="Multi Span">Multi Span</option>
                        <option value="Lean To">Lean To</option>
                    </select>
                </div>
                <div class="form-label">Base Type</div>
                <div class="form-input">
                    <select id="base-type">
                        <option value="Pinned Base">Pinned Base</option>
                        <option value="Fixed Base">Fixed Base</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-label">Min. Plate Thickness (mm)</div>
                <div class="form-input">
                    <select id="min-thickness">
                        <option value="5">5 mm</option>
                        <option value="6" selected>6 mm</option>
                        <option value="8">8 mm</option>
                        <option value="10">10 mm</option>
                    </select>
                </div>
                <div class="form-label">Double Welded</div>
                <div class="form-input">
                    <select id="double-welded">
                        <option value="No">No</option>
                        <option value="Yes">Yes</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-label">Left Endwall Type</div>
                <div class="form-input">
                    <select id="left-endwall-type">
                        <option value="Bearing Frame">Bearing Frame</option>
                        <option value="Main Frame">Main Frame</option>
                        <option value="False Rafter">False Rafter</option>
                        <option value="MF 1/2 Loaded">MF 1/2 Loaded</option>
                    </select>
                </div>
                <div class="form-label">Right Endwall Type</div>
                <div class="form-input">
                    <select id="right-endwall-type">
                        <option value="Bearing Frame">Bearing Frame</option>
                        <option value="Main Frame">Main Frame</option>
                        <option value="False Rafter">False Rafter</option>
                        <option value="MF 1/2 Loaded">MF 1/2 Loaded</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-label">Bracing Type</div>
                <div class="form-input">
                    <select id="bracing-type">
                        <option value="Cables">Cables</option>
                        <option value="Rods">Rods</option>
                        <option value="Angles">Angles</option>
                        <option value="Portal">Portal</option>
                    </select>
                </div>
                <div class="form-label">B/U Finish</div>
                <div class="form-input">
                    <select id="bu-finish">
                        <option value="Red Oxide Primer">Red Oxide Primer</option>
                        <option value="Gray Primer">Gray Primer</option>
                        <option value="Galvanized">Galvanized</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-label">C/F Finish</div>
                <div class="form-input">
                    <select id="cf-finish">
                        <option value="Galvanized" selected>Galvanized</option>
                        <option value="Alu/Zinc">Alu/Zinc</option>
                        <option value="Painted">Painted</option>
                    </select>
                </div>
                <div class="form-label">Purlin Profile</div>
                <div class="form-input">
                    <select id="purlin-profile">
                        <option value="Z-Section">Z-Section</option>
                        <option value="C-Section">C-Section</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- LOADS -->
        <div class="section-divider">Loads</div>

        <div class="form-grid">
            <div class="form-row">
                <div class="form-label">Dead Load (kN/m²)</div>
                <div class="form-input">
                    <input type="number" id="dead-load" step="0.01" value="0.1">
                </div>
                <div class="form-label">Live Load - Purlin (kN/m²)</div>
                <div class="form-input">
                    <input type="number" id="live-load-purlin" step="0.01" value="0.57">
                </div>
            </div>

            <div class="form-row">
                <div class="form-label">Live Load - Frame (kN/m²)</div>
                <div class="form-input">
                    <input type="number" id="live-load-frame" step="0.01" value="0.57">
                </div>
                <div class="form-label">Additional Load (kN/m²)</div>
                <div class="form-input">
                    <input type="number" id="additional-load" step="0.01" value="0">
                </div>
            </div>

            <div class="form-row">
                <div class="form-label">Wind Speed (km/h)</div>
                <div class="form-input">
                    <input type="number" id="wind-speed" step="1" value="130">
                </div>
                <div class="form-label">Wind Load (kN/m²)</div>
                <div class="form-input readonly">
                    <input type="text" id="calc-wind-load" readonly>
                </div>
            </div>
        </div>

        <!-- EAVE CONDITIONS -->
        <div class="section-divider">Eave Conditions</div>

        <div class="form-grid">
            <div class="form-row">
                <div class="form-label">Back Eave Condition</div>
                <div class="form-input">
                    <select id="back-eave-condition">
                        <option value="Gutter+Dwnspts">Gutter + Downspouts</option>
                        <option value="Curved">Curved Eave</option>
                        <option value="Curved+VGutter">Curved + Valley Gutter</option>
                        <option value="Eave Trim">Eave Trim Only</option>
                    </select>
                </div>
                <div class="form-label">Front Eave Condition</div>
                <div class="form-input">
                    <select id="front-eave-condition">
                        <option value="Gutter+Dwnspts">Gutter + Downspouts</option>
                        <option value="Curved">Curved Eave</option>
                        <option value="Curved+VGutter">Curved + Valley Gutter</option>
                        <option value="Eave Trim">Eave Trim Only</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- ROOF SHEETING -->
        <div class="section-divider">Roof Sheeting</div>

        <div class="form-grid">
            <div class="form-row">
                <div class="form-label">Panel Profile</div>
                <div class="form-input">
                    <select id="roof-panel-profile">
                        <option value="M45-250">M45-250</option>
                        <option value="M45-150">M45-150</option>
                        <option value="Standing Seam">Standing Seam</option>
                    </select>
                </div>
                <div class="form-label">Top Skin</div>
                <div class="form-input">
                    <select id="roof-top-skin">
                        <option value="S5OW">0.5mm AZ Steel - Off White</option>
                        <option value="S5CT">0.5mm AZ Steel - Custom Color</option>
                        <option value="S7OW">0.7mm AZ Steel - Off White</option>
                        <option value="A7OW">0.7mm Aluminum - Off White</option>
                        <option value="None">None</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-label">Insulation Core</div>
                <div class="form-input">
                    <select id="roof-core">
                        <option value="-">Single Skin (No Core)</option>
                        <option value="Core50BPro">50mm PU - Premium</option>
                        <option value="Core75BPro">75mm PU - Premium</option>
                        <option value="Core100BPro">100mm PU - Premium</option>
                        <option value="Core50BEco">50mm PU - Economy</option>
                        <option value="Core75BEco">75mm PU - Economy</option>
                    </select>
                </div>
                <div class="form-label">Bottom Skin / Liner</div>
                <div class="form-input">
                    <select id="roof-bot-skin">
                        <option value="-">Single Skin (No Liner)</option>
                        <option value="S5OW">0.5mm AZ Steel - Off White</option>
                        <option value="A7MF">0.7mm Aluminum - Mill Finish</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- WALL SHEETING -->
        <div class="section-divider">Wall Sheeting</div>

        <div class="form-grid">
            <div class="form-row">
                <div class="form-label">Top Skin</div>
                <div class="form-input">
                    <select id="wall-top-skin">
                        <option value="S5OW">0.5mm AZ Steel - Off White</option>
                        <option value="S5CT">0.5mm AZ Steel - Custom Color</option>
                        <option value="S7OW">0.7mm AZ Steel - Off White</option>
                        <option value="A7OW">0.7mm Aluminum - Off White</option>
                        <option value="None">None</option>
                    </select>
                </div>
                <div class="form-label">Insulation Core</div>
                <div class="form-input">
                    <select id="wall-core">
                        <option value="-">Single Skin (No Core)</option>
                        <option value="Core50CPro">50mm PU - Premium</option>
                        <option value="Core75CPro">75mm PU - Premium</option>
                        <option value="Core100CPro">100mm PU - Premium</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-label">Bottom Skin / Liner</div>
                <div class="form-input">
                    <select id="wall-bot-skin">
                        <option value="-">Single Skin (No Liner)</option>
                        <option value="S5OW">0.5mm AZ Steel - Off White</option>
                        <option value="A7MF">0.7mm Aluminum - Mill Finish</option>
                    </select>
                </div>
                <div class="form-label">Trim Material</div>
                <div class="form-input">
                    <select id="trim-sizes">
                        <option value="0.5 AZ">0.5mm AZ Steel</option>
                        <option value="0.7 AZ">0.7mm AZ Steel</option>
                        <option value="0.7 AL">0.7mm Aluminum</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- FREIGHT -->
        <div class="section-divider">Freight & Delivery</div>

        <div class="form-grid">
            <div class="form-row">
                <div class="form-label">Freight Destination</div>
                <div class="form-input" style="grid-column: span 3;">
                    <select id="freight-destination">
                        <option value="">-- Select Destination --</option>
                        <option value="Dubai">Dubai</option>
                        <option value="Abu Dhabi">Abu Dhabi</option>
                        <option value="Sharjah">Sharjah</option>
                        <option value="RAK">Ras Al Khaimah</option>
                        <option value="Jeddah">Jeddah</option>
                        <option value="Riyadh">Riyadh</option>
                        <option value="Dammam">Dammam</option>
                        <option value="Doha">Doha</option>
                        <option value="Kuwait City">Kuwait City</option>
                        <option value="Muscat">Muscat</option>
                    </select>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Summary Panel (floating) -->
<div class="summary-panel" id="summary-panel" style="display: none;">
    <h3>Estimate Summary</h3>
    <div class="summary-item">
        <span class="label">Building Area</span>
        <span class="value" id="sum-area">-</span>
    </div>
    <div class="summary-item">
        <span class="label">Roof Area</span>
        <span class="value" id="sum-roof">-</span>
    </div>
    <div class="summary-item">
        <span class="label">Wall Area</span>
        <span class="value" id="sum-wall">-</span>
    </div>
    <div class="summary-item">
        <span class="label">Total Weight</span>
        <span class="value" id="sum-weight">-</span>
    </div>
    <div class="summary-item">
        <span class="label">Item Count</span>
        <span class="value" id="sum-items">-</span>
    </div>
    <div class="summary-item total">
        <span class="label">Total Price</span>
        <span class="value" id="sum-price">-</span>
    </div>
</div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loading-overlay">
    <div class="loading-spinner"></div>
</div>

<!-- ============================================ -->
<!-- MEZZANINE INPUT SECTION (Hidden by default) -->
<!-- ============================================ -->
<div id="mezzanine-section" class="additional-section" style="display: none;">
    <div class="spreadsheet-container">
        <div class="section-divider" style="background: #2e7d32;">Mezzanine Details</div>

        <div class="excel-form">
            <div class="form-grid">
                <div class="form-row">
                    <div class="form-label">Mezzanine Description</div>
                    <div class="form-input" style="grid-column: span 3;">
                        <input type="text" id="mez-description" placeholder="e.g., Office Mezzanine" value="Mezzanine">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-label">Column Spacing (m)</div>
                    <div class="form-input">
                        <input type="text" id="mez-col-spacing" placeholder="e.g., 2@6" value="2@6">
                    </div>
                    <div class="form-label">Beam Spacing (m)</div>
                    <div class="form-input">
                        <input type="text" id="mez-beam-spacing" placeholder="e.g., 3@4" value="3@4">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-label">Joist Spacing (m)</div>
                    <div class="form-input">
                        <input type="text" id="mez-joist-spacing" placeholder="e.g., 1@1.5" value="1@1.5">
                    </div>
                    <div class="form-label">Clear Height (m)</div>
                    <div class="form-input">
                        <input type="number" id="mez-clear-height" step="0.1" value="3.0">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-label">Deck Type</div>
                    <div class="form-input">
                        <select id="mez-deck-type">
                            <option value="Deck-0.75">Deck 0.75mm</option>
                            <option value="Deck-1.00">Deck 1.00mm</option>
                            <option value="Deck-1.25">Deck 1.25mm</option>
                            <option value="Chequered Plate">Chequered Plate</option>
                        </select>
                    </div>
                    <div class="form-label">Double Welded</div>
                    <div class="form-input">
                        <select id="mez-double-welded">
                            <option value="No">No</option>
                            <option value="Yes">Yes</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-label">No. of Stairs</div>
                    <div class="form-input">
                        <input type="number" id="mez-stairs" min="0" value="1">
                    </div>
                    <div class="form-label">Min Plate Thickness (mm)</div>
                    <div class="form-input">
                        <select id="mez-min-thickness">
                            <option value="5">5 mm</option>
                            <option value="6" selected>6 mm</option>
                            <option value="8">8 mm</option>
                            <option value="10">10 mm</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-label">Dead Load (kN/m²)</div>
                    <div class="form-input">
                        <input type="number" id="mez-dead-load" step="0.1" value="0.5">
                    </div>
                    <div class="form-label">Live Load (kN/m²)</div>
                    <div class="form-input">
                        <input type="number" id="mez-live-load" step="0.1" value="5.0">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-label">Additional Load (kN/m²)</div>
                    <div class="form-input">
                        <input type="number" id="mez-additional-load" step="0.1" value="0">
                    </div>
                    <div class="form-label">Calculated Area</div>
                    <div class="form-input readonly">
                        <input type="text" id="mez-calc-area" readonly placeholder="Auto-calculated">
                    </div>
                </div>
            </div>

            <div class="form-actions" style="margin-top: 15px; text-align: right;">
                <button type="button" id="btn-add-mezzanine-item" class="btn btn-primary">Add Mezzanine to Estimate</button>
                <button type="button" id="btn-cancel-mezzanine" class="btn btn-secondary">Cancel</button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- CRANE INPUT SECTION (Hidden by default) -->
<!-- ============================================ -->
<div id="crane-section" class="additional-section" style="display: none;">
    <div class="spreadsheet-container">
        <div class="section-divider" style="background: #c62828;">Crane Runway Details</div>

        <div class="excel-form">
            <div class="form-grid">
                <div class="form-row">
                    <div class="form-label">Crane Description</div>
                    <div class="form-input" style="grid-column: span 3;">
                        <input type="text" id="crane-description" placeholder="e.g., EOT Crane" value="EOT Crane">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-label">Crane Capacity (MT)</div>
                    <div class="form-input">
                        <input type="number" id="crane-capacity" step="0.5" value="5">
                    </div>
                    <div class="form-label">Duty Class</div>
                    <div class="form-input">
                        <select id="crane-duty">
                            <option value="L">Light (L)</option>
                            <option value="M" selected>Medium (M)</option>
                            <option value="H">Heavy (H)</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-label">Rail Centers (m)</div>
                    <div class="form-input">
                        <input type="number" id="crane-rail-centers" step="0.1" value="18">
                    </div>
                    <div class="form-label">Crane Run (m)</div>
                    <div class="form-input">
                        <input type="text" id="crane-run" placeholder="e.g., 6@6 or 36" value="6@6">
                    </div>
                </div>
            </div>

            <div class="form-actions" style="margin-top: 15px; text-align: right;">
                <button type="button" id="btn-add-crane-item" class="btn btn-primary">Add Crane to Estimate</button>
                <button type="button" id="btn-cancel-crane" class="btn btn-secondary">Cancel</button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- PARTITION INPUT SECTION (Hidden by default) -->
<!-- ============================================ -->
<div id="partition-section" class="additional-section" style="display: none;">
    <div class="spreadsheet-container">
        <div class="section-divider" style="background: #7b1fa2;">Partition Wall Details</div>

        <div class="excel-form">
            <div class="form-grid">
                <div class="form-row">
                    <div class="form-label">Partition Description</div>
                    <div class="form-input" style="grid-column: span 3;">
                        <input type="text" id="par-description" placeholder="e.g., Fire Wall" value="Partition Wall">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-label">Column Spacing (m)</div>
                    <div class="form-input">
                        <input type="text" id="par-col-spacing" placeholder="e.g., 4@6" value="4@6">
                    </div>
                    <div class="form-label">Direction</div>
                    <div class="form-input">
                        <select id="par-direction">
                            <option value="Across">Across Building</option>
                            <option value="Along">Along Building</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-label">Wall Height (m)</div>
                    <div class="form-input">
                        <input type="number" id="par-height" step="0.1" value="6">
                    </div>
                    <div class="form-label">Open Height at Bottom (m)</div>
                    <div class="form-input">
                        <input type="number" id="par-open-height" step="0.1" value="0">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-label">Front Sheeting</div>
                    <div class="form-input">
                        <select id="par-front-sheeting">
                            <option value="S5OW">0.5mm AZ Steel - Off White</option>
                            <option value="S7OW">0.7mm AZ Steel - Off White</option>
                            <option value="A7OW">0.7mm Aluminum</option>
                            <option value="None">None</option>
                        </select>
                    </div>
                    <div class="form-label">Back Sheeting</div>
                    <div class="form-input">
                        <select id="par-back-sheeting">
                            <option value="S5OW">0.5mm AZ Steel - Off White</option>
                            <option value="S7OW">0.7mm AZ Steel - Off White</option>
                            <option value="A7OW">0.7mm Aluminum</option>
                            <option value="None">None</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-label">Insulation</div>
                    <div class="form-input">
                        <select id="par-insulation">
                            <option value="None">None</option>
                            <option value="FB50">Fiberglass 50mm</option>
                            <option value="FB75">Fiberglass 75mm</option>
                            <option value="FB100">Fiberglass 100mm</option>
                        </select>
                    </div>
                    <div class="form-label">Wind Speed (km/h)</div>
                    <div class="form-input">
                        <input type="number" id="par-wind-speed" step="1" value="130">
                    </div>
                </div>
            </div>

            <div class="form-actions" style="margin-top: 15px; text-align: right;">
                <button type="button" id="btn-add-partition-item" class="btn btn-primary">Add Partition to Estimate</button>
                <button type="button" id="btn-cancel-partition" class="btn btn-secondary">Cancel</button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- CANOPY INPUT SECTION (Hidden by default) -->
<!-- ============================================ -->
<div id="canopy-section" class="additional-section" style="display: none;">
    <div class="spreadsheet-container">
        <div class="section-divider" style="background: #00838f;">Canopy / Roof Extension Details</div>

        <div class="excel-form">
            <div class="form-grid">
                <div class="form-row">
                    <div class="form-label">Description</div>
                    <div class="form-input" style="grid-column: span 3;">
                        <input type="text" id="can-description" placeholder="e.g., Loading Canopy" value="Canopy">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-label">Type</div>
                    <div class="form-input">
                        <select id="can-type">
                            <option value="Canopy">Canopy (Free-standing)</option>
                            <option value="Roof Extension">Roof Extension</option>
                            <option value="Fascia">Fascia</option>
                        </select>
                    </div>
                    <div class="form-label">Location</div>
                    <div class="form-input">
                        <select id="can-location">
                            <option value="Front Sidewall">Front Sidewall</option>
                            <option value="Back Sidewall">Back Sidewall</option>
                            <option value="Left Endwall">Left Endwall</option>
                            <option value="Right Endwall">Right Endwall</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-label">Column/Bay Spacing (m)</div>
                    <div class="form-input">
                        <input type="text" id="can-col-spacing" placeholder="e.g., 6@6" value="6@6">
                    </div>
                    <div class="form-label">Projection Width (m)</div>
                    <div class="form-input">
                        <input type="number" id="can-width" step="0.1" value="3">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-label">Column Height (m)</div>
                    <div class="form-input">
                        <input type="number" id="can-height" step="0.1" value="3">
                    </div>
                    <div class="form-label">Live Load (kN/m²)</div>
                    <div class="form-input">
                        <input type="number" id="can-live-load" step="0.01" value="0.57">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-label">Roof Sheeting</div>
                    <div class="form-input">
                        <select id="can-roof-sheeting">
                            <option value="S5OW">0.5mm AZ Steel - Off White</option>
                            <option value="S7OW">0.7mm AZ Steel - Off White</option>
                            <option value="A7OW">0.7mm Aluminum</option>
                            <option value="None">None</option>
                        </select>
                    </div>
                    <div class="form-label">Drainage</div>
                    <div class="form-input">
                        <select id="can-drainage">
                            <option value="Gutter+Dwnspts">Gutter + Downspouts</option>
                            <option value="Eave Trim">Eave Trim Only</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-label">Soffit</div>
                    <div class="form-input">
                        <select id="can-soffit">
                            <option value="None">None</option>
                            <option value="S5OW">0.5mm AZ Steel - Off White</option>
                            <option value="A7MF">0.7mm Aluminum - Mill Finish</option>
                        </select>
                    </div>
                    <div class="form-label">Wind Speed (km/h)</div>
                    <div class="form-input">
                        <input type="number" id="can-wind-speed" step="1" value="130">
                    </div>
                </div>
            </div>

            <div class="form-actions" style="margin-top: 15px; text-align: right;">
                <button type="button" id="btn-add-canopy-item" class="btn btn-primary">Add Canopy to Estimate</button>
                <button type="button" id="btn-cancel-canopy" class="btn btn-secondary">Cancel</button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MONITOR INPUT SECTION (Hidden by default) -->
<!-- ============================================ -->
<div id="monitor-section" class="additional-section" style="display: none;">
    <div class="spreadsheet-container">
        <div class="section-divider" style="background: #5d4037;">Roof Monitor Details</div>

        <div class="excel-form">
            <div class="form-grid">
                <div class="form-row">
                    <div class="form-label">Monitor Description</div>
                    <div class="form-input" style="grid-column: span 3;">
                        <input type="text" id="mon-description" placeholder="e.g., Natural Ventilation Monitor" value="Roof Monitor">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-label">Monitor Type</div>
                    <div class="form-input">
                        <select id="mon-type">
                            <option value="Curve-CF">Curved Eave - Cold Formed</option>
                            <option value="Straight-CF">Straight Eave - Cold Formed</option>
                            <option value="Curve-HR">Curved Eave - Hot Rolled</option>
                            <option value="Straight-HR">Straight Eave - Hot Rolled</option>
                        </select>
                    </div>
                    <div class="form-label">Bay Spacing (m)</div>
                    <div class="form-input">
                        <input type="text" id="mon-bay-spacing" placeholder="e.g., 6@6" value="6@6">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-label">Opening/Throat Width (mm)</div>
                    <div class="form-input">
                        <input type="number" id="mon-opening-width" step="100" value="1000">
                    </div>
                    <div class="form-label">Monitor Length (m)</div>
                    <div class="form-input">
                        <input type="number" id="mon-length" step="0.1" value="36">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-label">Roof Sheeting</div>
                    <div class="form-input">
                        <select id="mon-roof-sheeting">
                            <option value="S5OW">0.5mm AZ Steel - Off White</option>
                            <option value="S7OW">0.7mm AZ Steel - Off White</option>
                            <option value="A7OW">0.7mm Aluminum</option>
                        </select>
                    </div>
                    <div class="form-label">Wall Sheeting</div>
                    <div class="form-input">
                        <select id="mon-wall-sheeting">
                            <option value="S5OW">0.5mm AZ Steel - Off White</option>
                            <option value="S7OW">0.7mm AZ Steel - Off White</option>
                            <option value="A7OW">0.7mm Aluminum</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-actions" style="margin-top: 15px; text-align: right;">
                <button type="button" id="btn-add-monitor-item" class="btn btn-primary">Add Monitor to Estimate</button>
                <button type="button" id="btn-cancel-monitor" class="btn btn-secondary">Cancel</button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- ACCESSORY INPUT SECTION (Hidden by default) -->
<!-- ============================================ -->
<div id="accessory-section" class="additional-section" style="display: none;">
    <div class="spreadsheet-container">
        <div class="section-divider" style="background: #f57c00;">Accessories</div>

        <div class="excel-form">
            <div class="form-grid">
                <div class="form-row">
                    <div class="form-label">Accessory Description</div>
                    <div class="form-input" style="grid-column: span 3;">
                        <input type="text" id="acc-description" placeholder="e.g., Doors and Skylights" value="Accessories">
                    </div>
                </div>

                <!-- Accessory Item 1 -->
                <div class="form-row">
                    <div class="form-label">Item 1</div>
                    <div class="form-input" style="grid-column: span 2;">
                        <select id="acc-item-1" class="acc-item-select">
                            <option value="">-- Select Accessory --</option>
                            <optgroup label="Skylights">
                                <option value="Skylight 3250mm (GRP,Single Skin )">Skylight 3250mm (GRP, Single Skin)</option>
                                <option value="Skylight 3250mm (GRP, Double Skin 35 mm thk)">Skylight 3250mm (GRP, Double Skin 35mm)</option>
                                <option value="Skylight 3250mm (GRP, Double Skin 50 mm thk)">Skylight 3250mm (GRP, Double Skin 50mm)</option>
                                <option value="Skylight 3250mm (GRP, Double Skin 75 mm thk)">Skylight 3250mm (GRP, Double Skin 75mm)</option>
                                <option value="Skylight 3250mm (GRP, Double Skin 100 mm thk)">Skylight 3250mm (GRP, Double Skin 100mm)</option>
                            </optgroup>
                            <optgroup label="Personnel Doors">
                                <option value="Personnel Door (900x2100)">Personnel Door (900x2100)</option>
                                <option value="Personnel Door (1200x2100)">Personnel Door (1200x2100)</option>
                                <option value="Personnel Door Double (1800x2100)">Personnel Door Double (1800x2100)</option>
                            </optgroup>
                            <optgroup label="Sliding Doors">
                                <option value="Slide door 3mX3m Steel Only with Framed Opening (Top Sliding)">Sliding Door 3m x 3m (Top)</option>
                                <option value="Slide door 4mX4m Steel Only with Framed Opening (Top Sliding)">Sliding Door 4m x 4m (Top)</option>
                                <option value="Slide door 5mX5m Steel Only with Framed Opening (Top Sliding)">Sliding Door 5m x 5m (Top)</option>
                                <option value="Slide door 6mX6m Steel Only with Framed Opening (Top Sliding)">Sliding Door 6m x 6m (Top)</option>
                                <option value="Slide door 3mX3m Steel Only with Framed Opening (Dual Sliding)">Sliding Door 3m x 3m (Dual)</option>
                                <option value="Slide door 4mX4m Steel Only with Framed Opening (Dual Sliding)">Sliding Door 4m x 4m (Dual)</option>
                                <option value="Slide door 5mX5m Steel Only with Framed Opening (Dual Sliding)">Sliding Door 5m x 5m (Dual)</option>
                                <option value="Slide door 6mX6m Steel Only with Framed Opening (Dual Sliding)">Sliding Door 6m x 6m (Dual)</option>
                            </optgroup>
                            <optgroup label="Louvers">
                                <option value="Louver 600x600">Louver 600x600</option>
                                <option value="Louver 900x900">Louver 900x900</option>
                                <option value="Louver 1200x900">Louver 1200x900</option>
                            </optgroup>
                            <optgroup label="Ventilators">
                                <option value="Ridge Ventilator">Ridge Ventilator</option>
                                <option value="Turbo Ventilator">Turbo Ventilator</option>
                            </optgroup>
                        </select>
                    </div>
                    <div class="form-input">
                        <input type="number" id="acc-qty-1" placeholder="Qty" min="0" value="0">
                    </div>
                </div>

                <!-- Accessory Item 2 -->
                <div class="form-row">
                    <div class="form-label">Item 2</div>
                    <div class="form-input" style="grid-column: span 2;">
                        <select id="acc-item-2" class="acc-item-select">
                            <option value="">-- Select Accessory --</option>
                            <optgroup label="Skylights">
                                <option value="Skylight 3250mm (GRP,Single Skin )">Skylight 3250mm (GRP, Single Skin)</option>
                                <option value="Skylight 3250mm (GRP, Double Skin 35 mm thk)">Skylight 3250mm (GRP, Double Skin 35mm)</option>
                                <option value="Skylight 3250mm (GRP, Double Skin 50 mm thk)">Skylight 3250mm (GRP, Double Skin 50mm)</option>
                            </optgroup>
                            <optgroup label="Personnel Doors">
                                <option value="Personnel Door (900x2100)">Personnel Door (900x2100)</option>
                                <option value="Personnel Door (1200x2100)">Personnel Door (1200x2100)</option>
                                <option value="Personnel Door Double (1800x2100)">Personnel Door Double (1800x2100)</option>
                            </optgroup>
                            <optgroup label="Sliding Doors">
                                <option value="Slide door 3mX3m Steel Only with Framed Opening (Top Sliding)">Sliding Door 3m x 3m (Top)</option>
                                <option value="Slide door 4mX4m Steel Only with Framed Opening (Top Sliding)">Sliding Door 4m x 4m (Top)</option>
                                <option value="Slide door 5mX5m Steel Only with Framed Opening (Top Sliding)">Sliding Door 5m x 5m (Top)</option>
                                <option value="Slide door 6mX6m Steel Only with Framed Opening (Top Sliding)">Sliding Door 6m x 6m (Top)</option>
                            </optgroup>
                            <optgroup label="Louvers">
                                <option value="Louver 600x600">Louver 600x600</option>
                                <option value="Louver 900x900">Louver 900x900</option>
                                <option value="Louver 1200x900">Louver 1200x900</option>
                            </optgroup>
                        </select>
                    </div>
                    <div class="form-input">
                        <input type="number" id="acc-qty-2" placeholder="Qty" min="0" value="0">
                    </div>
                </div>

                <!-- Accessory Item 3 -->
                <div class="form-row">
                    <div class="form-label">Item 3</div>
                    <div class="form-input" style="grid-column: span 2;">
                        <select id="acc-item-3" class="acc-item-select">
                            <option value="">-- Select Accessory --</option>
                            <optgroup label="Skylights">
                                <option value="Skylight 3250mm (GRP,Single Skin )">Skylight 3250mm (GRP, Single Skin)</option>
                                <option value="Skylight 3250mm (GRP, Double Skin 35 mm thk)">Skylight 3250mm (GRP, Double Skin 35mm)</option>
                            </optgroup>
                            <optgroup label="Personnel Doors">
                                <option value="Personnel Door (900x2100)">Personnel Door (900x2100)</option>
                                <option value="Personnel Door (1200x2100)">Personnel Door (1200x2100)</option>
                            </optgroup>
                            <optgroup label="Sliding Doors">
                                <option value="Slide door 3mX3m Steel Only with Framed Opening (Top Sliding)">Sliding Door 3m x 3m (Top)</option>
                                <option value="Slide door 4mX4m Steel Only with Framed Opening (Top Sliding)">Sliding Door 4m x 4m (Top)</option>
                            </optgroup>
                            <optgroup label="Louvers">
                                <option value="Louver 600x600">Louver 600x600</option>
                                <option value="Louver 900x900">Louver 900x900</option>
                            </optgroup>
                        </select>
                    </div>
                    <div class="form-input">
                        <input type="number" id="acc-qty-3" placeholder="Qty" min="0" value="0">
                    </div>
                </div>

                <!-- Accessory Item 4 -->
                <div class="form-row">
                    <div class="form-label">Item 4</div>
                    <div class="form-input" style="grid-column: span 2;">
                        <select id="acc-item-4" class="acc-item-select">
                            <option value="">-- Select Accessory --</option>
                            <optgroup label="Skylights">
                                <option value="Skylight 3250mm (GRP,Single Skin )">Skylight 3250mm (GRP, Single Skin)</option>
                            </optgroup>
                            <optgroup label="Personnel Doors">
                                <option value="Personnel Door (900x2100)">Personnel Door (900x2100)</option>
                            </optgroup>
                            <optgroup label="Sliding Doors">
                                <option value="Slide door 3mX3m Steel Only with Framed Opening (Top Sliding)">Sliding Door 3m x 3m (Top)</option>
                            </optgroup>
                            <optgroup label="Louvers">
                                <option value="Louver 600x600">Louver 600x600</option>
                            </optgroup>
                        </select>
                    </div>
                    <div class="form-input">
                        <input type="number" id="acc-qty-4" placeholder="Qty" min="0" value="0">
                    </div>
                </div>

                <!-- Accessory Item 5 -->
                <div class="form-row">
                    <div class="form-label">Item 5</div>
                    <div class="form-input" style="grid-column: span 2;">
                        <select id="acc-item-5" class="acc-item-select">
                            <option value="">-- Select Accessory --</option>
                            <optgroup label="Skylights">
                                <option value="Skylight 3250mm (GRP,Single Skin )">Skylight 3250mm (GRP, Single Skin)</option>
                            </optgroup>
                            <optgroup label="Personnel Doors">
                                <option value="Personnel Door (900x2100)">Personnel Door (900x2100)</option>
                            </optgroup>
                            <optgroup label="Sliding Doors">
                                <option value="Slide door 3mX3m Steel Only with Framed Opening (Top Sliding)">Sliding Door 3m x 3m (Top)</option>
                            </optgroup>
                        </select>
                    </div>
                    <div class="form-input">
                        <input type="number" id="acc-qty-5" placeholder="Qty" min="0" value="0">
                    </div>
                </div>
            </div>

            <div class="form-actions" style="margin-top: 15px; text-align: right;">
                <button type="button" id="btn-add-accessory-item" class="btn btn-primary">Add Accessories to Estimate</button>
                <button type="button" id="btn-cancel-accessory" class="btn btn-secondary">Cancel</button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- LINER INPUT SECTION (Hidden by default) -->
<!-- ============================================ -->
<div id="liner-section" class="additional-section" style="display: none;">
    <div class="spreadsheet-container">
        <div class="section-divider" style="background: #00838f;">Liner Details</div>

        <div class="excel-form">
            <div class="form-grid">
                <div class="form-row">
                    <div class="form-label">Liner Description</div>
                    <div class="form-input" style="grid-column: span 3;">
                        <input type="text" id="liner-description" placeholder="e.g., Interior Liner" value="Liner">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-label">Liner Type</div>
                    <div class="form-input">
                        <select id="liner-type">
                            <option value="Both">Roof + Wall Liner</option>
                            <option value="Roof Liner">Roof Liner Only</option>
                            <option value="Wall Liner">Wall Liner Only</option>
                        </select>
                    </div>
                    <div class="form-label">Roof Liner Material</div>
                    <div class="form-input">
                        <select id="liner-roof-material">
                            <option value="S5OW">0.5mm AZ Steel - Off White</option>
                            <option value="S5GZ">0.5mm Galvanized Steel</option>
                            <option value="S7OW">0.7mm AZ Steel - Off White</option>
                            <option value="A5MF">0.5mm Aluminum - Mill Finish</option>
                            <option value="A7MF">0.7mm Aluminum - Mill Finish</option>
                            <option value="PUS35">35mm PU Panel - Steel</option>
                            <option value="PUS50">50mm PU Panel - Steel</option>
                            <option value="None">None</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-label">Wall Liner Material</div>
                    <div class="form-input">
                        <select id="liner-wall-material">
                            <option value="S5OW">0.5mm AZ Steel - Off White</option>
                            <option value="S5GZ">0.5mm Galvanized Steel</option>
                            <option value="S7OW">0.7mm AZ Steel - Off White</option>
                            <option value="A5MF">0.5mm Aluminum - Mill Finish</option>
                            <option value="A7MF">0.7mm Aluminum - Mill Finish</option>
                            <option value="PUS35">35mm PU Panel - Steel</option>
                            <option value="PUS50">50mm PU Panel - Steel</option>
                            <option value="None">None</option>
                        </select>
                    </div>
                    <div class="form-label">Building Dimensions</div>
                    <div class="form-input readonly">
                        <input type="text" id="liner-dimensions" readonly placeholder="From main building">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-label">Roof Area (m²)</div>
                    <div class="form-input">
                        <input type="number" id="liner-roof-area" step="0.1" placeholder="Auto or manual" value="0">
                        <small style="color: #666; font-size: 10px;">0 = Auto-calculate</small>
                    </div>
                    <div class="form-label">Wall Area (m²)</div>
                    <div class="form-input">
                        <input type="number" id="liner-wall-area" step="0.1" placeholder="Auto or manual" value="0">
                        <small style="color: #666; font-size: 10px;">0 = Auto-calculate</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-label">Roof Openings (m²)</div>
                    <div class="form-input">
                        <input type="number" id="liner-roof-openings" step="0.1" value="0">
                        <small style="color: #666; font-size: 10px;">Skylights, etc.</small>
                    </div>
                    <div class="form-label">Wall Openings (m²)</div>
                    <div class="form-input">
                        <input type="number" id="liner-wall-openings" step="0.1" value="0">
                        <small style="color: #666; font-size: 10px;">Doors, windows, etc.</small>
                    </div>
                </div>
            </div>

            <div class="form-actions" style="margin-top: 15px; text-align: right;">
                <button type="button" id="btn-add-liner-item" class="btn btn-primary">Add Liner to Estimate</button>
                <button type="button" id="btn-cancel-liner" class="btn btn-secondary">Cancel</button>
            </div>
        </div>
    </div>
</div>
