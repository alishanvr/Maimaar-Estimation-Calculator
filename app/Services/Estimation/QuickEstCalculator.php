<?php

namespace App\Services\Estimation;

class QuickEstCalculator
{
    public function __construct(
        private readonly CachingService $cachingService
    ) {}

    /**
     * Purlin design table: maps PDIndex range thresholds to cold-formed codes.
     * Derived from DB sheet named ranges PDIndex/PDCode.
     * Format: [max_index => code]
     *
     * @var array<float, string>
     */
    private const PURLIN_DESIGN_TABLE = [
        15.0 => 'Z15G',
        25.0 => 'Z18G',
        50.0 => 'Z20G',
        75.0 => 'Z25G',
        100.0 => 'Z25G',
        200.0 => 'Z25G',
        350.0 => 'Z25G',
        500.0 => 'Z25G',
    ];

    /**
     * Endwall column design table for painted finish (CFFinish=3).
     * Maps EWCIndex thresholds to section codes.
     *
     * @var array<float, string>
     */
    private const EWC_TABLE_PAINTED = [
        30.0 => 'Z15G',
        60.0 => 'Z18G',
        120.0 => 'Z20G',
        200.0 => 'Z25G',
        400.0 => 'HRB1',
        800.0 => 'HRB2',
        1200.0 => 'BUC',
        2000.0 => 'BUC',
        3000.0 => 'BUC',
        5000.0 => 'BUC',
        10000.0 => 'BUC',
    ];

    /**
     * Endwall column design table for galvanized finish (CFFinish=4).
     *
     * @var array<float, string>
     */
    private const EWC_TABLE_GALVANIZED = [
        30.0 => 'Z15G',
        60.0 => 'Z18G',
        120.0 => 'Z20G',
        200.0 => 'Z25G',
        400.0 => 'Z25G',
        800.0 => 'Z25G',
        1200.0 => 'BUC',
        2000.0 => 'BUC',
        3000.0 => 'BUC',
        5000.0 => 'BUC',
        10000.0 => 'BUC',
    ];

    /**
     * Gutter type dimensions: [depth, avg_width, downspout_area].
     *
     * @var array<int, array{depth: int, width: int, area: int}>
     */
    private const GUTTER_TYPES = [
        1 => ['depth' => 160, 'width' => 168, 'area' => 7670],    // Eave Gutter
        2 => ['depth' => 185, 'width' => 400, 'area' => 17671],   // Common Eave Valley Gutter
        3 => ['depth' => 185, 'width' => 370, 'area' => 17671],   // High/Low Valley Gutter
    ];

    /**
     * Lookup purlin/girt code by design index.
     * VBA: Sheet5.Range("PDIndex") = value; code = Sheet5.Range("PDCode")
     */
    public function lookupPurlinCode(float $pdIndex): string
    {
        foreach (self::PURLIN_DESIGN_TABLE as $threshold => $code) {
            if ($pdIndex <= $threshold) {
                return $code;
            }
        }

        return 'Z25G'; // Default for very high indices
    }

    /**
     * Lookup endwall column code by design index and finish type.
     * VBA: Sheet5.Range("EWCIndex") = value; code = Sheet5.Range("EWCCode")
     */
    public function lookupEndwallColumnCode(float $ewcIndex, int $cfFinish = 3): string
    {
        $table = $cfFinish === 4 ? self::EWC_TABLE_GALVANIZED : self::EWC_TABLE_PAINTED;

        foreach ($table as $threshold => $code) {
            if ($ewcIndex <= $threshold) {
                return $code;
            }
        }

        return 'BUC'; // Default
    }

    /**
     * Get product weight from MBSDB by code.
     * VBA: weight() function — Sheet7.Range("Code") = Code; weight = offset(0,5)
     */
    public function getProductWeight(string $code): float
    {
        return $this->cachingService->getProductWeight($code);
    }

    /**
     * Get product field from MBSDB by code and column.
     * VBA: DField() function
     */
    public function getProductField(string $code, string $field): mixed
    {
        return $this->cachingService->getProductField($code, $field);
    }

    /**
     * Calculate downspout quantity.
     * VBA: GetDownspoutQty() and CalculateDownspoutQty()
     */
    public function calculateDownspoutSpacing(
        int $roofSlope,
        float $leftRoofWidth,
        float $rightRoofWidth,
        float $baySpacing,
        int $gutterType,
        string $downspoutLocation,
        string $rainfallType,
        float $rainfallIntensity
    ): float {
        // Calculate roof angle
        $roofAngle = atan($roofSlope / 10) * (180 / M_PI);

        // Total width to be drained
        $r = ($leftRoofWidth + $rightRoofWidth) / cos(deg2rad($roofAngle));

        // Gutter and downspout data
        $gutterData = self::GUTTER_TYPES[$gutterType] ?? self::GUTTER_TYPES[1];
        $d = $gutterData['depth'];
        $w = $gutterData['width'];
        $a = $gutterData['area'];

        // Downspout location factor
        $t = ($downspoutLocation === 'End') ? 1 : 2;

        $n = 1; // Number of downspouts

        // Rainfall coefficient
        $c = ($rainfallType === 'Normal') ? (0.8 + (0.2 * 1)) : (0.8 + (0.2 * 2));

        $cosAngle = cos(deg2rad($roofAngle));
        $i = $rainfallIntensity;

        // Gutter capacity based spacing
        $l1 = 0.0585 *
            pow(
                pow($w, 3 / 7) * pow($d, 4 / 7) * pow($cosAngle / ($r * $i), 5 / 14),
                28 / 13
            ) *
            ($t / $c);

        // Downspout capacity based spacing
        $l2 = 4.39 * $a * $n * ($cosAngle / ($c * $i * $r));

        return min($baySpacing, $l1, $l2);
    }

    /**
     * Calculate purlin continuity factor based on number of bays.
     */
    public function getPurlinContinuityFactor(int $numBays): float
    {
        return match (true) {
            $numBays <= 1 => 1.0,
            $numBays === 2 => 1.25,
            $numBays === 3 => 1.08,
            $numBays === 4 => 1.03,
            default => 1.0,
        };
    }

    /**
     * Calculate main frame weight per linear meter.
     * VBA: wplm = (0.1 * MFLoad * TrBay + 0.3) * (2 * span - 9)
     */
    public function calculateFrameWeightPerMeter(float $mfLoad, float $tributaryBay, float $span): float
    {
        return (0.1 * $mfLoad * $tributaryBay + 0.3) * (2 * $span - 9);
    }

    /**
     * Calculate fixed base index factor.
     * VBA: FBIndex = (12 / BEH) ^ 0.15
     */
    public function calculateFixedBaseIndex(string $baseType, float $backEaveHeight): float
    {
        if ($baseType === 'Fixed Base' && $backEaveHeight > 0) {
            return pow(12 / $backEaveHeight, 0.15);
        }

        return 1.0;
    }

    /**
     * Calculate connection plate percentage based on span type and base type.
     * VBA: r_ variable in Main Frame Connections section.
     */
    public function getConnectionPlatePercentage(int $numSpans, string $baseType): float
    {
        if ($numSpans > 1) {
            return ($baseType === 'Pinned Base') ? 14.0 : 18.0;
        }

        return ($baseType === 'Pinned Base') ? 17.0 : 20.0;
    }

    /**
     * Calculate number of bracing bays.
     * VBA: NBrBays = CInt(nbays / 5 + 1)
     */
    public function calculateBracingBays(int $numBays): int
    {
        return (int) round($numBays / 5 + 1);
    }

    /**
     * Calculate number of bracing panels.
     * VBA: NBrPanels = CInt(BEH / 9 + 1) + CInt(wid / 6 + 2)
     */
    public function calculateBracingPanels(float $backEaveHeight, float $frontEaveHeight, float $width, string $frameType): int
    {
        $panels = (int) round($backEaveHeight / 9 + 1) + (int) round($width / 6 + 2);
        if ($frameType !== 'Lean To') {
            $panels += (int) round($frontEaveHeight / 9 + 1);
        }

        return $panels;
    }

    /**
     * Calculate purlin line count.
     * VBA: PLINES = CInt(wid / 1.5 + nPeaks + nValeys)
     */
    public function calculatePurlinLines(float $width, int $numPeaks, int $numValleys): int
    {
        return (int) round($width / 1.5 + $numPeaks + $numValleys);
    }

    /**
     * Calculate girt line count.
     * VBA: GLines = CInt(BEH + FEH / 1.75)
     */
    public function calculateGirtLines(float $backEaveHeight, float $frontEaveHeight): int
    {
        return (int) round($backEaveHeight + $frontEaveHeight / 1.75);
    }

    /**
     * Calculate endwall girt lines.
     * VBA: EWGLines = CInt(Havg / 1.7)
     */
    public function calculateEndwallGirtLines(float $endwallArea, float $width): int
    {
        if ($width <= 0) {
            return 0;
        }

        $avgHeight = $endwallArea / $width;

        return (int) round($avgHeight / 1.7);
    }

    /**
     * Calculate purlin size with overlapping extensions.
     * VBA: Psize = Bays(i,1) + 0.107; if > 6.5 then +0.599; if > 9 then +0.706
     */
    public function calculatePurlinSize(float $baySpacing): float
    {
        $size = $baySpacing + 0.107;
        if ($baySpacing > 6.5) {
            $size += 0.599;
        }
        if ($baySpacing > 9) {
            $size += 0.706;
        }

        return $size;
    }

    /**
     * Calculate flange bracing quantity.
     * VBA: FBQty formula based on building dimensions.
     */
    public function calculateFlangeBracingQty(
        int $numFrames,
        float $backEaveHeight,
        float $frontEaveHeight,
        float $width,
        int $numSpans
    ): int {
        $qty = 2 * (int) round($numFrames * ((($backEaveHeight + $frontEaveHeight) / 1.5 + ($width) / 2) + 4 * $numSpans) / 2);

        if ($width > 50) {
            $qty = 2 * $qty;
        }

        return $qty;
    }

    /**
     * Calculate roof sheeting area.
     * VBA: 1.02 * rafterLength * buildingLength - openings
     * With M45-150 panel profile adjustment: divide by 0.9
     */
    public function calculateRoofSheetingArea(
        float $rafterLength,
        float $buildingLength,
        float $roofOpenings,
        string $panelProfile
    ): float {
        $area = 1.02 * $rafterLength * $buildingLength - $roofOpenings;

        if ($panelProfile !== 'M45-250') {
            $area = $area / 0.9;
        }

        return $area;
    }

    /**
     * Calculate bead mastic quantities for roof sheeting.
     * VBA: RoofSheetingBeadMasticQuantity
     */
    public function calculateBeadMasticQuantity(float $rafterLength, float $buildingLength): array
    {
        // Transverse bead mastic lines
        $bm1Lines = 2 * ($buildingLength + 1);
        $bm1Qty = ($bm1Lines * $rafterLength) / 19;

        // Longitudinal bead mastic lines
        $bm2Lines = (($rafterLength / 9) + 2) * 2;
        $bm2Qty = ($bm2Lines * $buildingLength * 1.219) / 9;

        return [
            'bm1_qty' => $bm1Qty,
            'bm2_qty' => $bm2Qty,
        ];
    }

    /**
     * Calculate portal frame weight.
     * VBA: WPortal formula
     */
    public function calculatePortalWeight(
        float $buildingLength,
        int $numBays,
        float $backEaveHeight,
        float $frontEaveHeight,
        float $endwallArea,
        float $wind,
        int $numPortals
    ): float {
        $weight = 40 * (($buildingLength / $numBays) + ($backEaveHeight ** 2 + $frontEaveHeight ** 2) / 6) * $endwallArea * $wind / 200 / sqrt($numPortals);
        $minWeight = 25 * ($buildingLength / $numBays + $backEaveHeight + $frontEaveHeight);

        return max($weight, $minWeight);
    }

    /**
     * Calculate wind strut requirements.
     * VBA: StrutIndex calculation with iterative sizing.
     *
     * @return array{t200: int, t150: int, t125: int, st_purlin: int, st_clip: int}
     */
    public function calculateWindStruts(float $wind, float $width, float $backEaveHeight, int $numBracingBays): array
    {
        $strutIndex = $wind * $width * $backEaveHeight / $numBracingBays / 4;
        $stClip = 0;
        $stPurlin = 0;
        $st125 = 0;
        $st150 = 0;
        $st200 = 0;

        while ($strutIndex > 4) {
            if ($strutIndex < 10) {
                $stClip += 2;
            } elseif ($strutIndex < 20) {
                $stPurlin += 2;
            } elseif ($strutIndex < 30) {
                $st125 += 2;
            } elseif ($strutIndex < 40) {
                $st150 += 2;
            } else {
                $st200 += 2;
            }
            $strutIndex -= $wind * 12 * $backEaveHeight / $numBracingBays / 4;
        }

        return [
            't200' => $st200,
            't150' => $st150,
            't125' => $st125,
            'st_purlin' => $stPurlin,
            'st_clip' => $stClip,
        ];
    }
}
