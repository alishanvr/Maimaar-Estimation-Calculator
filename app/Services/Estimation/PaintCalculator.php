<?php

namespace App\Services\Estimation;

class PaintCalculator
{
    /**
     * Paint system definitions with rates per square meter.
     * VBA: Paint system lookup in AddArea_Click for blasting and painting.
     *
     * @var array<string, array{name: string, blast_rate: float, paint_rate: float}>
     */
    private const PAINT_SYSTEMS = [
        'None' => ['name' => 'No Paint', 'blast_rate' => 0, 'paint_rate' => 0],
        'Primer Only' => ['name' => 'Primer Only', 'blast_rate' => 0.2, 'paint_rate' => 1.0],
        'Primer + Finish' => ['name' => 'Primer + Finish', 'blast_rate' => 0.2, 'paint_rate' => 3.54],
        'Primer + Intermediate + Finish' => ['name' => 'Primer + Intermediate + Finish', 'blast_rate' => 0.2, 'paint_rate' => 5.0],
        'Epoxy' => ['name' => 'Epoxy System', 'blast_rate' => 0.2, 'paint_rate' => 8.0],
    ];

    /**
     * Calculate paint and blasting requirements.
     *
     * VBA: Blasting/Painting section of AddArea_Click():
     *   - Surface area from Detail items (column S)
     *   - Blasting: area × blast_rate
     *   - Paint: area × paint_rate
     *   - Cost codes: 10611 (blasting), 10711 (paint system)
     *
     * @param  array<string, mixed>  $input  Input data with paint system selection
     * @param  float  $totalSurfaceArea  Total paintable surface area (m²) from Detail items
     * @return array{
     *     paint_system: string,
     *     surface_area: float,
     *     blast_area: float,
     *     blast_rate: float,
     *     blast_cost: float,
     *     paint_area: float,
     *     paint_rate: float,
     *     paint_cost: float,
     *     items: array<int, array>
     * }
     */
    public function calculate(array $input, float $totalSurfaceArea): array
    {
        $paintSystem = $input['paint_system'] ?? 'Primer Only';
        $paintData = self::PAINT_SYSTEMS[$paintSystem] ?? self::PAINT_SYSTEMS['Primer Only'];

        // Blasting applies to all steel surface area
        $blastArea = $totalSurfaceArea;
        $blastRate = $paintData['blast_rate'];
        $blastCost = $blastArea * $blastRate;

        // Paint applies to same area
        $paintArea = $totalSurfaceArea;
        $paintRate = $paintData['paint_rate'];
        $paintCost = $paintArea * $paintRate;

        // Build items for injection into Detail sheet
        $items = [];

        if ($blastArea > 0 && $blastRate > 0) {
            $items[] = [
                'description' => 'Blasting',
                'code' => 'Blast',
                'sales_code' => 1,
                'cost_code' => '10611',
                'size' => 1,
                'qty' => round($blastArea, 3),
                'rate' => $blastRate,
                'weight_per_unit' => 0,
                'is_header' => false,
            ];
        }

        if ($paintArea > 0 && $paintRate > 0) {
            $items[] = [
                'description' => 'Paint System - '.$paintData['name'],
                'code' => 'Paint',
                'sales_code' => 1,
                'cost_code' => '10711',
                'size' => 1,
                'qty' => round($paintArea, 3),
                'rate' => $paintRate,
                'weight_per_unit' => 0,
                'is_header' => false,
            ];
        }

        return [
            'paint_system' => $paintSystem,
            'surface_area' => round($totalSurfaceArea, 3),
            'blast_area' => round($blastArea, 3),
            'blast_rate' => $blastRate,
            'blast_cost' => round($blastCost, 2),
            'paint_area' => round($paintArea, 3),
            'paint_rate' => $paintRate,
            'paint_cost' => round($paintCost, 2),
            'items' => $items,
        ];
    }
}
