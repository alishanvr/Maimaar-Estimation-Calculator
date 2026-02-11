<?php
/**
 * QuickEst - Partition Calculator
 *
 * Calculates internal partition wall materials
 * Replicates VBA AddPartition_Click() procedure
 */

namespace QuickEst\Services;

use QuickEst\Models\BillOfMaterials;
use QuickEst\Helpers\ListParser;
use QuickEst\Database\ProductLookup;

class PartitionCalculator {

    /**
     * Calculate partition wall materials
     *
     * @param array $params Partition parameters:
     *   - description: string
     *   - salesCode: int
     *   - direction: string (Across/Along)
     *   - colSpacing: string (e.g., "4@6")
     *   - height: float (m)
     *   - openHeight: float (m) - open area at bottom
     *   - frontSheeting: string
     *   - backSheeting: string
     *   - insulation: string
     *   - windSpeed: float (km/h)
     *   - buFinish: string
     *   - cfFinish: string
     */
    public function calculate(array $params): BillOfMaterials {
        $bom = new BillOfMaterials();

        // Extract parameters
        $description = $params['description'] ?? 'Partition Wall';
        $salesCode = intval($params['salesCode'] ?? 1);
        $direction = $params['direction'] ?? 'Across';
        $colSpacingStr = $params['colSpacing'] ?? '4@6';
        $height = floatval($params['height'] ?? 6);
        $openHeight = floatval($params['openHeight'] ?? 0);
        $frontSheeting = $params['frontSheeting'] ?? 'S5OW';
        $backSheeting = $params['backSheeting'] ?? 'S5OW';
        $insulation = $params['insulation'] ?? 'None';
        $windSpeed = floatval($params['windSpeed'] ?? 130);
        $cfFinish = $params['cfFinish'] ?? 'Galvanized';

        // Parse column spacing
        $colSpacing = ListParser::parseList($colSpacingStr);

        // Calculate total length
        $totalLength = ListParser::getTotalSum($colSpacing);
        $numBays = ListParser::getTotalCount($colSpacing);

        // Wind load calculation
        $windLoad = pow($windSpeed, 2) / 20000;

        // Build description
        $fullDesc = "Partition: {$description}, Width={$totalLength}m, Height={$height}m";

        // Add header
        $bom->addCode($fullDesc, '-', $salesCode, '', '');

        // === COLUMNS ===
        $colDesc = "Partition Columns";
        $totalColumns = 0;

        foreach ($colSpacing as $index => $spacing) {
            $qty = $spacing['count'];
            $trWidth = $spacing['value'];

            // Last spacing group gets +1 for end column
            if ($index === count($colSpacing) - 1) {
                $qty++;
            }

            // Endwall Column Index: EWCIndex = WindLoad * Height^3 * TributaryWidth / 3
            $ewcIndex = $windLoad * pow($height, 3) * $trWidth / 3;

            // Get column code based on index
            $colCode = $this->getColumnCode($ewcIndex);

            $bom->addCode($colDesc, $colCode, $salesCode, $height, $qty);
            $colDesc = ''; // Clear after first item
            $totalColumns += $qty;
        }

        // Column connections
        $bom->addCode('', 'CFClip', $salesCode, 1, 4 * $numBays + 4);
        $bom->addCode('', 'HSB12', $salesCode, 1, 6 * $numBays + 6);
        $bom->addCode('', 'AB16', $salesCode, 1, 2 * $numBays + 2);

        // === GIRTS ===
        $girtClips = 0;
        $girtBolts = 0;
        $sagRods = 0;

        // Calculate average height (minus open area)
        $avgHeight = $height - $openHeight;

        // Number of girt lines (approximately 1.9m spacing)
        $girtLines = intval($avgHeight / 1.9) + 1;

        $girtDesc = "Partition Wall Girts";

        foreach ($colSpacing as $spacing) {
            $trWidth = $spacing['value'];
            $count = $spacing['count'];

            // Purlin Design Index for girts
            $pdIndex = 2.01 * $windLoad * pow($trWidth, 2);

            // Get girt code
            $girtCode = ProductLookup::getGirtCode($pdIndex);

            $qty = $count * $girtLines;

            if ($qty > 0) {
                $bom->addCode($girtDesc, $girtCode, $salesCode, $trWidth, $qty);
                $girtClips += 2 * $qty;
                $girtBolts += $qty * 8;
                $girtDesc = '';

                // Sag rods for spans > 7.5m
                if ($trWidth > 7.5) {
                    $sagRods += $qty;
                }
            }
        }

        // Girt bolts and clips
        $bom->addCode('', 'HSB12', $salesCode, 1, $girtBolts);
        if ($girtClips > 0) {
            $bom->addCode('', 'CFClip', $salesCode, 1, $girtClips);
        }

        // === ANGLES ===
        // Gable angle (top)
        $bom->addCode('', 'Gang', $salesCode, 1, 2 * $totalLength);

        // Base angle (if no open height)
        if ($openHeight == 0) {
            $bom->addCode('', 'Bang', $salesCode, 1, 2 * $totalLength);
        }

        // Sag rods
        if ($sagRods > 0) {
            $bom->addCode('', 'SR12', $salesCode, 1, $sagRods);
        }

        // === SHEETING & TRIMS ===
        $sheetingDesc = "Sheeting & Trims";
        $sheetingArea = $totalLength * ($height - $openHeight);
        $totalTrim = 0;
        $fasteners = 0;

        // Front sheeting
        if ($frontSheeting !== 'None' && !empty($frontSheeting)) {
            $frontCode = ProductLookup::codeOf($frontSheeting) ?: $frontSheeting;
            $bom->addCode($sheetingDesc, $frontCode, $salesCode, 1, $sheetingArea);
            $sheetingDesc = '';
            $totalTrim += $totalLength + 2 * ($height - $openHeight);
            $fasteners += 4 * $sheetingArea;
        }

        // Back sheeting
        if ($backSheeting !== 'None' && !empty($backSheeting)) {
            $backCode = ProductLookup::codeOf($backSheeting) ?: $backSheeting;
            $bom->addCode($sheetingDesc, $backCode, $salesCode, 1, $sheetingArea);
            $sheetingDesc = '';
            $totalTrim += $totalLength + 2 * ($height - $openHeight);
            $fasteners += $sheetingArea;
        }

        // Trims
        if ($totalTrim > 0) {
            $bom->addCode('', 'TTS1', $salesCode, 1, $totalTrim);
        }

        // Fasteners (screws)
        if ($fasteners > 0) {
            // Use aluminum screws if sheeting contains aluminum
            $screwCode = 'CS2';
            if (stripos($frontSheeting . $backSheeting, 'A') !== false) {
                $screwCode = 'SS2';
            }
            $bom->addCode('', $screwCode, $salesCode, 1, $fasteners);
        }

        // === INSULATION ===
        if ($insulation !== 'None' && !empty($insulation)) {
            $insCode = ProductLookup::codeOf($insulation) ?: $insulation;
            $bom->addCode('Insulation', $insCode, $salesCode, 1, $sheetingArea);
        }

        // Close section
        $bom->addCode('', '-', $salesCode, '', '');

        return $bom;
    }

    /**
     * Get column code based on EWC Index
     */
    private function getColumnCode(float $ewcIndex): string {
        // Thresholds based on VBA EWCCode lookup
        if ($ewcIndex > 500) return 'T200';
        if ($ewcIndex > 300) return 'T150';
        if ($ewcIndex > 150) return 'IPEa';
        if ($ewcIndex > 80) return 'C20G';
        return 'C15G';
    }
}
