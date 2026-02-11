<?php

namespace App\Services\Estimation;

use App\Models\MbsdbProduct;

class DetailGenerator
{
    /** @var array<int, array{description: string, code: string, sales_code: int|string, size: float|int|string, qty: float|int, is_header: bool, cost_code: string}> */
    private array $items = [];

    private int $sortOrder = 0;

    public function __construct(
        private readonly InputParserService $parser,
        private readonly QuickEstCalculator $calculator
    ) {}

    /**
     * Generate complete bill of materials from input data.
     * This is the PHP equivalent of VBA's AddArea_Click().
     *
     * @param  array<string, mixed>  $input  All estimation input fields
     * @return array<int, array<string, mixed>>
     */
    public function generate(array $input): array
    {
        $this->items = [];
        $this->sortOrder = 0;

        $this->generateAreaItems($input);
        $this->generateAccessoryItems($input);

        return $this->items;
    }

    /**
     * Insert an item code into the detail list.
     * VBA: InsCode() — central function for adding items with DB lookups.
     */
    private function insertCode(
        string $description,
        string $code,
        int|string $salesCode,
        float|int|string $size,
        float|int|string $qty,
        string $costCode = ''
    ): void {
        // Description-only header row
        if ($description !== '' && ($code === '' || $code === '-')) {
            $this->sortOrder++;
            $this->items[] = [
                'description' => $description,
                'code' => '-',
                'sales_code' => $salesCode,
                'cost_code' => $costCode,
                'size' => $size,
                'qty' => $qty,
                'is_header' => true,
                'sort_order' => $this->sortOrder,
                'weight_per_unit' => 0,
                'rate' => 0,
                'unit' => '',
            ];
        }

        // Code row with DB lookup
        if ($code !== '' && $code !== '-') {
            $product = $this->lookupProduct($code);
            $this->sortOrder++;
            $this->items[] = [
                'description' => $description ?: ($product['description'] ?? $code),
                'code' => $code,
                'sales_code' => $salesCode,
                'cost_code' => $costCode,
                'size' => $size,
                'qty' => $qty,
                'is_header' => false,
                'sort_order' => $this->sortOrder,
                'weight_per_unit' => $product['weight_per_unit'] ?? 0,
                'rate' => $product['rate'] ?? 0,
                'unit' => $product['unit'] ?? '',
                'surface_area' => ($product['surface_area'] ?? 0) * $size * $qty,
            ];
        }
    }

    /**
     * Lookup product details from MBSDB/SSDB.
     *
     * @return array{description: string, unit: string, weight_per_unit: float, rate: float}
     */
    private function lookupProduct(string $code): array
    {
        static $cache = [];

        if (isset($cache[$code])) {
            return $cache[$code];
        }

        $product = MbsdbProduct::query()->byCode($code)->first();

        if (! $product) {
            $cache[$code] = [
                'description' => $code,
                'unit' => '',
                'weight_per_unit' => 0,
                'rate' => 0,
            ];

            return $cache[$code];
        }

        $metadata = $product->metadata ?? [];
        $cache[$code] = [
            'description' => $product->description ?? $code,
            'unit' => $product->unit ?? '',
            'weight_per_unit' => (float) ($metadata['weight_per_unit'] ?? $product->rate ?? 0),
            'rate' => (float) ($product->rate ?? 0),
            'surface_area' => (float) ($metadata['surface_area'] ?? 0),
        ];

        return $cache[$code];
    }

    /**
     * Generate main building area items.
     * VBA: AddArea_Click() — ~2000 lines of VBA.
     *
     * @param  array<string, mixed>  $input
     */
    private function generateAreaItems(array $input): void
    {
        // Parse all input dimensions
        $spanStr = (string) ($input['spans'] ?? '');
        $bayStr = (string) ($input['bays'] ?? '');
        $slopeStr = (string) ($input['slopes'] ?? '1@0.1');
        $backEaveHeight = (float) ($input['back_eave_height'] ?? 0);
        $frontEaveHeight = (float) ($input['front_eave_height'] ?? 0);
        $frameType = (string) ($input['frame_type'] ?? 'Clear Span');
        $minThickness = (float) ($input['min_thickness'] ?? 6);
        $baseType = (string) ($input['base_type'] ?? 'Pinned Base');
        $doubleWeld = (string) ($input['double_weld'] ?? 'No');
        $lewcs = (string) ($input['left_endwall_columns'] ?? '');
        $leType = (string) ($input['left_endwall_type'] ?? 'Bearing Frame');
        $rewcs = (string) ($input['right_endwall_columns'] ?? '');
        $reType = (string) ($input['right_endwall_type'] ?? 'Bearing Frame');
        $numRoofSagRods = $input['roof_sag_rods'] ?? 0;
        $numWallSagRods = $input['wall_sag_rods'] ?? 0;
        $roofSagRodDia = (int) ($input['roof_sag_rod_dia'] ?? 12);
        $wallSagRodDia = (int) ($input['wall_sag_rod_dia'] ?? 12);
        $buFinish = (string) ($input['bu_finish'] ?? '');
        $cfFinish = (string) ($input['cf_finish'] ?? '');
        $bracingType = (string) ($input['bracing_type'] ?? 'Cables');
        $deadLoad = (float) ($input['dead_load'] ?? 0.1);
        $livePermanent = (float) ($input['live_load_permanent'] ?? 0.57);
        $liveFloor = (float) ($input['live_load_floor'] ?? 0.57);
        $additionalLoad = (float) ($input['additional_load'] ?? 0);
        $windSpeed = (float) ($input['wind_speed'] ?? 130);
        $salesCode = (int) ($input['area_sales_code'] ?? 1);
        $purlinDepth = (int) ($input['purlin_depth'] ?? 200);
        $panelProfile = (string) ($input['panel_profile'] ?? 'M45-250');

        // CF Finish code: 3=painted, 4=galvanized
        $cfFinishCode = (in_array($cfFinish, ['Alu/Zinc', 'Galvanized'])) ? 4 : 3;

        // Calculate loads
        $totalLoadPermanent = $deadLoad + $livePermanent + $additionalLoad;
        $totalLoadFloor = $deadLoad + $liveFloor + $additionalLoad;
        $wind = $windSpeed ** 2 / 20000;

        // Parse dimensions
        $spans = $this->parser->getList($spanStr);
        $bays = $this->parser->getList($bayStr);
        $slopeList = $this->parser->getList($slopeStr);
        $lwcs = ! empty($lewcs) ? $this->parser->getList($lewcs) : [0 => [0 => 0, 1 => 0]];
        $rwcs = ! empty($rewcs) ? $this->parser->getList($rewcs) : [0 => [0 => 0, 1 => 0]];

        // Calculate totals
        $width = $this->parser->calculateTotalFromList($spans);
        $length = $this->parser->calculateTotalFromList($bays);
        $numSpans = $this->parser->calculateCountFromList($spans);
        $numBays = $this->parser->calculateCountFromList($bays);
        $numLwcs = $this->parser->calculateCountFromList($lwcs);
        $numRwcs = $this->parser->calculateCountFromList($rwcs);

        // Calculate slope profile
        $slopeProfile = $this->parser->calculateSlopeProfile(
            $slopeList, $width, $backEaveHeight, $frontEaveHeight
        );
        $rafterLength = $slopeProfile['rafter_length'];
        $endwallArea = $slopeProfile['endwall_area'];
        $peakHeight = $slopeProfile['peak_height'];
        $numPeaks = $slopeProfile['num_peaks'];
        $numValleys = $slopeProfile['num_valleys'];
        $numSlopeSegments = $slopeProfile['num_slope_segments'];

        // Calculate bay sizes
        $baySize = $this->parser->expandList($bays);

        // Calculate column heights
        $expandedSpans = $this->parser->expandList($spans);
        $inCol = $this->parser->calculateColumnHeights(
            $expandedSpans, $slopeProfile['slopes'], $backEaveHeight, $numSlopeSegments
        );

        // Left endwall column heights
        $expandedLwcs = ($numLwcs > 0) ? $this->parser->expandList($lwcs) : [0 => 0];
        $lInCol = ($numLwcs > 0) ? $this->parser->calculateColumnHeights(
            $expandedLwcs, $slopeProfile['slopes'], $backEaveHeight, $numSlopeSegments
        ) : [];

        // Right endwall column heights
        $expandedRwcs = ($numRwcs > 0) ? $this->parser->expandList($rwcs) : [0 => 0];
        $rInCol = ($numRwcs > 0) ? $this->parser->calculateColumnHeights(
            $expandedRwcs, $slopeProfile['slopes'], $backEaveHeight, $numSlopeSegments
        ) : [];

        // Read openings
        $openings = $this->parseOpenings($input, $length, $width, $backEaveHeight, $frontEaveHeight, $peakHeight, $endwallArea);

        // Minimum weight per linear meter from thickness
        $mwplm = sqrt($minThickness / 3.5) * 18.5;

        // ============================================================
        // DESCRIPTION LINE
        // ============================================================
        $desc = ($input['area_description'] ?? 'Building Area')." ({$width} X {$length})";
        $this->insertCode($desc, '-', $salesCode, '', '');

        // ============================================================
        // MAIN FRAMES
        // ============================================================
        $this->generateMainFrames(
            $spans, $bays, $numSpans, $numBays, $width, $length,
            $backEaveHeight, $frontEaveHeight, $frameType, $baseType,
            $doubleWeld, $mwplm, $totalLoadFloor, $deadLoad, $wind,
            $rafterLength, $slopeProfile, $numPeaks, $numValleys,
            $salesCode, $baySize, $inCol, $expandedSpans, $spanStr,
            $openings
        );

        // ============================================================
        // BEARING FRAMES / ENDWALL COLUMNS
        // ============================================================
        $ewml = $this->generateBearingFrames(
            $lwcs, $rwcs, $numLwcs, $numRwcs, $lInCol, $rInCol,
            $leType, $reType, $frameType, $baseType, $doubleWeld,
            $bays, $width, $backEaveHeight, $frontEaveHeight,
            $totalLoadFloor, $wind, $cfFinishCode, $salesCode,
            $expandedLwcs, $expandedRwcs
        );

        // ============================================================
        // BRACING
        // ============================================================
        $this->generateBracing(
            $numBays, $backEaveHeight, $frontEaveHeight, $width, $length,
            $frameType, $bracingType, $salesCode
        );

        // ============================================================
        // WIND STRUTS
        // ============================================================
        $this->generateWindStruts(
            $wind, $width, $backEaveHeight, $frontEaveHeight,
            $numBays, $length, $endwallArea, $cfFinishCode, $salesCode,
            $input
        );

        // ============================================================
        // PURLINS
        // ============================================================
        $this->generatePurlins(
            $bays, $numBays, $width, $length,
            $totalLoadPermanent, $wind,
            $numPeaks, $numValleys,
            $roofSagRodDia, $wallSagRodDia,
            $numRoofSagRods, $numWallSagRods,
            $purlinDepth, $salesCode, $bayStr,
            $openings
        );

        // ============================================================
        // GIRTS
        // ============================================================
        $this->generateGirts(
            $bays, $numBays, $lwcs, $rwcs, $numLwcs, $numRwcs,
            $width, $length, $backEaveHeight, $frontEaveHeight,
            $wind, $endwallArea,
            $roofSagRodDia, $wallSagRodDia,
            $numRoofSagRods, $numWallSagRods,
            $purlinDepth, $salesCode,
            $openings, $bayStr
        );

        // ============================================================
        // ROOF SHEETING
        // ============================================================
        $this->generateRoofSheeting(
            $input, $width, $length, $rafterLength,
            $salesCode, $panelProfile, $openings
        );

        // ============================================================
        // WALL SHEETING
        // ============================================================
        $this->generateWallSheeting(
            $input, $width, $length, $backEaveHeight, $frontEaveHeight,
            $endwallArea, $salesCode, $panelProfile, $openings
        );

        // ============================================================
        // TRIMS
        // ============================================================
        $this->generateTrims(
            $input, $width, $length, $rafterLength,
            $backEaveHeight, $frontEaveHeight,
            $numBays, $numPeaks, $numValleys,
            $salesCode
        );

        // ============================================================
        // INSULATION
        // ============================================================
        $this->generateInsulation(
            $input, $width, $length, $rafterLength,
            $backEaveHeight, $frontEaveHeight,
            $endwallArea, $salesCode, $openings
        );

        // Separator
        $this->insertCode('', '-', $salesCode, 0, 0);

    }

    /**
     * Parse wall/roof openings from input.
     *
     * @return array{
     *     fsw_openings: float, bsw_openings: float,
     *     lew_openings: float, rew_openings: float,
     *     roof_openings: float, total_opening_width: float,
     *     details: array
     * }
     */
    private function parseOpenings(array $input, float $length, float $width, float $beh, float $feh, float $peak, float $ewArea): array
    {
        $fswOpenings = 0.0;
        $bswOpenings = 0.0;
        $lewOpenings = 0.0;
        $rewOpenings = 0.0;
        $roofOpenings = 0.0;
        $totalOpeningWidth = 0.0;
        $details = [];

        $openingsData = $input['openings'] ?? [];
        for ($i = 0; $i < 9; $i++) {
            $opening = $openingsData[$i] ?? null;
            if (! $opening) {
                continue;
            }

            $location = $opening['location'] ?? '';
            $sizeStr = $opening['size'] ?? '';
            $parsed = $this->parser->parseOpeningSize($sizeStr);
            $opWidth = $parsed['width'];
            $opHeight = $parsed['height'];

            // Default full sizes
            if (stripos($sizeStr, 'ful') !== false || stripos($sizeStr, 'x') !== false) {
                if ($opWidth == 0) {
                    $opWidth = match ($location) {
                        'Front Sidewall', 'Back Sidewall' => $length,
                        'Left Endwall', 'Right Endwall' => $width,
                        default => 0,
                    };
                }
                if ($opHeight == 0) {
                    $opHeight = match ($location) {
                        'Front Sidewall' => $feh,
                        'Back Sidewall' => $beh,
                        'Left Endwall', 'Right Endwall' => $peak,
                        default => 0,
                    };
                }
            }

            match ($location) {
                'Front Sidewall' => $fswOpenings += $opWidth * $opHeight,
                'Back Sidewall' => $bswOpenings += $opWidth * $opHeight,
                'Left Endwall' => $lewOpenings += $opWidth * $opHeight,
                'Right Endwall' => $rewOpenings += $opWidth * $opHeight,
                default => null,
            };

            $totalOpeningWidth += $opWidth;

            $details[] = [
                'location' => $location,
                'width' => $opWidth,
                'height' => $opHeight,
                'purlin_support' => $opening['purlin_support'] ?? 0,
                'bracing' => $opening['bracing'] ?? 0,
            ];
        }

        // Cap openings to wall areas
        $lewOpenings = min($lewOpenings, $ewArea);
        $rewOpenings = min($rewOpenings, $ewArea);
        $bswOpenings = min($bswOpenings, $length * $beh);
        $fswOpenings = min($fswOpenings, $length * $feh);
        $totalOpeningWidth = min($totalOpeningWidth, 2 * ($length + $width));

        return [
            'fsw_openings' => $fswOpenings,
            'bsw_openings' => $bswOpenings,
            'lew_openings' => $lewOpenings,
            'rew_openings' => $rewOpenings,
            'roof_openings' => $roofOpenings,
            'total_opening_width' => $totalOpeningWidth,
            'details' => $details,
        ];
    }

    /**
     * Generate main frame items.
     */
    private function generateMainFrames(
        array $spans, array $bays, int $numSpans, int $numBays,
        float $width, float $length,
        float $beh, float $feh, string $frameType, string $baseType,
        string $doubleWeld, float $mwplm,
        float $totalLoadFloor, float $deadLoad, float $wind,
        float $rafterLength, array $slopeProfile, int $numPeaks, int $numValleys,
        int $salesCode, array $baySize, array $inCol, array $expandedSpans,
        string $spanStr, array $openings
    ): void {
        $nFrames = $numBays - 1;
        $leType = '';
        $reType = '';
        // Extract LE/RE types from context — they affect nFrames
        // (This is handled by the caller or the input data)
        // For simplicity, we'll count from the input
        if (isset($this->currentInput['left_endwall_type'])) {
            $leType = $this->currentInput['left_endwall_type'];
        }
        if (isset($this->currentInput['right_endwall_type'])) {
            $reType = $this->currentInput['right_endwall_type'];
        }

        if (in_array($reType, ['Main Frame', 'MF 1/2 Loaded'])) {
            $nFrames++;
        }
        if (in_array($leType, ['Main Frame', 'MF 1/2 Loaded'])) {
            $nFrames++;
        }

        $cFactor = $this->calculator->getPurlinContinuityFactor($numBays);
        $fbIndex = $this->calculator->calculateFixedBaseIndex($baseType, $beh);

        $mfWeight = array_fill(0, $numBays + 1, 0.0);
        $mfc = array_fill(1, 5, 0); // Connection type counters
        $mfb = array_fill(1, 5, 0); // Bolt size counters
        $mfab = array_fill(1, 5, 0); // Anchor bolt counters

        $frameLength = 0.0;
        $excwplm = 0.0;

        // Loop frames (interior frames between bays)
        for ($f = 1; $f < $numBays; $f++) {
            $frWeight = 0.0;
            $leftBay = $baySize[$f] ?? 0;
            $rightBay = $baySize[$f + 1] ?? 0;
            $trBay = $cFactor * ($leftBay / 2 + $rightBay / 2);

            // Rafters
            for ($i = 1; $i <= $numSpans; $i++) {
                $spanWidth = $inCol[$i]['span'] ?? ($expandedSpans[$i] ?? 0);
                $mfLoad = $totalLoadFloor;
                if (0.75 * ($wind - $deadLoad) > $totalLoadFloor) {
                    $mfLoad = 0.75 * ($wind - $deadLoad);
                }

                $wplm = $this->calculator->calculateFrameWeightPerMeter($mfLoad, $trBay, $spanWidth);
                if ($wplm < $mwplm) {
                    $wplm = $mwplm;
                }
                $frWeight += $spanWidth * $wplm;

                // Rafter connections
                $nrc = 2 * (int) ($spanWidth / 10);
                if ($nrc >= 2) {
                    $nrc -= 2;
                }
                $ct = $this->parser->getConnectionType($wplm);
                $mfc[$ct] = ($mfc[$ct] ?? 0) + $nrc;
                $mfb[$ct] = ($mfb[$ct] ?? 0) + $nrc * (8 + 2 * ($ct - 2)) / 2;
            }

            // Adjust for sloped length
            $frWeight = $frWeight * $rafterLength / $width;
            $excwplm = $frWeight / $width;
            $frameLength = $rafterLength - 0.4;

            // Interior columns
            for ($i = 1; $i < $numSpans; $i++) {
                $colHeight = $inCol[$i]['height'] ?? 0;
                $spanLeft = $inCol[$i]['span'] ?? ($expandedSpans[$i] ?? 0);
                $spanRight = $inCol[$i + 1]['span'] ?? ($expandedSpans[$i + 1] ?? 0);

                $wplm = 0.002 * $fbIndex * ($mfLoad * $trBay * ($spanLeft + $spanRight) / 2 * $colHeight ** 2);
                if ($wplm < $mwplm) {
                    $wplm = $mwplm;
                }
                $frWeight += $colHeight * $wplm;
                $frameLength += $colHeight - 0.8;

                // Interior column connections
                $nrc = 3;
                if ($colHeight > 18) {
                    $nrc += 2;
                }
                $ct = $this->parser->getConnectionType($wplm);
                $mfc[$ct] = ($mfc[$ct] ?? 0) + $nrc;
                $mfb[$ct] = ($mfb[$ct] ?? 0) + (8 + 2 * ($ct - 2)) * ($nrc - 1) / 2;

                // Interior column anchor bolts
                if ($baseType === 'Fixed Base') {
                    $dim = $this->parser->getBuildingDimension($spanStr);
                    $fbt = $this->parser->getFixedBaseType($dim['max_span']);
                    $mfab[$fbt['connection_type']] = ($mfab[$fbt['connection_type']] ?? 0) + $fbt['bolt_count'];
                } else {
                    $mfab[$ct] = ($mfab[$ct] ?? 0) + 4;
                    if ($ct > 4) {
                        $mfab[$ct] += 2;
                    }
                }
            }

            // Exterior columns
            $opFactor = 1 + sqrt($openings['bsw_openings'] / $length / $beh) / 2;
            $frWeight += $beh * $excwplm * $fbIndex * $opFactor;
            $frameLength += $beh - 0.6;

            $opFactor = 1 + sqrt($openings['fsw_openings'] / $length / $feh) / 2;
            if ($frameType !== 'Lean To') {
                $frWeight += $feh * $excwplm * $fbIndex * $opFactor;
                $frameLength += $feh - 0.6;
            }

            $mfWeight[$f] = (int) ($frWeight / 10) * 10;

            // Exterior column connections
            $numExteriorColumns = ($frameType !== 'Lean To') ? 2 : 1;
            $nc = 3 * $numExteriorColumns;
            $fbab = 2;
            if ($baseType !== 'Fixed Base') {
                $nc = 2 * $numExteriorColumns;
                $mfc[1] = ($mfc[1] ?? 0) + $numExteriorColumns;
                $fbab = 1;
            }
            $ct = $this->parser->getConnectionType($excwplm);
            $mfc[$ct] = ($mfc[$ct] ?? 0) + $nc;
            $mfb[$ct] = ($mfb[$ct] ?? 0) + (8 + 2 * ($ct - 2)) * ($nc - 1) / 2;

            // Exterior column anchor bolts
            if ($baseType === 'Pinned Base') {
                $mfab[$ct] = ($mfab[$ct] ?? 0) + (4 + 2 * (int) ($ct / 4)) * $fbab * $numExteriorColumns;
            } else {
                $dim = $this->parser->getBuildingDimension($spanStr);
                $fbt = $this->parser->getFixedBaseType($dim['max_span']);
                $mfab[$fbt['connection_type']] = ($mfab[$fbt['connection_type']] ?? 0) + (2 * $fbt['bolt_count']);
            }

            // Peak and valley connections
            $ct = $this->parser->getConnectionType($excwplm);
            $mfc[$ct] = ($mfc[$ct] ?? 0) + 2 * $numPeaks + 2 * $numValleys;
            $mfb[$ct] = ($mfb[$ct] ?? 0) + ($numPeaks + $numValleys) * (8 + 2 * ($ct - 2));
        }

        // Handle LE/RE as Main Frame
        if ($leType === 'Main Frame') {
            $mfWeight[0] = $mfWeight[1];
        }
        if ($reType === 'Main Frame') {
            $mfWeight[$numBays] = $mfWeight[$numBays - 1];
        }

        $hmf = 0.65;
        if ($hmf < $mwplm / max($excwplm, 0.001)) {
            $hmf = $mwplm / max($excwplm, 0.001);
        }
        if ($leType === 'MF 1/2 Loaded') {
            $mfWeight[0] = (int) ($mfWeight[1] * $hmf / 10) * 10;
        }
        if ($reType === 'MF 1/2 Loaded') {
            $mfWeight[$numBays] = (int) ($mfWeight[$numBays - 1] * $hmf / 10) * 10;
        }

        // Insert main frames - group by weight
        $des = 'Main Frames';
        $totalMfWeight = 0.0;
        $mfQty = array_fill(0, $numBays + 1, 0);

        for ($i = 0; $i <= $numBays; $i++) {
            $qty = 1;
            for ($j = 0; $j <= $numBays; $j++) {
                if ($mfWeight[$i] == $mfWeight[$j]) {
                    if ($j < $i) {
                        $qty = 0;
                    }
                    $mfQty[$i]++;
                }
            }
            $mfQty[$i] *= $qty;

            if ($mfWeight[$i] > 0 && $mfQty[$i] > 0) {
                $this->insertCode($des, 'BU', $salesCode, $mfWeight[$i], $mfQty[$i]);
                $des = '';
                $totalMfWeight += $mfWeight[$i] * $mfQty[$i];
            }
        }

        // Double side welding
        if ($doubleWeld === 'Yes') {
            $this->insertCode('', 'DSW', $salesCode, 1, (int) ($nFrames * $frameLength));
        }
        $this->insertCode('', 'BuLeng', $salesCode, 1, (int) ($nFrames * $frameLength));

        // Main frame connections (plate weight as percentage)
        $r = $this->calculator->getConnectionPlatePercentage($numSpans, $baseType);
        $connectionWeight = $totalMfWeight * $r / 100;
        $this->insertCode("Main Frame Connections - Weight ({$r}%)", 'ConPlates', $salesCode, 1, $connectionWeight);

        // Main frame bolts
        $mfb[1] = 4 * (int) (($mfb[1] + $mfb[2] / 2 + ($mfb[3] ?? 0) / 4) / 4);
        $mfb[2] = 4 * (int) (($mfb[2] / 2 + ($mfb[3] ?? 0) / 4) / 4);
        $mfb[3] = 4 * (int) ((($mfb[3] ?? 0) / 2 + ($mfb[4] ?? 0) / 4 + ($mfb[5] ?? 0) / 4) / 4);
        $mfb[4] = 4 * (int) ((($mfb[4] ?? 0) / 2 + ($mfb[5] ?? 0) / 2) / 4);
        $mfb[5] = 4 * (int) ((($mfb[5] ?? 0) / 2) / 4);

        $boltDes = 'Main Frame Bolts';
        $boltCodes = [1 => 'HSB16', 2 => 'HSB2060', 3 => 'HSB2480', 4 => 'HSB27', 5 => 'HSB30'];
        foreach ($boltCodes as $idx => $boltCode) {
            if (($mfb[$idx] ?? 0) > 0) {
                $this->insertCode($boltDes, $boltCode, $salesCode, 1, $mfb[$idx]);
                $boltDes = '';
            }
        }

        // Flange bracing
        $fbQty = $this->calculator->calculateFlangeBracingQty($nFrames, $beh, $feh, $width, $numSpans);
        if ($fbQty > 0) {
            $fbCode = ($width <= 35) ? 'FBA' : 'FBA2';
            $this->insertCode('Flange Bracing', $fbCode, $salesCode, 1, $fbQty);
            $this->insertCode('', 'HSB12', $salesCode, 1, 2 * $fbQty);
        }

        // Anchor bolts
        $abDes = 'Anchor Bolts';
        $abCodes = [1 => 'AB16', 2 => 'AB20', 3 => 'AB24', 4 => 'AB30', 5 => 'AB36'];
        foreach ($abCodes as $idx => $abCode) {
            if (($mfab[$idx] ?? 0) > 0) {
                $this->insertCode($abDes, $abCode, $salesCode, 1, $mfab[$idx]);
                $abDes = '';
            }
        }
    }

    /**
     * Generate bearing frame items.
     */
    private function generateBearingFrames(
        array $lwcs, array $rwcs, int $numLwcs, int $numRwcs,
        array $lInCol, array $rInCol,
        string $leType, string $reType, string $frameType, string $baseType,
        string $doubleWeld,
        array $bays, float $width,
        float $beh, float $feh,
        float $totalLoadFloor, float $wind, int $cfFinishCode,
        int $salesCode,
        array $expandedLwcs, array $expandedRwcs
    ): float {
        $ewml = 0.0;
        $des = 'Bearing Frame';
        $bfLoad = max($totalLoadFloor, $wind - ($totalLoadFloor - $wind));

        if (in_array($leType, ['Bearing Frame', 'False Rafter'])) {
            $numGroups = (int) $lwcs[0][0];
            for ($i = 1; $i <= $numGroups; $i++) {
                $spanWidth = $lwcs[$i][1];
                $height = $lInCol[$i]['height'] ?? $beh;
                $ewcIndex = $bfLoad * $spanWidth ** 2 * ($bays[1][1] ?? 0);
                $code = $this->calculator->lookupEndwallColumnCode($ewcIndex, $cfFinishCode);

                if ($i > 1) {
                    $des = '';
                }
                if (str_starts_with($code, 'BU')) {
                    $ewml += $height * $spanWidth;
                }
                $this->insertCode($des, $code, $salesCode, $height, (int) $lwcs[$i][0]);

                // End plates for large widths
                if (($width / 2) > 12) {
                    $endPlateQty = 2 * 2 * (int) (($width / 2) / 12);
                    $this->insertCode('', 'MFC1', $salesCode, 1, $endPlateQty);
                }
            }
        }

        if (in_array($reType, ['Bearing Frame', 'False Rafter'])) {
            $numGroups = (int) $rwcs[0][0];
            $lastBaySpacing = $bays[(int) $bays[0][0]][1] ?? 0;
            for ($i = 1; $i <= $numGroups; $i++) {
                $spanWidth = $rwcs[$i][1];
                $height = $rInCol[$i]['height'] ?? $beh;
                $ewcIndex = $bfLoad * $spanWidth ** 2 * $lastBaySpacing;
                $code = $this->calculator->lookupEndwallColumnCode($ewcIndex, $cfFinishCode);

                if ($i > 1) {
                    $des = '';
                }
                if (str_starts_with($code, 'BU')) {
                    $ewml += $height * $spanWidth;
                }
                $this->insertCode($des, $code, $salesCode, $height, (int) $rwcs[$i][0]);

                if (($width / 2) > 12) {
                    $endPlateQty = 2 * 2 * (int) (($width / 2) / 12);
                    $this->insertCode('', 'MFC1', $salesCode, 1, $endPlateQty);
                }
            }
        }

        // Endwall columns (when not False Rafter)
        if ($leType !== 'False Rafter' && $numLwcs > 1) {
            $colDes = 'Left Endwall Columns';
            for ($i = 1; $i < $numLwcs; $i++) {
                $height = $lInCol[$i]['height'] ?? 0;
                $leftSpan = $expandedLwcs[$i] ?? 0;
                $rightSpan = $expandedLwcs[$i + 1] ?? 0;
                $ewcIndex = $wind * $height ** 2 * ($leftSpan + $rightSpan) / 2;
                $code = $this->calculator->lookupEndwallColumnCode($ewcIndex, $cfFinishCode);

                if ($i > 1) {
                    $colDes = '';
                }
                if (str_starts_with($code, 'BU')) {
                    $ewml += $height;
                }
                $this->insertCode($colDes, $code, $salesCode, $height, 1);
            }
        }

        if ($reType !== 'False Rafter' && $numRwcs > 1) {
            $colDes = 'Right Endwall Columns';
            for ($i = 1; $i < $numRwcs; $i++) {
                $height = $rInCol[$i]['height'] ?? 0;
                $leftSpan = $expandedRwcs[$i] ?? 0;
                $rightSpan = $expandedRwcs[$i + 1] ?? 0;
                $ewcIndex = $wind * $height ** 2 * ($leftSpan + $rightSpan) / 2;
                $code = $this->calculator->lookupEndwallColumnCode($ewcIndex, $cfFinishCode);

                if ($i > 1) {
                    $colDes = '';
                }
                if (str_starts_with($code, 'BU')) {
                    $ewml += $height;
                }
                $this->insertCode($colDes, $code, $salesCode, $height, 1);
            }
        }

        // Endwall manufacturing
        if ($ewml > 0) {
            $this->insertCode('Endwall Manufacturing', 'BuLeng', $salesCode, 1, $ewml);
            if ($doubleWeld === 'Yes') {
                $this->insertCode('', 'DSW', $salesCode, 1, $ewml);
            }
        }

        // Bearing frame connections
        $ewc = 2 * $numLwcs + 2 * $numRwcs - 4;
        $ewb = $ewc * 4;
        $ewab = 0;

        if ($leType !== 'False Rafter') {
            $ewc += $numLwcs - 1;
            $ewab = 4 * $numLwcs - 4;
        } else {
            $ewc += 2;
        }
        if ($reType !== 'False Rafter') {
            $ewc += $numRwcs - 1;
            $ewab += 4 * $numRwcs - 4;
        } else {
            $ewc += 2;
        }
        if ($leType === 'Bearing Frame') {
            $ewc += 4;
            $ewab += 4;
        }
        if ($reType === 'Bearing Frame') {
            $ewc += 4;
            $ewab += 4;
        }

        $bfcDes = 'Bearing Frame Connections';
        if ($ewc > 0) {
            $this->insertCode($bfcDes, 'EWC', $salesCode, 1, $ewc);
            $bfcDes = '';
        }
        if ($ewb > 0) {
            $this->insertCode($bfcDes, 'HSB1250', $salesCode, 1, $ewb);
            $bfcDes = '';
        }
        if ($ewab > 0) {
            $this->insertCode($bfcDes, 'AB16', $salesCode, 1, $ewab);
        }

        return $ewml;
    }

    /**
     * Generate bracing items.
     */
    private function generateBracing(
        int $numBays, float $beh, float $feh, float $width, float $length,
        string $frameType, string $bracingType, int $salesCode
    ): void {
        $nBrBays = $this->calculator->calculateBracingBays($numBays);
        $nBrPanels = $this->calculator->calculateBracingPanels($beh, $feh, $width, $frameType);
        $nBrP = $nBrPanels;

        if ($bracingType === 'Cables') {
            $this->insertCode('Bracing', 'CBR', $salesCode, 1, 4 * $nBrBays);
            $nBrP -= 4;
            if ($nBrP > 4) {
                $this->insertCode('', 'CBR', $salesCode, 1, 4 * $nBrBays);
            } elseif ($nBrP > 0) {
                $this->insertCode('', 'CBR', $salesCode, 1, $nBrP * $nBrBays);
            }
            $nBrP -= 4;
            if ($nBrP > 4) {
                $this->insertCode('', 'CBR', $salesCode, 1, 4 * $nBrBays);
            } elseif ($nBrP > 0) {
                $this->insertCode('', 'CBR', $salesCode, 1, $nBrP * $nBrBays);
            }
            $nBrP -= 4;
            if ($nBrP > 0) {
                $this->insertCode('', 'CBR', $salesCode, 1, $nBrP * $nBrBays);
            }
        }

        if ($bracingType === 'Rods') {
            $this->insertCode('Bracing', 'RBR22', $salesCode, 1, 4 * $nBrBays);
            $nBrP -= 4;
            if ($nBrP > 4) {
                $this->insertCode('', 'RBR22', $salesCode, 1, 4 * $nBrBays);
            } elseif ($nBrP > 0) {
                $this->insertCode('', 'RBR22', $salesCode, 1, $nBrP * $nBrBays);
            }
            $nBrP -= 4;
            if ($nBrP > 4) {
                $this->insertCode('', 'RBR22', $salesCode, 1, 4 * $nBrBays);
            } elseif ($nBrP > 0) {
                $this->insertCode('', 'RBR22', $salesCode, 1, $nBrP * $nBrBays);
            }
            $nBrP -= 4;
            if ($nBrP > 0) {
                $this->insertCode('', 'RBR22', $salesCode, 1, $nBrP * $nBrBays);
            }
        }

        if ($bracingType === 'Angles') {
            $brAngLen = (int) (10 * sqrt(($length / $numBays) ** 2 + 36)) / 10;
            $this->insertCode('Wind Bracing', 'FBA2', $salesCode, 1, 4 * $nBrBays * 2 * $brAngLen);
            $nBrP -= 4;
            if ($nBrP > 4) {
                $this->insertCode('', 'FBA3', $salesCode, 1, 4 * $nBrBays * 2 * $brAngLen);
            } elseif ($nBrP > 0) {
                $this->insertCode('', 'FBA3', $salesCode, 1, $nBrP * $nBrBays * 2 * $brAngLen);
            }
            $nBrP -= 4;
            if ($nBrP > 4) {
                $this->insertCode('', 'FBA3', $salesCode, 1, 4 * $nBrBays * 2 * $brAngLen);
            } elseif ($nBrP > 0) {
                $this->insertCode('', 'CRA', $salesCode, 1, $nBrP * $nBrBays * 2 * $brAngLen);
            }
            $nBrP -= 4;
            if ($nBrP > 0) {
                $this->insertCode('', 'CRA', $salesCode, 1, $nBrP * $nBrBays * 2 * $brAngLen);
            }
            $this->insertCode('', 'BrGu', $salesCode, 1, $nBrPanels * $nBrBays * 3 + 8);
            $this->insertCode('', 'HSB20', $salesCode, 1, 14 * $nBrPanels * $nBrBays);
        }
    }

    /**
     * Generate wind strut items.
     */
    private function generateWindStruts(
        float $wind, float $width, float $beh, float $feh,
        int $numBays, float $length, float $endwallArea,
        int $cfFinishCode, int $salesCode, array $input
    ): void {
        $nBrBays = $this->calculator->calculateBracingBays($numBays);
        $struts = $this->calculator->calculateWindStruts($wind, $width, $beh, $nBrBays);

        $stDes = 'Wind Strut Members';
        if ($struts['t200'] > 0) {
            $this->insertCode($stDes, 'T200', $salesCode, (int) (10 * $length / $numBays) / 10, $struts['t200'] * $nBrBays);
            $stDes = '';
        }
        if ($struts['t150'] > 0) {
            $this->insertCode($stDes, 'T150', $salesCode, (int) (10 * $length / $numBays) / 10, $struts['t150'] * $nBrBays);
            $stDes = '';
        }
        if ($struts['t125'] > 0) {
            $this->insertCode($stDes, 'T125', $salesCode, (int) (10 * $length / $numBays) / 10, $struts['t125'] * $nBrBays);
            $stDes = '';
        }

        $stPCode = ($cfFinishCode === 3) ? 'Z25P' : 'Z25G';
        if ($struts['st_purlin'] > 0) {
            $this->insertCode($stDes, $stPCode, $salesCode, (int) (10 * $length / $numBays) / 10, $struts['st_purlin'] * $nBrBays);
            $stDes = '';
        }
        if ($struts['st_purlin'] + $struts['st_clip'] > 0) {
            $this->insertCode($stDes, 'CFClip', $salesCode, 1, 2 * $nBrBays * ($struts['st_clip'] + 2 * $struts['st_purlin']));
            $stDes = '';
        }
        if ($struts['t200'] + $struts['t150'] + $struts['t125'] > 0) {
            $this->insertCode($stDes, 'MFC1', $salesCode, 1, 2 * ($struts['t150'] + $struts['t200'] + $struts['t125']) * $nBrBays);
            $stDes = '';
        }
        if ($struts['t150'] + $struts['t200'] + $struts['t125'] > 0) {
            $this->insertCode('', 'HSB16', $salesCode, 1, 8 * ($struts['t200'] + $struts['t150'] + $struts['t125']) * $nBrBays);
        }
        if ($struts['st_clip'] + $struts['st_purlin'] > 0) {
            $this->insertCode('', 'HSB12', $salesCode, 1, 8 * ($struts['st_clip'] + $struts['st_purlin']) * $nBrBays);
        }

        // Portals
        $numPortal = 0;
        if (($input['left_endwall_portal'] ?? '') === 'Portal') {
            $numPortal++;
        }
        if (($input['right_endwall_portal'] ?? '') === 'Portal') {
            $numPortal++;
        }
        $numPortal *= $nBrBays;

        if ($numPortal > 0) {
            $wPortal = $this->calculator->calculatePortalWeight(
                $length, $numBays, $beh, $feh, $endwallArea, $wind, $numPortal
            );
            $ct = $this->parser->getConnectionType($wPortal / ($length / $numBays + $beh + $feh));

            $this->insertCode('', 'BUPortal', $salesCode, 10 * (int) ($wPortal / 10), $numPortal);
            $this->insertCode('', 'BuLeng', $salesCode, 1, (int) ($numPortal * ($length / $numBays - 0.6 + $beh / 2 + $feh / 2)));
            $this->insertCode('', 'MFC'.$ct, $salesCode, 1, 4 * $numPortal);
            $boltCode = ['HSB16', 'HSB20', 'HSB24', 'HSB27', 'HSB30'][$ct - 1] ?? 'HSB16';
            $this->insertCode('', $boltCode, $salesCode, 1, 16 * $numPortal);
            $this->insertCode('', 'HSB16', $salesCode, 1, $numPortal * (8 + 4 * (int) ($beh / 2)));
        }
    }

    /**
     * Generate purlin items (end bay, interior bay).
     */
    private function generatePurlins(
        array $bays, int $numBays, float $width, float $length,
        float $totalLoadPermanent, float $wind,
        int $numPeaks, int $numValleys,
        int $roofSagRodDia, int $wallSagRodDia,
        $numRoofSagRods, $numWallSagRods,
        int $purlinDepth, int $salesCode, string $bayStr,
        array $openings
    ): void {
        $loadP = max($totalLoadPermanent, $wind);
        $pLines = $this->calculator->calculatePurlinLines($width, $numPeaks, $numValleys);
        $pgBolts = 0;
        $nSagRods = 0;
        $dim = $this->parser->getBuildingDimension($bayStr);
        $pClipQty = $pLines * ($dim['bay_count'] + 1);

        // End bay purlins
        $pdIndex = 1.25 * $loadP * ($bays[1][1] ?? 0) ** 2;
        $pdCode = $this->calculator->lookupPurlinCode($pdIndex);
        $qty = $pLines;
        $pSize = $this->calculator->calculatePurlinSize($bays[1][1] ?? 0);

        $esQty = 2;
        if ($bays[0][0] == 1 && ($bays[1][0] ?? 0) > 1) {
            $qty *= 2;
            $esQty = 4;
        }

        if ($numRoofSagRods === 'A' && $pSize > 8.598) {
            $nSagRods += $qty + 1;
        }
        if (is_numeric($numRoofSagRods) && $numRoofSagRods > 0) {
            $nSagRods += $numRoofSagRods * ($qty + 1);
        }

        // Select purlin code based on depth
        $endBayPurlinCode = match ($purlinDepth) {
            250 => '25Z25G',
            360 => 'M20G',
            default => $pdCode,
        };

        $this->insertCode('End Bay Purlins', $endBayPurlinCode, $salesCode, $pSize, $qty);

        // Eave strut
        $eaveCode = match ($purlinDepth) {
            250 => '25Z25G',
            360 => 'M20G',
            default => $pdCode,
        };
        $this->insertCode('', $eaveCode, $salesCode, $pSize, $esQty);
        $this->insertCode('', 'Gang', $salesCode, $bays[1][1] ?? 0, $esQty);

        $pgBolts += $qty * 2;

        // Second end bay if different
        if ($bays[0][0] > 1) {
            $lastBayIdx = (int) $bays[0][0];
            $pdIndex = 1.25 * $loadP * ($bays[$lastBayIdx][1] ?? 0) ** 2;
            $pdCode = $this->calculator->lookupPurlinCode($pdIndex);
            $pSize = $this->calculator->calculatePurlinSize($bays[$lastBayIdx][1] ?? 0);

            $endBayPurlinCode = match ($purlinDepth) {
                250 => '25Z25G',
                360 => 'M20G',
                default => $pdCode,
            };

            $this->insertCode('', $endBayPurlinCode, $salesCode, $pSize, $qty);
            $this->insertCode('', $eaveCode, $salesCode, $pSize, 2 * ($bays[$lastBayIdx][0] ?? 1));
            $this->insertCode('', 'Gang', $salesCode, $bays[$lastBayIdx][1] ?? 0, 2);

            $pgBolts += $qty * 2;
        }

        // Interior bay purlins
        if ($numBays > 2) {
            $intPurlinDes = 'Interior Bay Purlins';
            $baysCopy = $bays;
            $baysCopy[1][0]--;
            $lastBayIdx = (int) $baysCopy[0][0];
            $baysCopy[$lastBayIdx][0]--;

            for ($i = 1; $i <= (int) $baysCopy[0][0]; $i++) {
                if (($baysCopy[$i][0] ?? 0) <= 0) {
                    continue;
                }

                $pdIndex = $loadP * ($baysCopy[$i][1] ?? 0) ** 2;
                $pdCode = $this->calculator->lookupPurlinCode($pdIndex);
                $pSize = $this->calculator->calculatePurlinSize($baysCopy[$i][1] ?? 0);
                $qty = (int) $baysCopy[$i][0] * $pLines;

                $intPurlinCode = match ($purlinDepth) {
                    250 => '250Z20G',
                    360 => 'M18G',
                    default => $pdCode,
                };

                $this->insertCode($intPurlinDes, $intPurlinCode, $salesCode, $pSize, $qty);
                $intPurlinDes = '';

                if ($numRoofSagRods === 'A' && $pSize > 8.598) {
                    $nSagRods += $qty + 1;
                }
                if (is_numeric($numRoofSagRods) && $numRoofSagRods > 0) {
                    $nSagRods += $numRoofSagRods * ($qty + 1);
                }

                $pgBolts += $qty * 8;

                // Eave strut for interior bays
                $intEaveCode = match ($purlinDepth) {
                    250 => '250Z20G',
                    360 => 'M18G',
                    default => $pdCode,
                };
                $this->insertCode('', $intEaveCode, $salesCode, $pSize, 2 * (int) $baysCopy[$i][0]);
                $this->insertCode('', 'Gang', $salesCode, $baysCopy[$i][1] ?? 0, 2 * (int) $baysCopy[$i][0]);
            }
        }

        // Store pgBolts and nSagRods for girts section
        $this->pgBolts = $pgBolts;
        $this->nSagRods = $nSagRods;
        $this->pClipQty = $pClipQty;
        $this->pLines = $pLines;
    }

    // Temporary properties for cross-method state
    private int $pgBolts = 0;

    private int $nSagRods = 0;

    private int $pClipQty = 0;

    private int $pLines = 0;

    private array $currentInput = [];

    /**
     * Generate girt items (sidewall and endwall).
     */
    private function generateGirts(
        array $bays, int $numBays,
        array $lwcs, array $rwcs, int $numLwcs, int $numRwcs,
        float $width, float $length,
        float $beh, float $feh,
        float $wind, float $endwallArea,
        int $roofSagRodDia, int $wallSagRodDia,
        $numRoofSagRods, $numWallSagRods,
        int $purlinDepth, int $salesCode,
        array $openings, string $bayStr
    ): void {
        $pgBolts = $this->pgBolts;
        $nSagRods = $this->nSagRods;
        $wSagRods = 0;

        $gLines = $this->calculator->calculateGirtLines($beh, $feh);
        $swgClipQty = 0;
        $lewgClipQty = 0;
        $rewgClipQty = 0;

        $dim = $this->parser->getBuildingDimension($bayStr);
        $swgClipQty = 2 * (int) ($beh / 1.75) * ($dim['bay_count'] + 1);

        // End bay sidewall girts
        $pdIndex = 1.25 * $wind * ($bays[1][1] ?? 0) ** 2;
        $pdCode = match ($purlinDepth) {
            250 => '25Z25G',
            360 => 'M20G',
            default => $this->calculator->lookupPurlinCode($pdIndex),
        };

        $girtOpeningFactor = 1 - ($openings['bsw_openings'] + $openings['fsw_openings']) / max(($length * $beh + $length * $feh), 0.001);
        $qty = (int) round($gLines * $girtOpeningFactor);
        $pSize = $this->calculator->calculatePurlinSize($bays[1][1] ?? 0);

        if ($bays[0][0] == 1 && ($bays[1][0] ?? 0) > 1) {
            $qty *= 2;
        }
        if ($qty > 0) {
            $this->insertCode('End Bay Sidewall Girts', $pdCode, $salesCode, $pSize, $qty);
        }
        $pgBolts += $qty * 2;

        // Second end bay girts
        if ($bays[0][0] > 1) {
            $lastBayIdx = (int) $bays[0][0];
            $pdIndex = 1.25 * $wind * ($bays[$lastBayIdx][1] ?? 0) ** 2;
            $pdCode = match ($purlinDepth) {
                250 => '25Z25G',
                360 => 'M20G',
                default => $this->calculator->lookupPurlinCode($pdIndex),
            };
            $pSize = $this->calculator->calculatePurlinSize($bays[$lastBayIdx][1] ?? 0);
            if ($qty > 0) {
                $this->insertCode('', $pdCode, $salesCode, $pSize, $qty);
            }
            $pgBolts += $qty * 2;
        }

        // Interior bay sidewall girts
        if ($numBays > 2) {
            $intGirtDes = 'Interior Bay Sidewall Girts';
            $baysCopy = $bays;
            $baysCopy[1][0]--;
            $lastBayIdx = (int) $baysCopy[0][0];
            $baysCopy[$lastBayIdx][0]--;

            for ($i = 1; $i <= (int) $baysCopy[0][0]; $i++) {
                if (($baysCopy[$i][0] ?? 0) <= 0) {
                    continue;
                }

                $pdIndex = $wind * ($baysCopy[$i][1] ?? 0) ** 2;
                $pdCode = match ($purlinDepth) {
                    250 => '250Z20G',
                    360 => 'M18G',
                    default => $this->calculator->lookupPurlinCode($pdIndex),
                };
                $pSize = $this->calculator->calculatePurlinSize($baysCopy[$i][1] ?? 0);
                $qty = (int) round($baysCopy[$i][0] * $gLines * $girtOpeningFactor);

                if ($qty > 0) {
                    $this->insertCode($intGirtDes, $pdCode, $salesCode, $pSize, $qty);
                    $intGirtDes = '';
                }
                $pgBolts += $qty * 8;
            }
        }

        // Endwall girts
        $gClips = 0;
        $ewgLines = $this->calculator->calculateEndwallGirtLines($endwallArea, $width);
        $ewGirtDes = 'End Wall Girts';

        // Left endwall girts
        $numLwcsGroups = (int) ($lwcs[0][0] ?? 0);
        for ($i = 1; $i <= $numLwcsGroups; $i++) {
            $pdIndex = 2.01 * $wind * ($lwcs[$i][1] ?? 0) ** 2;
            $pdCode = $this->calculator->lookupPurlinCode($pdIndex);
            $pSize = $lwcs[$i][1] ?? 0;
            $qty = (int) round(($lwcs[$i][0] ?? 0) * $ewgLines * (1 - $openings['lew_openings'] / max($endwallArea, 0.001)));

            if ($qty > 0) {
                $this->insertCode($ewGirtDes, $pdCode, $salesCode, $pSize, $qty);
                $ewGirtDes = '';
                $gClips += 2 * $qty;
                $lewgClipQty = 2 * $qty;
                $pgBolts += $qty * 8;
            }
        }

        // Right endwall girts
        $numRwcsGroups = (int) ($rwcs[0][0] ?? 0);
        for ($i = 1; $i <= $numRwcsGroups; $i++) {
            $pdIndex = 2.01 * $wind * ($rwcs[$i][1] ?? 0) ** 2;
            $pdCode = $this->calculator->lookupPurlinCode($pdIndex);
            $pSize = $rwcs[$i][1] ?? 0;
            $qty = (int) round(($rwcs[$i][0] ?? 0) * $ewgLines * (1 - $openings['rew_openings'] / max($endwallArea, 0.001)));

            if ($qty > 0) {
                $this->insertCode($ewGirtDes, $pdCode, $salesCode, $pSize, $qty);
                $ewGirtDes = '';
                $gClips += 2 * $qty;
                $rewgClipQty = 2 * $qty;
                $pgBolts += $qty * 8;
            }
        }

        // Purlin & girt connections
        $this->insertCode('Purlin & Girt Connections', 'HSB12', $salesCode, 1, $pgBolts);

        // Sag rods
        if ($roofSagRodDia === $wallSagRodDia) {
            if ($nSagRods > 0) {
                $this->insertCode('', 'SR'.$roofSagRodDia, $salesCode, 1, $nSagRods);
            }
        } else {
            if ($nSagRods > 0) {
                $this->insertCode('', 'SR'.$roofSagRodDia, $salesCode, 1, $nSagRods);
            }
            if ($wSagRods > 0) {
                $this->insertCode('', 'SR'.$wallSagRodDia, $salesCode, 1, $wSagRods);
            }
        }

        // CF clips
        $cfSize = (string) $purlinDepth;
        if ($cfSize === '200') {
            $this->insertCode('', 'CFClip', $salesCode, 1, $this->pClipQty + $swgClipQty + $lewgClipQty + $rewgClipQty);
        } elseif ($cfSize === '250') {
            $this->insertCode('', 'CFClip1', $salesCode, 1, $this->pClipQty + $swgClipQty);
            $this->insertCode('', 'CFClip', $salesCode, 1, $lewgClipQty + $rewgClipQty);
        } elseif ($cfSize === '360') {
            $this->insertCode('', 'CFClip2', $salesCode, 1, $this->pClipQty + $swgClipQty);
            $this->insertCode('', 'CFClip', $salesCode, 1, $lewgClipQty + $rewgClipQty);
        }

        // Gable angle and base angle
        $rafterLength = 0; // Will be recalculated
        $this->insertCode('', 'Gang', $salesCode, 1, 2 * (int) (10 * $this->currentRafterLength) / 10);
        $openingWidth = $openings['total_opening_width'];
        if ($openingWidth < 2 * ($length + $width)) {
            $this->insertCode('', 'Bang', $salesCode, 1, 2 * ($length + $width) - $openingWidth);
        }
    }

    private float $currentRafterLength = 0;

    /**
     * Generate roof sheeting items.
     */
    private function generateRoofSheeting(
        array $input, float $width, float $length, float $rafterLength,
        int $salesCode, string $panelProfile, array $openings
    ): void {
        $this->currentRafterLength = $rafterLength;

        $roofTopSkin = (string) ($input['roof_top_skin'] ?? 'None');
        $roofCore = (string) ($input['roof_core'] ?? '-');
        $roofBotSkin = (string) ($input['roof_bottom_skin'] ?? '-');
        $roofInsulation = (string) ($input['roof_insulation'] ?? 'None');
        $roofOpenings = $openings['roof_openings'];

        if ($roofTopSkin === 'None') {
            return;
        }

        $swpCode = $this->parser->generateSwpCode($roofTopSkin, $roofCore, $roofBotSkin);
        $isSandwich = ($roofCore !== '-' && $roofBotSkin !== '-');
        $roofArea = $this->calculator->calculateRoofSheetingArea($rafterLength, $length, $roofOpenings, $panelProfile);

        if ($isSandwich) {
            $this->insertCode('Roof Sheeting', '-', $salesCode, '', '');
            $this->insertCode('Mammut SWP Code: '.$swpCode, $roofTopSkin, $salesCode, 1, (int) ceil($roofArea));
            $coreArea = ($panelProfile === 'M45-250') ? $roofArea : (1.02 * $rafterLength * $length - $roofOpenings);
            $this->insertCode('', $roofCore, $salesCode, 1, (int) ceil($coreArea));
            $this->insertCode('', $roofBotSkin, $salesCode, 1, (int) ceil($roofArea));
            $this->insertCode($swpCode, '-', $salesCode, '', '');
        } else {
            $this->insertCode('Roof Sheeting', $roofTopSkin, $salesCode, 1, $roofArea);
        }

        // Screws
        $screwInfo = $this->parser->getScrewCodes($swpCode, $roofCore, $roofInsulation);
        $sheetArea = (int) ($width * $length - $roofOpenings);
        $this->insertCode('', $screwInfo['screw_code'], $salesCode, 1, 3 * $sheetArea);
        $this->insertCode('', $screwInfo['stitch_code'], $salesCode, 1, 2 * $sheetArea);

        // Bead mastic
        $bm = $this->calculator->calculateBeadMasticQuantity($rafterLength, $length);
        $this->insertCode('', 'BM1', $salesCode, 1, $bm['bm1_qty']);
        $this->insertCode('', 'BM2', $salesCode, 1, $bm['bm2_qty']);
    }

    /**
     * Generate wall sheeting items.
     */
    private function generateWallSheeting(
        array $input, float $width, float $length,
        float $beh, float $feh, float $endwallArea,
        int $salesCode, string $panelProfile, array $openings
    ): void {
        $wallTopSkin = (string) ($input['wall_top_skin'] ?? 'None');
        $wallCore = (string) ($input['wall_core'] ?? '-');
        $wallBotSkin = (string) ($input['wall_bottom_skin'] ?? '-');
        $wallInsulation = (string) ($input['wall_insulation'] ?? 'None');

        if ($wallTopSkin === 'None') {
            return;
        }

        $swpCode = $this->parser->generateSwpCode($wallTopSkin, $wallCore, $wallBotSkin);
        $isSandwich = ($wallCore !== '-' && $wallBotSkin !== '-');
        $profileFactor = ($panelProfile === 'M45-250') ? 1.0 : (1.0 / 0.9);

        // Back sidewall
        $bswArea = ($beh * $length - $openings['bsw_openings']) * $profileFactor;
        if ($isSandwich) {
            $this->insertCode('Sidewall Sheeting', '-', $salesCode, '', '');
            $this->insertCode('Mammut SWP Code: '.$swpCode, $wallTopSkin, $salesCode, 1, $bswArea);
            $coreArea = $beh * $length - $openings['bsw_openings'];
            $this->insertCode('', $wallCore, $salesCode, 1, $coreArea);
            $this->insertCode('', $wallBotSkin, $salesCode, 1, $bswArea);
            $this->insertCode($swpCode, '-', $salesCode, '', '');
        } else {
            $this->insertCode('Sidewall Sheeting', $wallTopSkin, $salesCode, 1, $bswArea);
        }

        // Front sidewall
        $fswArea = ($feh * $length - $openings['fsw_openings']) * $profileFactor;
        if ($isSandwich) {
            $this->insertCode('Mammut SWP Code: '.$swpCode, $wallTopSkin, $salesCode, 1, $fswArea);
            $coreArea = $feh * $length - $openings['fsw_openings'];
            $this->insertCode('', $wallCore, $salesCode, 1, $coreArea);
            $this->insertCode('', $wallBotSkin, $salesCode, 1, $fswArea);
            $this->insertCode($swpCode, '-', $salesCode, '', '');
        } else {
            $this->insertCode('', $wallTopSkin, $salesCode, 1, $fswArea);
        }

        // Left endwall
        $lewArea = ($endwallArea - $openings['lew_openings']) * $profileFactor;
        if ($isSandwich) {
            $this->insertCode('Endwall Sheeting', '-', $salesCode, '', '');
            $this->insertCode('Mammut SWP Code: '.$swpCode, $wallTopSkin, $salesCode, 1, $lewArea);
            $coreArea = $endwallArea - $openings['lew_openings'];
            $this->insertCode('', $wallCore, $salesCode, 1, $coreArea);
            $this->insertCode('', $wallBotSkin, $salesCode, 1, $lewArea);
            $this->insertCode($swpCode, '-', $salesCode, '', '');
        } else {
            $this->insertCode('Endwall Sheeting', $wallTopSkin, $salesCode, 1, $lewArea);
        }

        // Right endwall
        $rewArea = ($endwallArea - $openings['rew_openings']) * $profileFactor;
        if ($isSandwich) {
            $this->insertCode('Mammut SWP Code: '.$swpCode, $wallTopSkin, $salesCode, 1, $rewArea);
            $coreArea = $endwallArea - $openings['rew_openings'];
            $this->insertCode('', $wallCore, $salesCode, 1, $coreArea);
            $this->insertCode('', $wallBotSkin, $salesCode, 1, $rewArea);
            $this->insertCode($swpCode, '-', $salesCode, '', '');
        } else {
            $this->insertCode('', $swpCode, $salesCode, 1, $rewArea);
        }

        // Wall screws
        $wallShArea = ($feh + $beh) * $length + 2 * $endwallArea
            - $openings['bsw_openings'] - $openings['fsw_openings']
            - $openings['lew_openings'] - $openings['rew_openings'];
        $screwInfo = $this->parser->getScrewCodes($swpCode, $wallCore, $wallInsulation);
        $this->insertCode('', $screwInfo['screw_code'], $salesCode, 1, 3 * (int) $wallShArea);
    }

    /**
     * Generate trim items.
     */
    private function generateTrims(
        array $input, float $width, float $length, float $rafterLength,
        float $beh, float $feh,
        int $numBays, int $numPeaks, int $numValleys,
        int $salesCode
    ): void {
        $roofTopSkin = (string) ($input['roof_top_skin'] ?? 'None');
        $roofCore = (string) ($input['roof_core'] ?? '-');
        $roofBotSkin = (string) ($input['roof_bottom_skin'] ?? '-');
        $swpCode = $this->parser->generateSwpCode($roofTopSkin, $roofCore, $roofBotSkin);
        $trimSize = (string) ($input['trim_size'] ?? '0.5 AZ');
        $trimInfo = $this->parser->getTrimSuffix($swpCode, $trimSize);
        $tr = $trimInfo['trim_suffix'];
        $tr1 = $trimInfo['ds_rs_suffix'];

        if ($roofTopSkin === 'None') {
            return;
        }

        // Peak box
        $peakBoxCode = (str_contains($swpCode, 'A')) ? 'MPB2' : 'MPB';
        $this->insertCode('Trims:', $peakBoxCode, $salesCode, 1, 2);

        // Rake trim
        $this->insertCode('', 'GT'.$tr, $salesCode, 1, 2 * (int) (10 * $rafterLength) / 10);

        // Ridge caps
        if ($numPeaks > 0) {
            $this->insertCode('', 'RP'.$tr, $salesCode, 1, $length * $numPeaks);
        }

        // Valley gutters
        if ($numValleys > 0) {
            $this->insertCode('', 'VGG', $salesCode, 1, ((int) ($length / 5.5) + 1) * $numValleys);
            $this->insertCode('', 'VGS', $salesCode, 1, ((int) ($length / 5.5) + 1) * $numValleys * 3);
            $this->insertCode('', 'VGEC', $salesCode, 1, $numValleys * 2);
        }

        // Eave conditions
        $backEaveCondition = (string) ($input['back_eave_condition'] ?? '');
        $frontEaveCondition = (string) ($input['front_eave_condition'] ?? '');

        $ce = 0;
        $vg = 0;
        $eg = 0;
        $et = 0;

        if ($backEaveCondition === 'Gutter+Dwnspts') {
            $eg++;
        }
        if ($backEaveCondition === 'Curved ') {
            $ce++;
        }
        if ($backEaveCondition === 'Curved+VGutter') {
            $vg++;
            $ce++;
        }
        if ($backEaveCondition === 'Eave Trim') {
            $et++;
        }

        if ($frontEaveCondition === 'Gutter+Dwnspts') {
            $eg++;
        }
        if ($frontEaveCondition === 'Curved ') {
            $ce++;
        }
        if ($frontEaveCondition === 'Curved+VGutter') {
            $vg++;
            $ce++;
        }
        if ($frontEaveCondition === 'Eave Trim') {
            $et++;
        }

        if ($et > 0) {
            $this->insertCode('', 'ET'.$tr, $salesCode, 1, $length * $et);
        }
        if ($ce > 0) {
            $this->insertCode('', 'CP'.$tr, $salesCode, 1, $length * $ce);
        }
        if ($eg > 0) {
            $this->insertCode('', 'EG'.$tr, $salesCode, 1, $length * $eg);
            $this->insertCode('', 'ET'.$tr, $salesCode, 1, $length * $eg);
            $this->insertCode('', 'GSTR', $salesCode, 1, ((int) ($length * 2) + 1) * $eg);
            $this->insertCode('', 'DS'.$tr1, $salesCode, ($beh + $feh) / 2, $eg * (int) ($length / 9) + 1);
            $this->insertCode('', 'RS'.$tr1, $salesCode, 1, $eg * (int) ($length / 9) + 1);
        }

        // Corner trim
        $this->insertCode('', 'CT'.$tr, $salesCode, 1, ($beh + $feh) * 2);

        // Panel fixing clips
        $panelProfile = (string) ($input['panel_profile'] ?? 'M45-250');
        if ($panelProfile === 'M45-250') {
            $this->insertCode('', 'FMC1', $salesCode, 1, 4 * $length + 2 * $width);
        } else {
            $this->insertCode('', 'FMC2', $salesCode, 1, 4 * $length + 2 * $width);
        }
    }

    /**
     * Generate insulation items.
     */
    private function generateInsulation(
        array $input, float $width, float $length, float $rafterLength,
        float $beh, float $feh, float $endwallArea,
        int $salesCode, array $openings
    ): void {
        $roofInsulation = (string) ($input['roof_insulation'] ?? 'None');
        $wallInsulation = (string) ($input['wall_insulation'] ?? 'None');
        $wwmOption = (string) ($input['wwm_option'] ?? '');

        $insDes = 'Insulation';
        $dfTape = 0;

        if ($roofInsulation !== 'None') {
            $roofArea = 1.1 * ($width * $length - $openings['roof_openings']);
            $this->insertCode($insDes, $roofInsulation, $salesCode, 1, $roofArea);
            $insDes = '';

            if (in_array($wwmOption, ['Roof Only', 'Roof+Wall'])) {
                $wwm = round($roofArea, 2);
                $this->insertCode('', 'WWM', $salesCode, 1, $wwm);
            }

            // DF Tape calculation
            if ($width > 25) {
                $dfTape = (int) ceil($width / 25) * 2 * $length;
            } else {
                $dfTape = 2 * $length;
            }
        }

        if ($wallInsulation !== 'None') {
            $wallArea = 1.1 * (
                $length * $beh - $openings['bsw_openings']
                + $length * $feh - $openings['fsw_openings']
                + $endwallArea - $openings['lew_openings']
                + $endwallArea - $openings['rew_openings']
            );
            $this->insertCode($insDes, $wallInsulation, $salesCode, 1, $wallArea);

            if (in_array($wwmOption, ['Wall Only', 'Roof+Wall'])) {
                $wwm = round($wallArea, 2);
                $this->insertCode('', 'WWM', $salesCode, 1, $wwm);
            }

            // Additional DF Tape
            if ($feh > 25) {
                $dfTape += (int) ceil($feh / 25) * 2 * $length;
            } else {
                $dfTape += 2 * $length;
            }
            if ($beh > 25) {
                $dfTape += (int) ceil($beh / 25) * 2 * $length;
            } else {
                $dfTape += 2 * $length;
            }

            $maxEaveHeight = max($feh, $beh);
            if ($maxEaveHeight > 25) {
                $dfTape += (int) ceil($maxEaveHeight / 25) * 2 * $width * 2;
            } else {
                $dfTape += 4 * $width;
            }
        }

        if ($dfTape > 0) {
            $this->insertCode('', 'DFTP1', $salesCode, 1, (int) ceil($dfTape));
        }
    }

    /**
     * Generate accessory items.
     * VBA: AddAcc_Click()
     */
    private function generateAccessoryItems(array $input): void
    {
        $accessories = $input['accessories'] ?? [];
        if (empty($accessories)) {
            return;
        }

        $salesCode = (int) ($input['acc_sales_code'] ?? 1);
        $accDes = (string) ($input['acc_description'] ?? 'Accessories');

        foreach ($accessories as $acc) {
            $description = $acc['description'] ?? '';
            $qty = (int) ($acc['qty'] ?? 0);
            $code = $acc['code'] ?? '';

            if ($description !== '' && $qty > 0 && $code !== '') {
                $this->insertCode($accDes, $code, $salesCode, 1, $qty);
                $accDes = '';
            }
        }
    }

    /**
     * Get the generated items.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Calculate total weight from generated items (kg).
     */
    public function calculateTotalWeight(): float
    {
        $totalWeight = 0.0;
        foreach ($this->items as $item) {
            if ($item['is_header']) {
                continue;
            }
            $weight = (float) ($item['weight_per_unit'] ?? 0) * (float) ($item['size'] ?? 1) * (float) ($item['qty'] ?? 0);
            $totalWeight += $weight;
        }

        return $totalWeight;
    }

    /**
     * Calculate total material cost from generated items (AED).
     */
    public function calculateTotalCost(): float
    {
        $totalCost = 0.0;
        foreach ($this->items as $item) {
            if ($item['is_header']) {
                continue;
            }
            $cost = (float) ($item['rate'] ?? 0) * (float) ($item['size'] ?? 1) * (float) ($item['qty'] ?? 0);
            $totalCost += $cost;
        }

        return $totalCost;
    }
}
