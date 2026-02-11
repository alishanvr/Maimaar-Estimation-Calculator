<?php
/**
 * QuickEst - Crane Calculator
 *
 * Calculates crane runway beam materials
 * Replicates VBA AddCrane_Click() procedure
 */

namespace QuickEst\Services;

use QuickEst\Models\BillOfMaterials;
use QuickEst\Helpers\ListParser;
use QuickEst\Database\ProductLookup;

class CraneCalculator {

    /**
     * Duty factor multipliers
     */
    private array $dutyFactors = [
        'L' => 1.0,   // Light duty
        'M' => 1.1,   // Medium duty
        'H' => 1.2,   // Heavy duty
    ];

    /**
     * Calculate crane runway materials
     *
     * @param array $params Crane parameters:
     *   - description: string
     *   - salesCode: int (default 1)
     *   - capacity: float (MT - metric tons)
     *   - duty: string (L/M/H)
     *   - railCenters: float (m)
     *   - craneRun: string (e.g., "6@6" for 6 bays of 6m)
     */
    public function calculate(array $params): BillOfMaterials {
        $bom = new BillOfMaterials();

        // Extract parameters
        $description = $params['description'] ?? 'EOT Crane';
        $salesCode = intval($params['salesCode'] ?? 1);
        $capacity = floatval($params['capacity'] ?? 5);
        $duty = strtoupper(substr($params['duty'] ?? 'M', 0, 1));
        $railCenters = floatval($params['railCenters'] ?? 18);
        $craneRunStr = $params['craneRun'] ?? '6@6';

        // Parse crane run spacing
        $craneRunParsed = ListParser::parseList($craneRunStr);
        $craneRunExpanded = ListParser::expandList($craneRunParsed);
        $totalRun = array_sum($craneRunExpanded);
        $numBays = count($craneRunExpanded);

        // Get duty factor
        $dutyFactor = $this->dutyFactors[$duty] ?? 1.0;

        // Build description with specifications
        $fullDesc = "{$description} Capacity={$capacity} MT, Run={$craneRunStr} Width={$railCenters}";

        // Track PPL items for handling/packing
        $pplCrb = 0;
        $pplBra = 0;

        // Process each bay
        foreach ($craneRunParsed as $index => $run) {
            $bayLength = $run['value'];
            $bayCount = $run['count'];

            // Calculate Crane Beam Index: CBIndex = Capacity * BayLength * Sqrt(RailCenters)
            $cbIndex = $capacity * $bayLength * sqrt($railCenters);

            // Apply duty factor
            $cbIndex *= $dutyFactor;

            // Determine crane beam and related codes based on CBIndex
            $codes = $this->getCraneBeamCodes($cbIndex);

            // Crane Beam size
            $size = $bayLength;

            // If CBIndex > 1400, use built-up beam with calculated size
            if ($cbIndex > 1400) {
                $codes['beam'] = 'BUB';
                $size = intval($cbIndex / 1400 * 101 * $bayLength);
            }

            // Add crane beams (2 per bay - one on each side)
            $beamQty = 2 * $bayCount;
            $bom->addCode($fullDesc, $codes['beam'], $salesCode, $size, $beamQty);
            $fullDesc = ''; // Clear after first item

            // Add crane corbels
            $bom->addCode('', $codes['corbel'], $salesCode, $bayLength, $beamQty);

            // Add crane bracing
            // Bracing at each bay + 2 at end
            $bracingQty = 2 * $bayCount + ($index === count($craneRunParsed) - 1 ? 2 : 0);
            $bom->addCode('', $codes['bracing'], $salesCode, 1, $bracingQty);

            $pplCrb += $beamQty;
        }

        // Add crane bracing angles (4 per total number of bays)
        $bom->addCode('', 'CRA', $salesCode, 1, 4 * $numBays);
        $pplBra = 4 * $numBays;

        // Add crane stoppers (4 total - 2 at each end)
        $bom->addCode('', 'CRS', 1, 1, 4);

        // Additional frame weight for crane loads
        $cbIndexAvg = $capacity * ($totalRun / $numBays) * sqrt($railCenters) * $dutyFactor;
        $additionalFrameWeight = intval(4 * sqrt($cbIndexAvg));
        $frameQty = 2 * $numBays + 2;
        $bom->addCode('', 'BU', $salesCode, $additionalFrameWeight, $frameQty);

        // Connections - High Strength Bolts
        // HSB2060 for main connections
        $hsb2060Qty = 8 * ($numBays + 1);
        $bom->addCode('', 'HSB2060', $salesCode, 1, $hsb2060Qty);

        // HSB16 for secondary connections
        $hsb16Qty = 8 * $numBays;
        $bom->addCode('', 'HSB16', $salesCode, 1, $hsb16Qty);

        // Close section
        $bom->addCode('', '-', $salesCode, '', '');

        return $bom;
    }

    /**
     * Get crane beam codes based on CBIndex
     *
     * CBIndex thresholds (from VBA):
     * > 1400: BUB (built-up), CRC4, BUCRBr6
     * > 1000: BUCRB4, CRC4, BUCRBr6
     * > 800:  BUCRB3, CRC4, BUCRBr5
     * > 600:  BUCRB2, CRC3, BUCRBr5
     * > 400:  BUCRB2, CRC3, BUCRBr3
     * > 200:  BUCRB1, CRC2, BUCRBr3
     * <= 200: BUCRB1, CRC2, BUCRBr3
     */
    private function getCraneBeamCodes(float $cbIndex): array {
        if ($cbIndex > 1000) {
            return [
                'beam' => 'BUCRB4',
                'corbel' => 'CRC4',
                'bracing' => 'BUCRBr6',
            ];
        }

        if ($cbIndex > 800) {
            return [
                'beam' => 'BUCRB3',
                'corbel' => 'CRC4',
                'bracing' => 'BUCRBr5',
            ];
        }

        if ($cbIndex > 600) {
            return [
                'beam' => 'BUCRB2',
                'corbel' => 'CRC3',
                'bracing' => 'BUCRBr5',
            ];
        }

        if ($cbIndex > 400) {
            return [
                'beam' => 'BUCRB2',
                'corbel' => 'CRC3',
                'bracing' => 'BUCRBr3',
            ];
        }

        // CBIndex <= 400
        return [
            'beam' => 'BUCRB1',
            'corbel' => 'CRC2',
            'bracing' => 'BUCRBr3',
        ];
    }

    /**
     * Calculate crane beam index for reference
     */
    public static function calculateCBIndex(
        float $capacity,
        float $bayLength,
        float $railCenters,
        string $duty = 'M'
    ): float {
        $dutyFactors = ['L' => 1.0, 'M' => 1.1, 'H' => 1.2];
        $dutyFactor = $dutyFactors[strtoupper($duty)] ?? 1.0;

        return $capacity * $bayLength * sqrt($railCenters) * $dutyFactor;
    }

    /**
     * Get recommended crane beam size based on parameters
     */
    public static function getRecommendedBeamSize(
        float $capacity,
        float $bayLength,
        float $railCenters,
        string $duty = 'M'
    ): array {
        $cbIndex = self::calculateCBIndex($capacity, $bayLength, $railCenters, $duty);

        $calculator = new self();
        $codes = $calculator->getCraneBeamCodes($cbIndex);

        return [
            'cbIndex' => $cbIndex,
            'beamCode' => $codes['beam'],
            'corbelCode' => $codes['corbel'],
            'bracingCode' => $codes['bracing'],
            'isBuiltUp' => $cbIndex > 1400,
        ];
    }
}
