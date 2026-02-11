<?php
/**
 * QuickEst - Liner Calculator
 *
 * Calculates roof and wall liner materials
 * Replicates VBA Roof Liner and Wall Liner logic from AddArea_Click()
 */

namespace QuickEst\Services;

use QuickEst\Models\BillOfMaterials;
use QuickEst\Database\ProductLookup;

class LinerCalculator {

    /**
     * Calculate liner materials
     *
     * @param array $params Liner parameters:
     *   - description: string
     *   - salesCode: int
     *   - type: string (Roof Liner / Wall Liner / Both)
     *   - roofLinerType: string (liner product code)
     *   - wallLinerType: string (liner product code)
     *   - roofArea: float (m2) - manual entry or auto-calculated
     *   - wallArea: float (m2) - manual entry or auto-calculated
     *   - buildingWidth: float (m)
     *   - buildingLength: float (m)
     *   - backEaveHeight: float (m)
     *   - frontEaveHeight: float (m)
     *   - rafterLength: float (m) - sloped roof length
     *   - endwallArea: float (m2) - per endwall
     *   - roofOpenings: float (m2) - skylights, etc.
     *   - wallOpenings: float (m2) - doors, windows, etc.
     */
    public function calculate(array $params): BillOfMaterials {
        $bom = new BillOfMaterials();

        // Extract parameters
        $description = $params['description'] ?? 'Liner';
        $salesCode = intval($params['salesCode'] ?? 1);
        $type = $params['type'] ?? 'Both';
        $roofLinerType = $params['roofLinerType'] ?? 'S5OW';
        $wallLinerType = $params['wallLinerType'] ?? 'S5OW';

        // Building dimensions for area calculation
        $buildingWidth = floatval($params['buildingWidth'] ?? 24);
        $buildingLength = floatval($params['buildingLength'] ?? 36);
        $backEaveHeight = floatval($params['backEaveHeight'] ?? 8);
        $frontEaveHeight = floatval($params['frontEaveHeight'] ?? 8);
        $rafterLength = floatval($params['rafterLength'] ?? 0);
        $endwallArea = floatval($params['endwallArea'] ?? 0);

        // Openings to subtract
        $roofOpenings = floatval($params['roofOpenings'] ?? 0);
        $wallOpenings = floatval($params['wallOpenings'] ?? 0);

        // If rafter length not provided, estimate based on width and slope
        if ($rafterLength <= 0) {
            $avgSlope = 0.1; // Default 10% slope
            $rafterLength = $buildingWidth * sqrt(1 + pow($avgSlope, 2));
        }

        // If endwall area not provided, estimate as triangular gable
        if ($endwallArea <= 0) {
            $avgHeight = ($backEaveHeight + $frontEaveHeight) / 2;
            $peakHeight = $avgHeight + ($buildingWidth / 2) * 0.1; // Assume 10% slope
            $endwallArea = $avgHeight * $buildingWidth +
                           ($peakHeight - $avgHeight) * $buildingWidth / 2;
        }

        // Calculate or use provided areas (with 7.5% waste factor as per VBA)
        $wasteFactor = 1.075;

        // Roof liner area: rafterLength * buildingLength * 1.12 (VBA) - openings
        $roofArea = floatval($params['roofArea'] ?? 0);
        if ($roofArea <= 0) {
            $roofArea = $rafterLength * $buildingLength * 1.12 - $roofOpenings;
        }
        $roofArea = $roofArea * $wasteFactor;

        // Wall liner area: leng * (BEH + FEH) * 1.1 + 2 * EWArea * 1.1 - openings
        $wallArea = floatval($params['wallArea'] ?? 0);
        if ($wallArea <= 0) {
            $sidewallArea = $buildingLength * ($backEaveHeight + $frontEaveHeight) * 1.1;
            $bothEndwalls = 2 * $endwallArea * 1.1;
            $wallArea = $sidewallArea + $bothEndwalls - $wallOpenings;
        }
        $wallArea = $wallArea * $wasteFactor;

        // Add description line
        $fullDesc = "{$description} (Building: {$buildingWidth}m x {$buildingLength}m)";
        $bom->addCode($fullDesc, '-', $salesCode, '', '');

        // Process based on type
        if ($type === 'Roof Liner' || $type === 'Both') {
            $this->addRoofLiner($bom, $salesCode, $roofLinerType, $roofArea);
        }

        if ($type === 'Wall Liner' || $type === 'Both') {
            $this->addWallLiner($bom, $salesCode, $wallLinerType, $wallArea);
        }

        // End separator
        $bom->addCode('', '-', $salesCode, '', '');

        return $bom;
    }

    /**
     * Add roof liner materials
     */
    private function addRoofLiner(
        BillOfMaterials $bom,
        int $salesCode,
        string $linerType,
        float $area
    ): void {
        if ($linerType === 'None' || empty($linerType) || $area <= 0) {
            return;
        }

        // Get liner code
        $linerCode = ProductLookup::codeOf($linerType) ?: $linerType;

        $bom->addCode('Roof Liner', $linerCode, $salesCode, 1, round($area, 2));

        // Determine screw type based on liner code
        // A = Aluminum, S = Steel, PU = Polyurethane
        $screwCode = $this->getScrewCode($linerType);

        // 4 screws per m2 as per VBA
        $screwQty = intval($area * 4);
        $bom->addCode('', $screwCode, $salesCode, 1, $screwQty);

        // Stitch screws for joints (optional)
        $stitchQty = intval($area * 0.5); // Approx 0.5 stitch per m2
        if ($stitchQty > 0) {
            $stitchCode = (stripos($linerType, 'A') !== false) ? 'SS1' : 'CS1';
            $bom->addCode('', $stitchCode, $salesCode, 1, $stitchQty);
        }
    }

    /**
     * Add wall liner materials
     */
    private function addWallLiner(
        BillOfMaterials $bom,
        int $salesCode,
        string $linerType,
        float $area
    ): void {
        if ($linerType === 'None' || empty($linerType) || $area <= 0) {
            return;
        }

        // Get liner code
        $linerCode = ProductLookup::codeOf($linerType) ?: $linerType;

        $bom->addCode('Wall Liner', $linerCode, $salesCode, 1, round($area, 2));

        // Determine screw type based on liner code
        $screwCode = $this->getScrewCode($linerType);

        // 4 screws per m2 as per VBA
        $screwQty = intval($area * 4);
        $bom->addCode('', $screwCode, $salesCode, 1, $screwQty);

        // Stitch screws for joints
        $stitchQty = intval($area * 0.5);
        if ($stitchQty > 0) {
            $stitchCode = (stripos($linerType, 'A') !== false) ? 'SS1' : 'CS1';
            $bom->addCode('', $stitchCode, $salesCode, 1, $stitchQty);
        }
    }

    /**
     * Determine screw code based on liner type
     *
     * VBA Logic:
     *   - If InStr(Code, "A") > 0 Then screwcode = "SS2" (Stainless for Aluminum)
     *   - If InStr(Code, "PUS") > 0 Then screwcode = "CS4" (Long screw for PU Steel)
     *   - If InStr(Code, "PUA") > 0 Then screwcode = "SS4" (Long stainless for PU Aluminum)
     *   - Default: CS2 (Carbon steel)
     */
    private function getScrewCode(string $linerType): string {
        $upper = strtoupper($linerType);

        if (strpos($upper, 'PUA') !== false) {
            return 'SS4'; // Stainless long screw for PU Aluminum
        }
        if (strpos($upper, 'PUS') !== false) {
            return 'CS4'; // Carbon long screw for PU Steel
        }
        if (strpos($upper, 'A') !== false) {
            return 'SS2'; // Stainless for Aluminum
        }

        return 'CS2'; // Default carbon steel screw
    }

    /**
     * Calculate liner weight (for summary)
     * Uses product lookup to get weight per m2
     */
    public function calculateWeight(string $linerType, float $area): float {
        $weight = ProductLookup::getWeight($linerType);
        if ($weight <= 0) {
            // Default weights per m2 based on thickness
            if (stripos($linerType, '5') !== false) {
                $weight = 4.2; // 0.5mm steel ~4.2 kg/m2
            } elseif (stripos($linerType, '7') !== false) {
                $weight = 5.9; // 0.7mm steel ~5.9 kg/m2
            } else {
                $weight = 5.0; // Default
            }
        }
        return $weight * $area;
    }
}
