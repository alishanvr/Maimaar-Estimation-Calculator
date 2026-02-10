<?php

namespace App\Services\Estimation;

class RoofMonitorCalculator
{
    /**
     * Monitor type constants matching VBA dropdowns.
     */
    private const TYPE_CURVE_CF = 'Curve-CF';

    private const TYPE_STRAIGHT_CF = 'Straight-CF';

    private const TYPE_CURVE_HR = 'Curve-HR';

    private const TYPE_STRAIGHT_HR = 'Straight-HR';

    /**
     * Calculate roof monitor dimensions and weights.
     *
     * VBA: RoofMonitor section in AddArea_Click handles 4 monitor types.
     * Each adds:
     *   - Monitor framing (main structure)
     *   - Monitor sheeting (cladding)
     *   - Monitor gutters and trims
     *   - Ventilation openings
     *
     * @param  array<string, mixed>  $input  Input data with monitor parameters
     * @return array{
     *     is_applicable: bool,
     *     type: string,
     *     width: float,
     *     height: float,
     *     length: float,
     *     frame_weight: float,
     *     sheeting_area: float,
     *     items: array<int, array>
     * }
     */
    public function calculate(array $input): array
    {
        $monitorType = $input['monitor_type'] ?? 'None';
        if ($monitorType === 'None' || empty($monitorType)) {
            return [
                'is_applicable' => false,
                'type' => 'None',
                'width' => 0,
                'height' => 0,
                'length' => 0,
                'frame_weight' => 0,
                'sheeting_area' => 0,
                'items' => [],
            ];
        }

        $monitorWidth = (float) ($input['monitor_width'] ?? 3.0);
        $monitorHeight = (float) ($input['monitor_height'] ?? 1.5);
        $buildingLength = (float) ($input['building_length'] ?? 0);
        $monitorLength = (float) ($input['monitor_length'] ?? $buildingLength);
        $numBays = (int) ($input['num_bays'] ?? 1);

        // Frame weight calculation depends on monitor type
        $isHotRolled = str_contains($monitorType, 'HR');
        $isCurved = str_contains($monitorType, 'Curve');

        // Monitor frame weight per meter length
        // VBA: wMonitor = f(width, height, type)
        $frameWeightPerMeter = $this->calculateFrameWeight($monitorWidth, $monitorHeight, $isHotRolled, $isCurved);
        $totalFrameWeight = $frameWeightPerMeter * $monitorLength;

        // Sheeting: side panels + top panel
        $sideArea = 2 * $monitorHeight * $monitorLength; // Both sides
        $topArea = $monitorWidth * $monitorLength;
        $totalSheetingArea = $sideArea + $topArea;

        // Build items
        $items = [];

        // Monitor frames
        $numFrames = $numBays + 1;
        $frameWeightEach = $totalFrameWeight / $numFrames;
        $items[] = [
            'description' => 'Roof Monitor Frames - '.$monitorType,
            'code' => 'BU',
            'sales_code' => 1,
            'cost_code' => '10111',
            'size' => round($frameWeightEach, 3),
            'qty' => $numFrames,
            'is_header' => false,
        ];

        // Monitor purlins/girts (cold-formed members along monitor length)
        $monitorPurlinLines = max(2, (int) round($monitorWidth / 1.5));
        $monitorGirtLines = max(2, (int) round($monitorHeight / 0.8) * 2); // Both sides
        $totalSecondaryLength = ($monitorPurlinLines + $monitorGirtLines) * $monitorLength / $numBays;

        $items[] = [
            'description' => 'Monitor Purlins & Girts',
            'code' => 'Z15G',
            'sales_code' => 1,
            'cost_code' => '11211',
            'size' => round($buildingLength / $numBays + 0.107, 3),
            'qty' => $monitorPurlinLines + $monitorGirtLines,
            'is_header' => false,
        ];

        // Monitor sheeting
        if ($totalSheetingArea > 0) {
            $items[] = [
                'description' => 'Monitor Sheeting',
                'code' => 'SSP',
                'sales_code' => 1,
                'cost_code' => '20111',
                'size' => round($totalSheetingArea, 3),
                'qty' => 1,
                'is_header' => false,
            ];
        }

        // Monitor trims (ridge cap equivalent for monitor)
        $items[] = [
            'description' => 'Monitor Ridge Trim',
            'code' => 'PeakBoxS',
            'sales_code' => 1,
            'cost_code' => '21111',
            'size' => round($monitorLength, 3),
            'qty' => 1,
            'is_header' => false,
        ];

        return [
            'is_applicable' => true,
            'type' => $monitorType,
            'width' => $monitorWidth,
            'height' => $monitorHeight,
            'length' => $monitorLength,
            'frame_weight' => round($totalFrameWeight, 3),
            'sheeting_area' => round($totalSheetingArea, 3),
            'items' => $items,
        ];
    }

    /**
     * Calculate monitor frame weight per meter of length.
     *
     * Hot rolled monitors are ~40% heavier than cold-formed.
     * Curved monitors are ~15% heavier than straight.
     */
    private function calculateFrameWeight(float $width, float $height, bool $isHotRolled, bool $isCurved): float
    {
        // Base weight formula: perimeter × linear density
        $perimeter = 2 * $height + $width;
        $baseWeight = $perimeter * 8.0; // ~8 kg/m for cold-formed framing

        if ($isHotRolled) {
            $baseWeight *= 1.4;
        }

        if ($isCurved) {
            $baseWeight *= 1.15;
        }

        return $baseWeight;
    }
}
