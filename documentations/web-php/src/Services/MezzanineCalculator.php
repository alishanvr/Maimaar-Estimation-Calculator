<?php
/**
 * QuickEst - Mezzanine Calculator
 *
 * Calculates mezzanine floor materials
 * Replicates VBA AddMezz_Click() procedure
 */

namespace QuickEst\Services;

use QuickEst\Models\BillOfMaterials;
use QuickEst\Helpers\ListParser;
use QuickEst\Database\ProductLookup;

class MezzanineCalculator {

    /**
     * Calculate mezzanine materials
     *
     * @param array $params Mezzanine parameters:
     *   - description: string
     *   - salesCode: int
     *   - colSpacing: string (e.g., "2@6")
     *   - beamSpacing: string (e.g., "3@4")
     *   - joistSpacing: string (e.g., "1@1.5")
     *   - clearHeight: float (m)
     *   - doubleWelded: string (Yes/No)
     *   - deckType: string
     *   - nStairs: int
     *   - deadLoad: float (kN/m2)
     *   - liveLoad: float (kN/m2)
     *   - additionalLoad: float (kN/m2)
     *   - buFinish: string
     *   - cfFinish: string
     *   - minThickness: float (mm)
     */
    public function calculate(array $params): BillOfMaterials {
        $bom = new BillOfMaterials();

        // Extract parameters
        $description = $params['description'] ?? 'Mezzanine';
        $salesCode = $params['salesCode'] ?? 1;
        $colSpStr = $params['colSpacing'] ?? '';
        $beamSpStr = $params['beamSpacing'] ?? '';
        $joistSpStr = $params['joistSpacing'] ?? '';
        $clearHeight = floatval($params['clearHeight'] ?? 3.0);
        $doubleWelded = $params['doubleWelded'] ?? 'No';
        $deckType = $params['deckType'] ?? 'Deck-0.75';
        $nStairs = intval($params['nStairs'] ?? 0);
        $deadLoad = floatval($params['deadLoad'] ?? 0.5);
        $liveLoad = floatval($params['liveLoad'] ?? 5.0);
        $additionalLoad = floatval($params['additionalLoad'] ?? 0);
        $minThick = floatval($params['minThickness'] ?? 6);

        // Parse spacing strings
        $colSpacing = ListParser::parseList($colSpStr);
        $beamSpacing = ListParser::parseList($beamSpStr);
        $joistSpacing = ListParser::parseList($joistSpStr);

        $exColSp = ListParser::expandList($colSpacing);
        $exBeamSp = ListParser::expandList($beamSpacing);
        $exJoistSp = ListParser::expandList($joistSpacing);

        // Calculate mezzanine dimensions
        $mezWidth = array_sum($exColSp);
        $mezLength = array_sum($exBeamSp);
        $mezArea = $mezWidth * $mezLength;

        // Total load
        $totalLoad = $deadLoad + $liveLoad + $additionalLoad;

        // Minimum weight per linear meter based on thickness
        $mwplm = sqrt($minThick / 3.5) * 18.5;

        // Update description with dimensions
        $fullDesc = "{$description} ({$mezWidth}m X {$mezLength}m Load= {$totalLoad} kN/m2)";

        // Add header
        $bom->addCode($fullDesc, '-', $salesCode, '', '');

        // === DECK ===
        $deckCode = $this->getDeckCode($deckType);
        $deckWeight = ProductLookup::weight($deckCode) * $mezArea;
        $bom->addCode('', $deckCode, $salesCode, 1, $mezArea);

        // Mezzanine edge angles
        $edgeLength = 2 * ($mezLength + $mezWidth);
        $bom->addCode('', 'MEA', $salesCode, 1, $edgeLength);

        // Deck fasteners
        $bom->addCode('', 'MDF', $salesCode, 1, 6 * $mezArea);

        // === JOISTS ===
        $joistSpacingValue = count($exJoistSp) > 0 ? $exJoistSp[0] : 1.5;
        $nJoists = intval($mezWidth / $joistSpacingValue) + 1;
        $joistDesc = "Joists ({$nJoists} runs @ {$mezLength}m)";

        $joistLength = 0;
        $joistClips = 0;

        foreach ($beamSpacing as $beam) {
            $span = $beam['value'];
            $qty = $nJoists * $beam['count'];

            // Joist design index
            $jdIndex = pow($span, 2) * $totalLoad * $joistSpacingValue;
            $jdCode = ProductLookup::getJoistCode($jdIndex);

            $size = $span;
            if ($jdCode === 'BUB') {
                $size = intval($jdIndex / 1000 * $span * 40);
            }

            $bom->addCode($joistDesc, $jdCode, $salesCode, $size, $qty);
            $joistDesc = ''; // Clear for subsequent items

            if ($jdCode === 'BUB') {
                $joistLength += $qty * $span;
            }
            $joistClips += $qty;
        }

        // Double welding for joists
        if ($doubleWelded === 'Yes') {
            $bom->addCode('', 'DSW', $salesCode, 1, $joistLength);
        }

        // Built-up length tracking
        $bom->addCode('', 'BuLeng', $salesCode, 1, $joistLength);

        // Joist clips and bolts
        $bom->addCode('', 'JCL', $salesCode, 1, 2 * $joistClips);
        $bom->addCode('', 'HSB12', $salesCode, 1, 2 * $joistClips * 3);

        // === BEAMS ===
        $nBeams = count($exColSp) * (count($exBeamSp) + 1);
        $beamLength = 0;
        $bcPs = 0; // Small beam clips
        $bcPl = 0; // Large beam clips
        $mezBeams = [];

        for ($i = 1; $i <= count($exBeamSp) + 1; $i++) {
            // Calculate beam load
            if ($i > 1 && $i < count($exBeamSp) + 1) {
                $beamLoad = $totalLoad * ($exBeamSp[$i - 2] + $exBeamSp[$i - 1]) / 2;
            } elseif ($i === 1) {
                $beamLoad = 0.6 * $totalLoad * $exBeamSp[0];
            } else {
                $beamLoad = 0.6 * $totalLoad * $exBeamSp[count($exBeamSp) - 1];
            }

            foreach ($exColSp as $span) {
                $m = $beamLoad * pow($span, 2) / 8;
                $wplm = 0.55 * $m / $span + 0.66 * pow($m, 0.67);
                if ($wplm < 1.32 * pow($m, 0.67)) {
                    $wplm = 1.32 * pow($m, 0.67);
                }
                if ($wplm < $mwplm) {
                    $wplm = $mwplm;
                }

                $mezBeams[] = [
                    'wplm' => intval($wplm * $span),
                    'span' => $span
                ];
                $beamLength += $span;
            }
        }

        // Group beams by weight and add to BOM
        $beamDesc = "Mezzanine Beams {$nBeams} Nos";
        $grouped = $this->groupItems($mezBeams);

        foreach ($grouped as $weight => $items) {
            $qty = count($items);
            $bom->addCode($beamDesc, 'BUB', $salesCode, $weight, $qty);
            $beamDesc = '';

            // Beam clips based on size
            if ($weight < 450) {
                $bcPs += $qty * 4;
            } else {
                $bcPl += $qty * 4;
            }
        }

        // Double welding for beams
        if ($doubleWelded === 'Yes') {
            $bom->addCode('', 'DSW', $salesCode, 1, $beamLength);
        }
        $bom->addCode('', 'BuLeng', $salesCode, 1, $beamLength);

        // Beam clips and bolts
        if ($bcPs > 0) {
            $bom->addCode('', 'MFC1', $salesCode, 1, $bcPs);
        }
        if ($bcPl > 0) {
            $bom->addCode('', 'MFC3', $salesCode, 1, $bcPl);
        }
        if ($bcPs + $bcPl > 0) {
            $bom->addCode('', 'HSB2060', $salesCode, 1, 6 * $bcPs + 8 * $bcPl);
        }

        // === COLUMNS ===
        $nColumns = (count($exColSp) + 1) * (count($exBeamSp) + 1);
        $colDesc = "Mezzanine Columns {$nColumns} Nos";
        $mezColumns = [];

        for ($i = 0; $i <= count($exColSp); $i++) {
            $ist = ($i > 0) ? $exColSp[$i - 1] : 0;
            $ie = ($i < count($exColSp)) ? $exColSp[$i] : 0;

            for ($j = 0; $j <= count($exBeamSp); $j++) {
                $jst = ($j > 0) ? $exBeamSp[$j - 1] : 0;
                $je = ($j < count($exBeamSp)) ? $exBeamSp[$j] : 0;

                $height = $clearHeight + 0.3;
                if ($height < 2) $height = 2;

                $colIndex = $totalLoad * ($jst + $je) * ($ist + $ie) / 4 * pow($height, 2);

                // Determine column type based on index
                $colCode = 'BUC';
                $colSize = $clearHeight + 0.3;

                if ($colIndex < 4500) $colCode = 'T200';
                if ($colIndex < 1950) $colCode = 'T150';
                if ($colIndex < 600) $colCode = 'IPEa';

                if ($colCode === 'BUC') {
                    $colSize = intval($colSize * $colIndex / 4500 * 36);
                }

                $mezColumns[] = [
                    'code' => $colCode,
                    'size' => $colSize
                ];
            }
        }

        // Group columns by code and size
        $groupedCols = [];
        foreach ($mezColumns as $col) {
            $key = $col['code'] . '_' . $col['size'];
            if (!isset($groupedCols[$key])) {
                $groupedCols[$key] = ['code' => $col['code'], 'size' => $col['size'], 'count' => 0];
            }
            $groupedCols[$key]['count']++;
        }

        foreach ($groupedCols as $col) {
            $bom->addCode($colDesc, $col['code'], $salesCode, $col['size'], $col['count']);
            $colDesc = '';
        }

        // Column connections
        if ($nColumns > 0) {
            $bom->addCode('', 'MFC1', $salesCode, 1, $nColumns);
            $bom->addCode('', 'AB24', $salesCode, 1, 4 * $nColumns);
        }

        // === STAIRS ===
        if ($nStairs > 0) {
            $bom->addCode('', 'DSP', $salesCode, 1, $nStairs);
        }

        // Close section
        $bom->addCode('', '-', $salesCode, '', '');

        return $bom;
    }

    /**
     * Get deck code from description
     */
    private function getDeckCode(string $deckType): string {
        $deckMap = [
            'Deck-0.75' => 'Deck75',
            'Deck-1.00' => 'Deck100',
            'Deck-1.25' => 'Deck125',
            'Chequered Plate' => 'ChqPl',
        ];

        return $deckMap[$deckType] ?? 'Deck75';
    }

    /**
     * Group items by weight/size
     */
    private function groupItems(array $items): array {
        $grouped = [];
        foreach ($items as $item) {
            $weight = $item['wplm'];
            if (!isset($grouped[$weight])) {
                $grouped[$weight] = [];
            }
            $grouped[$weight][] = $item;
        }
        return $grouped;
    }
}
