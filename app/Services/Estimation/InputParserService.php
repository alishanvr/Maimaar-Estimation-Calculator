<?php

namespace App\Services\Estimation;

class InputParserService
{
    /**
     * Normalize separator characters to standard format.
     * VBA: FixSep() — converts :→@, +;/\&'→, , X/x→@
     *
     * @return string Normalized string with @ for multiplier and , for separator
     */
    public function fixSeparators(string $input): string
    {
        $text = $input;

        // Replace : with @ (multiplier notation)
        $text = str_replace(':', '@', $text);

        // Replace various separators with ,
        $text = str_replace(['+', ';', '/', '\\', "'", '&'], ',', $text);

        // Replace X and x with @ (multiplier notation)
        $text = str_ireplace(['X', 'x'], '@', $text);

        return $text;
    }

    /**
     * Parse bay/span notation into structured list.
     * VBA: GetList() — parses "1@6.865+1@9.104+2@9.144" into count/length pairs.
     *
     * Returns array where index 0 holds metadata:
     *   [0][0] = number of distinct groups
     *   [0][1] = total count of all items
     *   [n][0] = count (repetitions)
     *   [n][1] = value (spacing/length)
     *
     * @return array<int, array{0: float, 1: float}>
     */
    public function getList(string $input): array
    {
        $text = ' '.$this->fixSeparators($input).',';
        $list = [];
        $n = 1;
        $i = 0;
        $totalCount = 0;

        while (($k = strpos($text, ',', $i + 1)) !== false) {
            $m = strpos($text, '@', $i + 1);

            if ($m !== false && $m > $i && $m < $k) {
                $count = (float) substr($text, $i + 1, $m - $i - 1);
                $value = (float) substr($text, $m + 1, $k - $m - 1);
            } else {
                $count = 1.0;
                $value = (float) trim(substr($text, $i + 1, $k - $i - 1));
            }

            if ($value != 0) {
                $list[$n] = [0 => $count, 1 => $value];
                $totalCount += $count;
                $n++;
            }

            $i = $k;
        }

        // Metadata: [0][0] = number of groups, [0][1] = total count
        $list[0] = [0 => $n - 1, 1 => $totalCount];

        return $list;
    }

    /**
     * Expand compressed list into individual values.
     * VBA: ExpList() — turns [(2, 9.144)] into [9.144, 9.144].
     *
     * Returns array where index 0 = total count, subsequent indices = individual values.
     *
     * @param  array<int, array{0: float, 1: float}>  $list
     * @return array<int, float>
     */
    public function expandList(array $list): array
    {
        $totalCount = (int) $list[0][1];
        $expanded = [0 => $totalCount];
        $idx = 0;

        $numGroups = (int) $list[0][0];
        for ($i = 1; $i <= $numGroups; $i++) {
            $count = (int) ($list[$i][0] ?? 0);
            $value = $list[$i][1] ?? 0.0;
            for ($j = 1; $j <= $count; $j++) {
                $idx++;
                $expanded[$idx] = $value;
            }
        }

        return $expanded;
    }

    /**
     * Calculate building dimension (total length/width) from spacing notation.
     * VBA: GetBuildingDimension() — returns total dimension and sets globals.
     *
     * @return array{total: float, bay_count: int, max_span: float, bay_spacing: float}
     */
    public function getBuildingDimension(string $input): array
    {
        $totalLength = 0.0;
        $bayCount = 0;
        $maxSpan = 0.0;
        $baySpacing = 0.0;

        if (str_contains($input, '+') || str_contains($input, ',') || str_contains($input, ';')) {
            $list = $this->getList($input);
            $spans = [];
            $numGroups = (int) $list[0][0];

            for ($i = 1; $i <= $numGroups; $i++) {
                $count = (int) $list[$i][0];
                $value = $list[$i][1];
                $bayCount += $count;
                $totalLength += $count * $value;
                $spans[] = $value;
            }

            sort($spans);
            $maxSpan = end($spans);
        } elseif (str_contains($input, '@') || str_contains($input, 'x') || str_contains($input, 'X') || str_contains($input, ':')) {
            $normalized = $this->fixSeparators($input);
            $parts = explode('@', $normalized);
            $count = (float) $parts[0];
            $spacing = (float) ($parts[1] ?? 0);
            $totalLength = $count * $spacing;
            $bayCount = (int) $count;
            $baySpacing = $spacing;
            $maxSpan = $spacing;
        } else {
            $maxSpan = (float) $input;
        }

        return [
            'total' => $totalLength,
            'bay_count' => $bayCount,
            'max_span' => $maxSpan,
            'bay_spacing' => $baySpacing,
        ];
    }

    /**
     * Calculate total dimension sum from a list.
     *
     * @param  array<int, array{0: float, 1: float}>  $list
     */
    public function calculateTotalFromList(array $list): float
    {
        $total = 0.0;
        $numGroups = (int) ($list[0][0] ?? 0);

        for ($i = 1; $i <= $numGroups; $i++) {
            $total += ($list[$i][0] ?? 0) * ($list[$i][1] ?? 0);
        }

        return $total;
    }

    /**
     * Calculate total count from a list.
     *
     * @param  array<int, array{0: float, 1: float}>  $list
     */
    public function calculateCountFromList(array $list): int
    {
        $total = 0;
        $numGroups = (int) ($list[0][0] ?? 0);

        for ($i = 1; $i <= $numGroups; $i++) {
            $total += (int) ($list[$i][0] ?? 0);
        }

        return $total;
    }

    /**
     * Determine connection type based on weight per linear meter.
     * VBA: ConType() — returns 1-5 based on wplm ranges.
     */
    public function getConnectionType(float $wplm): int
    {
        return match (true) {
            $wplm <= 20 => 1,  // AB16
            $wplm < 40 => 2,   // AB20
            $wplm < 80 => 3,   // AB24
            $wplm < 120 => 4,  // AB30
            default => 5,       // AB36
        };
    }

    /**
     * Get fixed base type info based on building width.
     * VBA: FixedBaseType() — returns [connection_type, bolt_count, bolt_diameter].
     *
     * @return array{connection_type: int, bolt_count: int, bolt_diameter: int}
     */
    public function getFixedBaseType(float $buildingWidth): array
    {
        return match (true) {
            $buildingWidth <= 15 => ['connection_type' => 2, 'bolt_count' => 8, 'bolt_diameter' => 20],
            $buildingWidth <= 25 => ['connection_type' => 3, 'bolt_count' => 8, 'bolt_diameter' => 24],
            $buildingWidth <= 35 => ['connection_type' => 3, 'bolt_count' => 16, 'bolt_diameter' => 24],
            $buildingWidth <= 45 => ['connection_type' => 4, 'bolt_count' => 16, 'bolt_diameter' => 30],
            $buildingWidth <= 50 => ['connection_type' => 5, 'bolt_count' => 16, 'bolt_diameter' => 36],
            $buildingWidth <= 60 => ['connection_type' => 4, 'bolt_count' => 32, 'bolt_diameter' => 30],
            default => ['connection_type' => 5, 'bolt_count' => 32, 'bolt_diameter' => 36],
        };
    }

    /**
     * Parse slope profile from input.
     * Slopes define the roof profile with cumulative heights at breakpoints.
     *
     * @param  array<int, array{0: float, 1: float}>  $slopeList  Parsed slope groups [count, rise_per_run]
     * @return array{
     *     slopes: array<int, array{width: float, rise: float, height: float}>,
     *     total_sloped_width: float,
     *     rafter_length: float,
     *     endwall_area: float,
     *     peak_height: float,
     *     num_peaks: int,
     *     num_valleys: int
     * }
     */
    public function calculateSlopeProfile(
        array $slopeList,
        float $buildingWidth,
        float $backEaveHeight,
        float $frontEaveHeight
    ): array {
        $slopes = [];
        $slopedWidth = 0.0;
        $rafterLength = 0.0;
        $endwallArea = 0.0;
        $peakHeight = 0.0;
        $previousHeight = $backEaveHeight;

        $numGroups = (int) $slopeList[0][0];

        for ($i = 1; $i <= $numGroups; $i++) {
            $width = $slopeList[$i][0];
            $rise = $slopeList[$i][1];

            // If slope width is 1 (default), it means half of total width
            if ($width == 1) {
                $width = $buildingWidth / 2;
            }

            $slopedWidth += $width;
            $rafterLength += sqrt(1 + $rise * $rise) * $width;

            $currentHeight = $previousHeight + $width * $rise;
            if ($currentHeight > $peakHeight) {
                $peakHeight = $currentHeight;
            }

            $endwallArea += ($previousHeight + $currentHeight) * $width / 2;

            $slopes[$i] = [
                'width' => $width,
                'rise' => $rise,
                'height' => $currentHeight,
            ];

            $previousHeight = $currentHeight;
        }

        // If slopes don't span full width, add final slope segment
        $n = $numGroups;
        if ($slopedWidth < $buildingWidth) {
            $n++;
            $remainingWidth = $buildingWidth - $slopedWidth;
            $finalRise = ($frontEaveHeight - $previousHeight) / $remainingWidth;
            $currentHeight = $frontEaveHeight;

            $endwallArea += ($previousHeight + $currentHeight) * $remainingWidth / 2;
            $rafterLength += sqrt(1 + $finalRise * $finalRise) * $remainingWidth;

            $slopes[$n] = [
                'width' => $remainingWidth,
                'rise' => $finalRise,
                'height' => $currentHeight,
            ];
        }

        // Calculate peaks and valleys
        $numPeaks = 0;
        $numValleys = 0;
        for ($i = 1; $i < $n; $i++) {
            if ($n > 1) {
                if ($slopes[$i]['rise'] > $slopes[$i + 1]['rise']) {
                    $numPeaks++;
                }
                if ($slopes[$i]['rise'] < $slopes[$i + 1]['rise']) {
                    $numValleys++;
                }
            }
        }

        return [
            'slopes' => $slopes,
            'total_sloped_width' => $slopedWidth,
            'rafter_length' => $rafterLength,
            'endwall_area' => $endwallArea,
            'peak_height' => $peakHeight,
            'num_peaks' => $numPeaks,
            'num_valleys' => $numValleys,
            'num_slope_segments' => $n,
        ];
    }

    /**
     * Calculate column heights at roof steel line for interior columns.
     * VBA: Calculates inCol(i,1) by interpolating slope profile at span boundaries.
     *
     * @param  array<int, float>  $expandedSpans  Expanded span values (1-indexed)
     * @param  array<int, array{width: float, rise: float, height: float}>  $slopes  Slope profile (1-indexed)
     * @return array<int, array{span: float, height: float}>
     */
    public function calculateColumnHeights(
        array $expandedSpans,
        array $slopes,
        float $backEaveHeight,
        int $numSlopes
    ): array {
        $columns = [];
        $cumulativeSpan = 0.0;

        $numSpans = (int) $expandedSpans[0];
        for ($i = 1; $i <= $numSpans; $i++) {
            $cumulativeSpan += $expandedSpans[$i];
            $cumulativeSlope = 0.0;

            for ($j = 1; $j <= $numSlopes; $j++) {
                $cumulativeSlope += $slopes[$j]['width'];
                if ($cumulativeSpan <= $cumulativeSlope) {
                    $height = $slopes[$j]['height'] - ($cumulativeSlope - $cumulativeSpan) * $slopes[$j]['rise'];
                    $columns[$i] = [
                        'span' => $expandedSpans[$i],
                        'height' => $height,
                    ];
                    break;
                }
            }
        }

        return $columns;
    }

    /**
     * Parse opening dimensions from "WxH" or "Full" notation.
     *
     * @return array{width: float, height: float}
     */
    public function parseOpeningSize(string $sizeString): array
    {
        $width = 0.0;
        $height = 0.0;

        if (stripos($sizeString, 'ful') !== false || stripos($sizeString, 'x') !== false) {
            $width = (float) $sizeString;
            $xPos = stripos($sizeString, 'x');
            if ($xPos !== false) {
                $height = (float) substr($sizeString, $xPos + 1);
            }
        }

        return ['width' => $width, 'height' => $height];
    }

    /**
     * Generate SWP (Sandwich Panel) code from individual components.
     * Example: "PU" + "S045RAL9002" + "Core50BPro" + "S045RAL9002" = "PUS045RAL9002Core50BProS045RAL9002"
     */
    public function generateSwpCode(string $topSkin, string $core, string $bottomSkin): string
    {
        if ($core === '-' || $bottomSkin === '-') {
            return $topSkin;
        }

        return 'PU'.$topSkin.$core.$bottomSkin;
    }

    /**
     * Determine screw code based on material type and core thickness.
     * VBA: Complex Select Case logic in AddArea_Click for roof/wall sheeting screws.
     *
     * @return array{screw_code: string, stitch_code: string, plug_screw: string}
     */
    public function getScrewCodes(string $sheetingCode, string $coreCode, string $insulationInput): array
    {
        $screwCode = 'CS2';
        $stitchCode = 'CS1';
        $plugScrew = 'ST1';

        // Single skin AZ Steel
        if (str_starts_with($sheetingCode, 'S') && ! str_starts_with($sheetingCode, 'SS')) {
            $screwCode = ($insulationInput !== 'None' && $insulationInput !== '') ? 'CS3' : 'CS2';
            $plugScrew = 'ST1';
            $stitchCode = 'CS1';
        }

        // SWP AZ Steel
        if (str_starts_with($sheetingCode, 'PUS')) {
            $screwCode = match (true) {
                str_starts_with($coreCode, 'Core35') => 'CS4',
                str_starts_with($coreCode, 'Core50') => 'CS5',
                str_starts_with($coreCode, 'Core75') => 'CS6',
                str_starts_with($coreCode, 'Core100') => 'CS7',
                default => 'CS4',
            };
            $plugScrew = 'ST1';
            $stitchCode = 'CS1';
        }

        // Single skin Aluminum
        if (str_starts_with($sheetingCode, 'A')) {
            $screwCode = ($insulationInput !== 'None' && $insulationInput !== '') ? 'SS3' : 'SS2';
            $plugScrew = 'ST2';
            $stitchCode = 'SS1';
        }

        // SWP Aluminum
        if (str_starts_with($sheetingCode, 'PUA')) {
            $screwCode = match (true) {
                str_starts_with($coreCode, 'Core35') => 'SS4',
                str_starts_with($coreCode, 'Core50') => 'SS5',
                str_starts_with($coreCode, 'Core75') => 'SS6',
                str_starts_with($coreCode, 'Core100') => 'SS7',
                default => 'SS4',
            };
            $plugScrew = 'ST2';
            $stitchCode = 'SS1';
        }

        return [
            'screw_code' => $screwCode,
            'stitch_code' => $stitchCode,
            'plug_screw' => $plugScrew,
        ];
    }

    /**
     * Get trim material code suffix based on sheeting material.
     * VBA: Tr$ = "S" for AZ Steel, "A" for Aluminum.
     *
     * @return array{trim_suffix: string, ds_rs_suffix: string}
     */
    public function getTrimSuffix(string $sheetingCode, string $trimSize): array
    {
        $trimSuffix = 'S';
        $dsRsSuffix = 'S';

        if ($trimSize === '0.5 AZ') {
            $trimSuffix = 'S';
        } elseif ($trimSize === '0.7 AZ') {
            $trimSuffix = 'S1';
        }

        if (str_starts_with($sheetingCode, 'A') || str_starts_with($sheetingCode, 'PUA')) {
            $trimSuffix = 'A';
            $dsRsSuffix = 'A';
        }

        return [
            'trim_suffix' => $trimSuffix,
            'ds_rs_suffix' => $dsRsSuffix,
        ];
    }
}
