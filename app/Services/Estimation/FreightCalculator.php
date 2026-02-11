<?php

namespace App\Services\Estimation;

class FreightCalculator
{
    /**
     * Freight load categories matching FCPBS rows 97-108.
     * Each maps to a cost code and a source for load count.
     *
     * @var array<int, array{code: int, name: string}>
     */
    private const LOAD_CATEGORIES = [
        1 => ['code' => 60111, 'name' => 'Loads for Frames'],
        2 => ['code' => 60121, 'name' => 'Loads for Secondary Members'],
        3 => ['code' => 60211, 'name' => 'Loads for Sandwich Panels'],
        4 => ['code' => 60311, 'name' => 'Loads for Single Skin Sheeting'],
        5 => ['code' => 60321, 'name' => 'Loads for Trims'],
        6 => ['code' => 60421, 'name' => 'Loads for Standard Buyouts'],
        7 => ['code' => 60411, 'name' => 'Loads for Accessories & Buyouts'],
        8 => ['code' => 60511, 'name' => 'Loads for Fiberglass Insulation'],
        9 => ['code' => 60611, 'name' => 'Loads Adjustment'],
        10 => ['code' => 60711, 'name' => 'Total Loads Including Adjustment'],
    ];

    /**
     * Calculate freight loads and costs.
     *
     * VBA/Excel: The freight section (FCPBS rows 97-108) computes:
     *   - Number of truck loads per material category (from DB sheet C337-C344)
     *   - A total-loads item aggregated from Detail items with cost code 60711
     *   - Freight rate × total loads = freight cost
     *
     * @param  array<string, mixed>  $fcpbsCategories  FCPBS category data (with weights)
     * @param  array<string, mixed>  $input  Input data containing freight parameters
     * @param  array<int, array<string, mixed>>  $detailItems  Detail items for load extraction
     * @return array{
     *     loads: array<int, array>,
     *     total_loads: float,
     *     freight_rate: float,
     *     total_freight_cost: float,
     *     container_cost: float,
     *     items: array<int, array>
     * }
     */
    public function calculate(array $fcpbsCategories, array $input, array $detailItems = []): array
    {
        $freightType = $input['freight_type'] ?? 'By Mammut';
        $freightRate = (float) ($input['freight_rate'] ?? 0);
        $containerCount = (int) ($input['container_count'] ?? 6);
        $containerRate = (float) ($input['container_rate'] ?? 2000);

        // Calculate loads per category based on weight (MT) and load capacity
        $primaryWeight = ($fcpbsCategories['A']['weight_kg'] ?? 0) / 1000; // MT
        $secondaryWeight = ($fcpbsCategories['C']['weight_kg'] ?? 0) / 1000;
        $sandwichWeight = ($fcpbsCategories['G']['weight_kg'] ?? 0) / 1000;
        $singleSkinWeight = ($fcpbsCategories['F']['weight_kg'] ?? 0) / 1000;
        $trimsWeight = ($fcpbsCategories['H']['weight_kg'] ?? 0) / 1000;
        $buyoutsWeight = ($fcpbsCategories['D']['weight_kg'] ?? 0) / 1000;
        $accessoriesWeight = ($fcpbsCategories['J']['weight_kg'] ?? 0) / 1000;
        $insulationWeight = 0; // From insulation items if any

        // Load factors — approximate truck loads per MT (from DB sheet)
        $loads = [
            1 => $this->calculateCategoryLoads($primaryWeight, 15),        // ~15 MT per truck
            2 => $this->calculateCategoryLoads($secondaryWeight, 20),      // ~20 MT per truck
            3 => $this->calculateCategoryLoads($sandwichWeight, 5.3),      // ~5.3 MT per truck (volumetric)
            4 => $this->calculateCategoryLoads($singleSkinWeight, 8),      // ~8 MT per truck
            5 => $this->calculateCategoryLoads($trimsWeight, 10),          // ~10 MT per truck
            6 => $this->calculateCategoryLoads($buyoutsWeight, 20),        // ~20 MT per truck
            7 => $this->calculateCategoryLoads($accessoriesWeight, 10),    // ~10 MT per truck
            8 => $this->calculateCategoryLoads($insulationWeight, 5),      // ~5 MT per truck
        ];

        // Total loads from Detail items (cost code 60711)
        $totalLoadsFromDetail = 0;
        foreach ($detailItems as $item) {
            if (($item['cost_code'] ?? '') == '60711') {
                $totalLoadsFromDetail += (float) ($item['qty'] ?? 0);
            }
        }

        $sumCategoryLoads = array_sum($loads);
        $loadAdjustment = $totalLoadsFromDetail - $sumCategoryLoads;
        $loads[9] = max(0, $loadAdjustment); // Adjustment
        $loads[10] = $totalLoadsFromDetail > 0 ? $totalLoadsFromDetail : $sumCategoryLoads + max(0, $loadAdjustment);

        $totalFreightCost = $loads[10] * $freightRate;
        $containerCost = $containerCount * $containerRate;

        // Build freight detail items for injection into Detail sheet.
        // Cost codes must match FCPBS category codes so FCPBS can aggregate them:
        //   40111 → FCPBS O (Freight)
        //   30111 → FCPBS M (Container & Skids)
        $freightItems = [];
        if ($freightType !== 'Customer Pickup') {
            $freightItems[] = [
                'description' => 'Freight',
                'code' => 'Freight',
                'sales_code' => 'S',
                'cost_code' => '40111',
                'size' => 1,
                'qty' => $loads[10],
                'rate' => $freightRate,
                'weight_per_unit' => 0,
                'is_header' => false,
            ];
        }

        // Container skids
        if ($containerCount > 0) {
            $freightItems[] = [
                'description' => 'Container Skids',
                'code' => 'ContSkid',
                'sales_code' => 'P',
                'cost_code' => '30111',
                'size' => 1,
                'qty' => $containerCount,
                'rate' => $containerRate,
                'weight_per_unit' => 0,
                'is_header' => false,
            ];
        }

        $loadDetails = [];
        foreach (self::LOAD_CATEGORIES as $idx => $cat) {
            $loadDetails[] = [
                'index' => $idx,
                'code' => $cat['code'],
                'name' => $cat['name'],
                'loads' => round($loads[$idx] ?? 0, 6),
            ];
        }

        return [
            'loads' => $loadDetails,
            'total_loads' => round($loads[10], 3),
            'freight_rate' => $freightRate,
            'total_freight_cost' => round($totalFreightCost, 2),
            'container_count' => $containerCount,
            'container_rate' => $containerRate,
            'container_cost' => round($containerCost, 2),
            'items' => $freightItems,
        ];
    }

    /**
     * Calculate truck loads for a weight category.
     */
    private function calculateCategoryLoads(float $weightMT, float $capacityPerTruck): float
    {
        if ($weightMT <= 0 || $capacityPerTruck <= 0) {
            return 0;
        }

        return $weightMT / $capacityPerTruck;
    }
}
