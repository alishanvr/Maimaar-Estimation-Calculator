<?php
/**
 * QuickEst - Main Calculation Engine
 *
 * This is the core calculation engine that replicates the VBA AddArea_Click() logic
 * and other calculation procedures from the Excel file.
 */

namespace QuickEst\Services;

use QuickEst\Models\Building;
use QuickEst\Models\BillOfMaterials;
use QuickEst\Models\QuoteItem;
use QuickEst\Helpers\ListParser;
use QuickEst\Database\ProductLookup;

class CalculationEngine {

    private Building $building;
    private BillOfMaterials $bom;

    // Calculated intermediate values
    private array $spans = [];
    private array $bays = [];
    private array $slopes = [];
    private array $expandedSpans = [];
    private array $expandedBays = [];

    private float $buildingWidth = 0;
    private float $buildingLength = 0;
    private int $nSpans = 0;
    private int $nBays = 0;
    private float $avgSpan = 0;
    private float $avgBay = 0;
    private float $minSlope = 0;
    private float $avgSlope = 0;

    private float $backEaveHeight = 0;
    private float $frontEaveHeight = 0;
    private float $peakHeight = 0;
    private float $rafterLength = 0;
    private float $slopeFactor = 0;

    // Frame calculation results
    private float $frameWeight = 0;
    private int $nFrames = 0;
    private array $frameWeights = [];

    // Area calculations
    private float $roofArea = 0;
    private float $backWallArea = 0;
    private float $frontWallArea = 0;
    private float $leftEndwallArea = 0;
    private float $rightEndwallArea = 0;

    // Load calculations
    private float $wind = 0;
    private float $loadP = 0;
    private float $loadG = 0;
    private float $mfLoad = 0;

    // Counters for hardware
    private int $purlinClips = 0;
    private int $girtClips = 0;
    private int $bracingCables = 0;
    private int $bracingRods = 0;

    /**
     * Calculate the building estimate
     */
    public function calculate(Building $building): BillOfMaterials {
        $this->building = $building;
        $this->bom = new BillOfMaterials();

        // Parse input dimensions
        $this->parseDimensions();

        // Calculate derived values
        $this->calculateDerivedValues();

        // Calculate loads
        $this->calculateLoads();

        // Build the Bill of Materials
        $this->calculateMainFrames();
        $this->calculatePurlins();
        $this->calculateGirts();
        $this->calculateBracing();
        $this->calculateRoofSheeting();
        $this->calculateWallSheeting();
        $this->calculateEndwalls();
        $this->calculateTrims();
        $this->calculateFasteners();
        $this->calculateAccessories();
        $this->calculateHandlingPacking();

        // Update building with calculated values
        $this->updateBuildingCalculations();

        return $this->bom;
    }

    /**
     * Parse dimension strings into arrays
     */
    private function parseDimensions(): void {
        // Parse spans (e.g., "2@24" = 2 spans of 24m)
        $this->spans = ListParser::parseList($this->building->spans);
        $this->expandedSpans = ListParser::expandList($this->spans);
        $this->nSpans = count($this->expandedSpans);

        // Parse bays (e.g., "6@6" = 6 bays of 6m)
        $this->bays = ListParser::parseList($this->building->bays);
        $this->expandedBays = ListParser::expandList($this->bays);
        $this->nBays = count($this->expandedBays);

        // Parse slopes
        if (!empty($this->building->slopes)) {
            $this->slopes = ListParser::parseList($this->building->slopes);
        } else {
            // Default slope of 0.1 (1:10)
            $this->slopes = [['count' => 1, 'value' => 0.1]];
        }

        // Calculate totals
        $this->buildingWidth = ListParser::getTotalSum($this->spans);
        $this->buildingLength = ListParser::getTotalSum($this->bays);
        $this->avgSpan = $this->buildingWidth / max(1, $this->nSpans);
        $this->avgBay = $this->buildingLength / max(1, $this->nBays);

        // Get minimum slope
        $this->minSlope = PHP_FLOAT_MAX;
        foreach ($this->slopes as $slope) {
            if ($slope['value'] < $this->minSlope) {
                $this->minSlope = $slope['value'];
            }
        }
        if ($this->minSlope === PHP_FLOAT_MAX) {
            $this->minSlope = 0.1;
        }
        $this->avgSlope = $this->minSlope;
    }

    /**
     * Calculate derived values like heights, rafter length, etc.
     */
    private function calculateDerivedValues(): void {
        $this->backEaveHeight = $this->building->backEaveHeight;
        $this->frontEaveHeight = $this->building->frontEaveHeight;

        // Calculate slope factor
        $this->slopeFactor = sqrt(1 + pow($this->avgSlope, 2));

        // Calculate rafter length (half width * slope factor for symmetric roof)
        $halfWidth = $this->buildingWidth / 2;
        $this->rafterLength = $halfWidth * $this->slopeFactor;

        // Calculate peak height
        $this->peakHeight = max($this->backEaveHeight, $this->frontEaveHeight) +
                           ($halfWidth * $this->avgSlope);

        // Number of frames = number of bays + 1
        $this->nFrames = $this->nBays + 1;
    }

    /**
     * Calculate loads
     */
    private function calculateLoads(): void {
        // Wind load: V^2 / 20000
        $this->wind = pow($this->building->windSpeed, 2) / 20000;

        // Purlin load
        $this->loadP = $this->building->deadLoad + $this->building->liveLoadPurlin +
                       $this->building->additionalLoad;

        // Girt load (wind dominant)
        $this->loadG = $this->wind;

        // Main frame load
        $this->mfLoad = $this->building->deadLoad + $this->building->liveLoadFrame +
                        $this->building->additionalLoad;
    }

    /**
     * Calculate main frames - Replicates core logic from AddArea_Click()
     *
     * Formula: wplm = (0.1 * MFLoad * TrBay + 0.3) * (2 * Span - 9)
     */
    private function calculateMainFrames(): void {
        $salesCode = 1;
        $description = "Main Frames ({$this->nFrames} Nos)";

        // Calculate minimum weight per linear meter based on plate thickness
        $minThick = $this->building->minThickness;
        $mwplm = sqrt($minThick / 3.5) * 18.5;

        $this->frameWeight = 0;
        $this->frameWeights = [];
        $connectionPlates = 0;

        // Calculate for each span and frame
        for ($i = 0; $i < $this->nSpans; $i++) {
            $span = $this->expandedSpans[$i];

            // Calculate tributary bay width for each frame
            for ($j = 0; $j <= $this->nBays; $j++) {
                // Tributary bay = average of adjacent bays
                $trBay = 0;
                if ($j > 0) {
                    $trBay += $this->expandedBays[$j - 1] / 2;
                }
                if ($j < $this->nBays) {
                    $trBay += $this->expandedBays[$j] / 2;
                }

                // Endwall frames have smaller load
                if ($j === 0 || $j === $this->nBays) {
                    $trBay *= 0.6; // 60% for end frames
                }

                // Calculate weight per linear meter
                // Formula from VBA: wplm = (0.1 * MFLoad * TrBay + 0.3) * (2 * Span - 9)
                $wplm = (0.1 * $this->mfLoad * $trBay + 0.3) * (2 * $span - 9);

                // Apply minimum weight constraint
                if ($wplm < $mwplm) {
                    $wplm = $mwplm;
                }

                // Frame weight for this position
                $frameWt = $span * $wplm;
                $this->frameWeight += $frameWt;

                $this->frameWeights[] = [
                    'span' => $span,
                    'frame' => $j,
                    'trBay' => $trBay,
                    'wplm' => $wplm,
                    'weight' => $frameWt
                ];
            }
        }

        // Average frame weight (accounting for double counting interior frames)
        $avgFrameWt = $this->frameWeight / $this->nFrames;

        // Add main frame items to BOM
        $this->bom->addCode($description, '-', $salesCode, '', '');

        // Built-up sections for frames
        if ($this->building->doubleWelded === 'Yes') {
            $this->bom->addCode("Rafters & Columns (Double Welded)", 'BU', $salesCode, 1, $this->frameWeight);
            $this->bom->addCode('', 'DSW', $salesCode, 1, $this->frameWeight);
        } else {
            $this->bom->addCode("Rafters & Columns", 'BU', $salesCode, 1, $this->frameWeight);
        }

        // Connection plates (percentage of frame weight)
        $connPlateWeight = $this->frameWeight * 0.12; // ~12% for connections
        $this->bom->addCode('', 'ConPlates', $salesCode, 1, $connPlateWeight);

        // Anchor bolts based on base type
        $this->calculateAnchorBolts($salesCode);

        // Peak connections
        $peakConnQty = $this->nFrames * $this->nSpans;
        $this->bom->addCode('', 'HRB30', $salesCode, 1, $peakConnQty * 12); // Peak bolts

        // Add separator
        $this->bom->addCode('', '-', $salesCode, '', '');
    }

    /**
     * Calculate anchor bolts based on base type and building width
     */
    private function calculateAnchorBolts(int $salesCode): void {
        $nColumns = $this->nFrames * ($this->nSpans + 1);

        if ($this->building->baseType === 'Fixed Base') {
            // Fixed base configuration from FixedBaseType() function
            $baseInfo = $this->getFixedBaseType($this->buildingWidth);

            $connType = $baseInfo['connType'];
            $nBolts = $baseInfo['nBolts'];
            $boltDia = $baseInfo['boltDia'];

            // Fixed base connection plates
            $this->bom->addCode('', "FC{$connType}", $salesCode, 1, $nColumns);

            // Anchor bolts
            $abCode = "AB{$boltDia}";
            $this->bom->addCode('', $abCode, $salesCode, 1, $nBolts * $nColumns);
        } else {
            // Pinned base
            $connType = $this->getConnectionType($this->frameWeight / $this->nFrames);
            $abCode = "AB{$connType}";

            $this->bom->addCode('', 'PC1', $salesCode, 1, $nColumns); // Pinned connection
            $this->bom->addCode('', $abCode, $salesCode, 1, 4 * $nColumns);
        }
    }

    /**
     * Get connection type based on weight per meter
     * Replicates VBA ConType() function
     */
    private function getConnectionType(float $wplm): int {
        if ($wplm <= 20) return 16;
        if ($wplm < 40) return 20;
        if ($wplm < 80) return 24;
        if ($wplm < 120) return 30;
        return 36;
    }

    /**
     * Get fixed base type based on building width
     * Replicates VBA FixedBaseType() function
     */
    private function getFixedBaseType(float $buildingWidth): array {
        if ($buildingWidth <= 15) {
            return ['connType' => 2, 'nBolts' => 8, 'boltDia' => 20];
        } elseif ($buildingWidth <= 25) {
            return ['connType' => 3, 'nBolts' => 8, 'boltDia' => 24];
        } elseif ($buildingWidth <= 35) {
            return ['connType' => 3, 'nBolts' => 16, 'boltDia' => 24];
        } elseif ($buildingWidth <= 45) {
            return ['connType' => 4, 'nBolts' => 16, 'boltDia' => 30];
        } elseif ($buildingWidth <= 50) {
            return ['connType' => 5, 'nBolts' => 16, 'boltDia' => 36];
        } elseif ($buildingWidth <= 60) {
            return ['connType' => 4, 'nBolts' => 32, 'boltDia' => 30];
        } else {
            return ['connType' => 5, 'nBolts' => 32, 'boltDia' => 36];
        }
    }

    /**
     * Calculate purlins
     */
    private function calculatePurlins(): void {
        $salesCode = 1;
        $description = "Roof Purlins";

        $this->bom->addCode($description, '-', $salesCode, '', '');

        // Calculate purlin spacing (typically 1.5-2.0m)
        $purlinSpacing = 1.5; // Default spacing

        // Calculate number of purlin lines per span
        $totalPurlinLines = 0;
        $purlinLength = 0;
        $this->purlinClips = 0;

        foreach ($this->spans as $span) {
            $nPurlins = ceil($span['value'] / $purlinSpacing) + 1;
            $totalPurlinLines += $nPurlins * $span['count'];
        }

        // Total purlin length (across all bays)
        $purlinLength = $totalPurlinLines * $this->buildingLength;

        // Calculate purlin design index
        // PDIndex = 1.25 * LoadP * BaySpacing^2
        foreach ($this->bays as $bay) {
            $pdIndex = 1.25 * $this->loadP * pow($bay['value'], 2);
            $purlinCode = ProductLookup::getPurlinCode($pdIndex);

            $qty = $totalPurlinLines * $bay['count'];
            $size = $bay['value'];

            $this->bom->addCode('', $purlinCode, $salesCode, $size, $qty);
            $this->purlinClips += $qty * 2; // 2 clips per purlin
        }

        // Purlin clips
        $this->bom->addCode('', 'CFClip', $salesCode, 1, $this->purlinClips);
        $this->bom->addCode('', 'HSB12', $salesCode, 1, $this->purlinClips * 3);

        // Sag rods
        $sagRods = $this->calculateSagRods($this->buildingLength, $this->avgBay);
        if ($sagRods > 0) {
            $this->bom->addCode('', 'SR12', $salesCode, 1, $sagRods);
        }

        $this->bom->addCode('', '-', $salesCode, '', '');
    }

    /**
     * Calculate number of sag rods needed
     */
    private function calculateSagRods(float $length, float $avgBay): int {
        if ($avgBay > 7.5) {
            // Need sag rods for long spans
            return ceil($length / 3) * 2;
        }
        return 0;
    }

    /**
     * Calculate wall girts
     */
    private function calculateGirts(): void {
        $salesCode = 1;
        $description = "Wall Girts";

        $this->bom->addCode($description, '-', $salesCode, '', '');

        // Girt spacing (typically 1.8-2.0m)
        $girtSpacing = 1.8;

        // Calculate average height for girt count
        $avgHeight = ($this->backEaveHeight + $this->frontEaveHeight) / 2;

        // Number of girt lines
        $nGirtLines = ceil($avgHeight / $girtSpacing);

        // Back wall girts
        $backWallGirtQty = $nGirtLines * $this->nBays;
        $pdIndex = 2 * $this->wind * pow($this->avgBay, 2);
        $girtCode = ProductLookup::getGirtCode($pdIndex);

        $this->bom->addCode("Back Wall Girts", $girtCode, $salesCode, $this->avgBay, $backWallGirtQty);
        $this->girtClips += $backWallGirtQty * 2;

        // Front wall girts (same as back for symmetric building)
        $this->bom->addCode("Front Wall Girts", $girtCode, $salesCode, $this->avgBay, $backWallGirtQty);
        $this->girtClips += $backWallGirtQty * 2;

        // Girt clips and bolts
        $this->bom->addCode('', 'CFClip', $salesCode, 1, $this->girtClips);
        $this->bom->addCode('', 'HSB12', $salesCode, 1, $this->girtClips * 3);

        // Base angles
        $baseAngleLength = 2 * $this->buildingLength;
        $this->bom->addCode('', 'Bang', $salesCode, 1, $baseAngleLength);

        $this->bom->addCode('', '-', $salesCode, '', '');
    }

    /**
     * Calculate bracing
     */
    private function calculateBracing(): void {
        $salesCode = 1;
        $description = "Bracing";

        $this->bom->addCode($description, '-', $salesCode, '', '');

        $bracingType = $this->building->bracingType;

        // Calculate bracing length based on diagonal
        $roofBracingDiag = sqrt(pow($this->avgBay, 2) + pow($this->rafterLength, 2));
        $wallBracingDiag = sqrt(pow($this->avgBay, 2) + pow($this->backEaveHeight, 2));

        // Number of bracing sets (typically 2 sets per building)
        $nBracingSets = 2;

        if ($bracingType === 'Cables') {
            // Roof bracing cables (X pattern = 4 cables per set)
            $roofCableQty = $nBracingSets * 4;
            $this->bom->addCode("Roof Bracing Cables", 'CBC', $salesCode, $roofBracingDiag, $roofCableQty);

            // Wall bracing cables
            $wallCableQty = $nBracingSets * 4;
            $this->bom->addCode("Wall Bracing Cables", 'CBC', $salesCode, $wallBracingDiag, $wallCableQty);

            // Bracing hardware
            $this->bom->addCode('', 'TBCon', $salesCode, 1, ($roofCableQty + $wallCableQty) * 2);

        } elseif ($bracingType === 'Rods') {
            // Rod bracing
            $roofRodQty = $nBracingSets * 4;
            $this->bom->addCode("Roof Bracing Rods", 'BR12', $salesCode, $roofBracingDiag, $roofRodQty);

            $wallRodQty = $nBracingSets * 4;
            $this->bom->addCode("Wall Bracing Rods", 'BR12', $salesCode, $wallBracingDiag, $wallRodQty);

        } else {
            // Angle bracing
            $this->bom->addCode("Roof Bracing Angles", 'BA', $salesCode, $roofBracingDiag, $nBracingSets * 4);
            $this->bom->addCode("Wall Bracing Angles", 'BA', $salesCode, $wallBracingDiag, $nBracingSets * 4);
        }

        // Flange braces
        $flangeBraceQty = $this->nFrames * $this->nSpans * 4;
        $this->bom->addCode('', 'FB', $salesCode, 1, $flangeBraceQty);

        $this->bom->addCode('', '-', $salesCode, '', '');
    }

    /**
     * Calculate roof sheeting
     */
    private function calculateRoofSheeting(): void {
        $salesCode = 1;
        $description = "Roof Sheeting";

        $this->bom->addCode($description, '-', $salesCode, '', '');

        // Calculate roof area with 2% waste
        $this->roofArea = 1.02 * $this->rafterLength * 2 * $this->buildingLength;

        // Adjust for panel profile coverage
        $panelProfile = $this->building->roofPanelProfile;
        $coverageFactor = ($panelProfile === 'M45-250') ? 1.0 : 0.9;
        $actualArea = $this->roofArea / $coverageFactor;

        // Top skin
        $topSkinCode = $this->building->roofTopSkin;
        if ($topSkinCode !== 'None' && !empty($topSkinCode)) {
            $this->bom->addCode("Roof Top Skin", $topSkinCode, $salesCode, 1, $actualArea);
        }

        // Core (if sandwich panel)
        $coreCode = $this->building->roofCore;
        if ($coreCode !== '-' && $coreCode !== 'None' && !empty($coreCode)) {
            $this->bom->addCode("Roof Insulation Core", $coreCode, $salesCode, 1, $actualArea);
        }

        // Bottom skin (if sandwich panel)
        $botSkinCode = $this->building->roofBotSkin;
        if ($botSkinCode !== '-' && $botSkinCode !== 'None' && !empty($botSkinCode)) {
            $this->bom->addCode("Roof Bottom Skin", $botSkinCode, $salesCode, 1, $actualArea);
        }

        // Ridge cap
        $this->bom->addCode('', 'RC', $salesCode, 1, $this->buildingLength);

        $this->bom->addCode('', '-', $salesCode, '', '');
    }

    /**
     * Calculate wall sheeting
     */
    private function calculateWallSheeting(): void {
        $salesCode = 1;
        $description = "Wall Sheeting";

        $this->bom->addCode($description, '-', $salesCode, '', '');

        // Calculate wall areas
        $this->backWallArea = $this->backEaveHeight * $this->buildingLength;
        $this->frontWallArea = $this->frontEaveHeight * $this->buildingLength;
        $totalWallArea = $this->backWallArea + $this->frontWallArea;

        // Adjust for coverage
        $actualArea = $totalWallArea / 0.9;

        // Wall top skin
        $topSkinCode = $this->building->wallTopSkin;
        if ($topSkinCode !== 'None' && !empty($topSkinCode)) {
            $this->bom->addCode("Wall Sheeting", $topSkinCode, $salesCode, 1, $actualArea);
        }

        // Wall core
        $coreCode = $this->building->wallCore;
        if ($coreCode !== '-' && $coreCode !== 'None' && !empty($coreCode)) {
            $this->bom->addCode("Wall Insulation Core", $coreCode, $salesCode, 1, $actualArea);
        }

        // Wall bottom skin
        $botSkinCode = $this->building->wallBotSkin;
        if ($botSkinCode !== '-' && $botSkinCode !== 'None' && !empty($botSkinCode)) {
            $this->bom->addCode("Wall Inner Liner", $botSkinCode, $salesCode, 1, $actualArea);
        }

        $this->bom->addCode('', '-', $salesCode, '', '');
    }

    /**
     * Calculate endwalls
     */
    private function calculateEndwalls(): void {
        $salesCode = 1;

        // Left endwall
        $this->calculateEndwall('Left', $this->building->leftEndwallType, $salesCode);

        // Right endwall
        $this->calculateEndwall('Right', $this->building->rightEndwallType, $salesCode);
    }

    /**
     * Calculate a single endwall
     */
    private function calculateEndwall(string $side, string $type, int $salesCode): void {
        $description = "{$side} Endwall ({$type})";
        $this->bom->addCode($description, '-', $salesCode, '', '');

        $avgHeight = max(1, ($this->backEaveHeight + $this->peakHeight) / 2);
        $endwallArea = $avgHeight * max(1, $this->buildingWidth);

        if ($type === 'Main Frame' || $type === 'MF 1/2 Loaded') {
            // Main frame at endwall - no separate columns needed
            // Add girts only
            $nGirtLines = ceil($avgHeight / 1.8);
            $girtQty = $nGirtLines * ($this->nSpans + 1);
            $pdIndex = 2 * $this->wind * pow($this->avgSpan / 2, 2);
            $girtCode = ProductLookup::getGirtCode($pdIndex);

            $this->bom->addCode("Endwall Girts", $girtCode, $salesCode, $this->avgSpan / 2, $girtQty);

        } else {
            // Bearing frame or false rafter - need columns
            // Get column spacing from endwall spans or default
            $colSpacing = max(1, $this->avgSpan / 4); // Default 4 columns per span, min 1m
            $nColumns = $colSpacing > 0 ? ceil($this->buildingWidth / $colSpacing) + 1 : 2;

            // Calculate column design index
            $ewcIndex = pow($this->wind, 2) / 20000 * pow($avgHeight, 3) * $colSpacing / 3;
            $columnCode = ProductLookup::getEWColumnCode($ewcIndex);

            $this->bom->addCode("Endwall Columns", $columnCode, $salesCode, $avgHeight, $nColumns);

            // Endwall rafters (false rafter)
            if ($type === 'False Rafter') {
                $this->bom->addCode("False Rafters", 'Z25P', $salesCode, $this->avgSpan / 2, $nColumns);
            }

            // Endwall girts
            $nGirtLines = ceil($avgHeight / 1.8);
            $girtQty = $nGirtLines * ($nColumns - 1);
            $this->bom->addCode("Endwall Girts", 'Z20G', $salesCode, $colSpacing, $girtQty);

            // Clips and bolts
            $this->bom->addCode('', 'CFClip', $salesCode, 1, ($nColumns + $girtQty) * 2);
            $this->bom->addCode('', 'HSB12', $salesCode, 1, ($nColumns + $girtQty) * 6);
        }

        // Endwall sheeting
        $topSkinCode = $this->building->wallTopSkin;
        if ($topSkinCode !== 'None' && !empty($topSkinCode)) {
            $this->bom->addCode("Endwall Sheeting", $topSkinCode, $salesCode, 1, $endwallArea / 0.9);
        }

        // Gable angle
        $this->bom->addCode('', 'Gang', $salesCode, 1, $this->rafterLength * 2);

        // Base angle
        $this->bom->addCode('', 'Bang', $salesCode, 1, $this->buildingWidth);

        $this->bom->addCode('', '-', $salesCode, '', '');
    }

    /**
     * Calculate trims
     */
    private function calculateTrims(): void {
        $salesCode = 1;
        $description = "Trims & Flashings";

        $this->bom->addCode($description, '-', $salesCode, '', '');

        // Eave trim (both sides)
        $eaveTrimLength = 2 * $this->buildingLength;
        $this->bom->addCode("Eave Trim", 'TTE', $salesCode, 1, $eaveTrimLength);

        // Gable trim (both endwalls)
        $gableTrimLength = 4 * $this->rafterLength;
        $this->bom->addCode("Gable Trim", 'TTG', $salesCode, 1, $gableTrimLength);

        // Corner trim
        $avgHeight = ($this->backEaveHeight + $this->frontEaveHeight) / 2;
        $cornerTrimLength = 4 * $avgHeight;
        $this->bom->addCode("Corner Trim", 'TTC', $salesCode, 1, $cornerTrimLength);

        // Base trim
        $baseTrimLength = 2 * ($this->buildingLength + $this->buildingWidth);
        $this->bom->addCode("Base Trim", 'TTB', $salesCode, 1, $baseTrimLength);

        // Gutters (if applicable)
        if (strpos($this->building->backEaveCondition, 'Gutter') !== false) {
            $this->bom->addCode("Gutters", 'GUT', $salesCode, 1, $this->buildingLength);
            // Downspouts
            $nDownspouts = ceil($this->buildingLength / 12); // 1 per 12m
            $this->bom->addCode("Downspouts", 'DWSP', $salesCode, 1, $nDownspouts);
        }

        if (strpos($this->building->frontEaveCondition, 'Gutter') !== false) {
            $this->bom->addCode("Gutters", 'GUT', $salesCode, 1, $this->buildingLength);
            $nDownspouts = ceil($this->buildingLength / 12);
            $this->bom->addCode("Downspouts", 'DWSP', $salesCode, 1, $nDownspouts);
        }

        $this->bom->addCode('', '-', $salesCode, '', '');
    }

    /**
     * Calculate fasteners
     */
    private function calculateFasteners(): void {
        $salesCode = 1;
        $description = "Fasteners & Sealants";

        $this->bom->addCode($description, '-', $salesCode, '', '');

        // Roof screws (4 per m2)
        $roofScrews = $this->roofArea * 4;
        $screwCode = (strpos($this->building->roofTopSkin, 'A') !== false) ? 'SS2' : 'CS2';
        $this->bom->addCode("Roof Screws", $screwCode, $salesCode, 1, $roofScrews);

        // Wall screws
        $wallArea = $this->backWallArea + $this->frontWallArea + $this->leftEndwallArea + $this->rightEndwallArea;
        $wallScrews = $wallArea * 3;
        $this->bom->addCode("Wall Screws", $screwCode, $salesCode, 1, $wallScrews);

        // Bead mastic
        $beadMasticLength = $this->rafterLength * 2 * $this->buildingLength / 1.5; // Overlap every 1.5m
        $this->bom->addCode("Bead Mastic", 'BM', $salesCode, 1, $beadMasticLength);

        // Butyl tape
        $butylTapeLength = $this->rafterLength * 2 * $this->nFrames;
        $this->bom->addCode("Butyl Tape", 'BT', $salesCode, 1, $butylTapeLength);

        $this->bom->addCode('', '-', $salesCode, '', '');
    }

    /**
     * Calculate accessories (skylights, doors, etc.)
     */
    private function calculateAccessories(): void {
        // Skip if no accessories defined
        if (empty($this->building->accessories)) {
            return;
        }

        $salesCode = 1;
        $description = "Accessories";

        $this->bom->addCode($description, '-', $salesCode, '', '');

        foreach ($this->building->accessories as $accessory) {
            $code = $accessory['code'] ?? '';
            $qty = $accessory['quantity'] ?? 0;
            $size = $accessory['size'] ?? 1;
            $desc = $accessory['description'] ?? '';

            if (!empty($code) && $qty > 0) {
                $this->bom->addCode($desc, $code, $salesCode, $size, $qty);
            }
        }

        $this->bom->addCode('', '-', $salesCode, '', '');
    }

    /**
     * Calculate handling, packing, loading (HPL)
     */
    private function calculateHandlingPacking(): void {
        $salesCode = 1;
        $description = "Handling, Packing & Loading";

        $this->bom->addCode($description, '-', $salesCode, '', '');

        // HPL is typically a percentage of total weight
        $totalWeight = $this->bom->getTotalWeight();

        // PPL items based on weight categories
        $this->bom->addCode("Primary Steel Packing", 'PPLBu', $salesCode, 1, $this->frameWeight);
        $this->bom->addCode("Secondary Steel Packing", 'PPLCF', $salesCode, 1, $totalWeight * 0.3);
        $this->bom->addCode("Sheeting Packing", 'PPLSS', $salesCode, 1, $this->roofArea + $this->backWallArea + $this->frontWallArea);

        $this->bom->addCode('', '-', $salesCode, '', '');
    }

    /**
     * Update building object with calculated values
     */
    private function updateBuildingCalculations(): void {
        $this->building->buildingWidth = $this->buildingWidth;
        $this->building->buildingLength = $this->buildingLength;
        $this->building->roofArea = $this->roofArea;
        $this->building->wallArea = $this->backWallArea + $this->frontWallArea;
        $this->building->endwallArea = $this->leftEndwallArea + $this->rightEndwallArea;
        $this->building->rafterLength = $this->rafterLength;
        $this->building->peakHeight = $this->peakHeight;
    }

    /**
     * Get calculated building dimensions
     */
    public function getDimensions(): array {
        return [
            'width' => $this->buildingWidth,
            'length' => $this->buildingLength,
            'backEaveHeight' => $this->backEaveHeight,
            'frontEaveHeight' => $this->frontEaveHeight,
            'peakHeight' => $this->peakHeight,
            'rafterLength' => $this->rafterLength,
            'nSpans' => $this->nSpans,
            'nBays' => $this->nBays,
            'nFrames' => $this->nFrames,
        ];
    }

    /**
     * Get load summary
     */
    public function getLoads(): array {
        return [
            'deadLoad' => $this->building->deadLoad,
            'liveLoadPurlin' => $this->building->liveLoadPurlin,
            'liveLoadFrame' => $this->building->liveLoadFrame,
            'additionalLoad' => $this->building->additionalLoad,
            'windSpeed' => $this->building->windSpeed,
            'windLoad' => $this->wind,
            'totalPurlinLoad' => $this->loadP,
            'totalFrameLoad' => $this->mfLoad,
        ];
    }
}
