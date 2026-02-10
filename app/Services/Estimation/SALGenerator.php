<?php

namespace App\Services\Estimation;

class SALGenerator
{
    /**
     * Sales code descriptions used to label SAL line items.
     * VBA: salesdescriptions named range
     *
     * @var array<int|string, string>
     */
    private const SALES_DESCRIPTIONS = [
        1 => 'Supply of Pre-Engineered Building',
        2 => 'Supply of Mezzanine Structure',
        3 => 'Supply of Canopy Structure',
        4 => 'Supply of Crane System',
        5 => 'Supply of Car Parking Shade',
        6 => 'Supply of Roof Monitor',
        7 => 'Supply of Walkway & Platforms',
        8 => 'Supply of Staircase & Handrails',
        9 => 'Supply of Checkered Plates & Grating',
        10 => 'Supply of Cold Storage Panels',
        11 => 'Supply of Partitions',
        12 => 'Supply of Steel Doors & Louvers',
        13 => 'Supply of Windows',
        14 => 'Supply of Rolling Shutters',
        15 => 'Supply of Skylights',
        16 => 'Supply of Fire Protection',
        17 => 'Supply of Gutters & Downspouts',
        18 => 'Supply of Insulation',
        19 => 'Supply of Translucent Panels',
        20 => 'Supply of Expansion Joints',
        21 => 'Supply of Miscellaneous Items',
        22 => 'Supply of Structural Steel',
        'P' => 'Packing & Handling',
        'S' => 'Transportation',
    ];

    /**
     * Generate SAL (Sales Summary) sheet data.
     *
     * The SAL sheet aggregates Detail items by their "sales_code" (column C in Detail)
     * and applies markup to produce a customer-facing price summary.
     *
     * Key Excel formulas:
     *   E = SUMIF(salescodes, A, prices) + SUMIF(salescodes, A, prices)/Detail!O208 * FCPBS!M118
     *   G = SUMIF(salescodes, A, PricesMdUp) + FCPBS!O118 * E/E36
     *   F = G/E (markup ratio)
     *   H = G/D * 1000 (AED per MT)
     *
     * @param  array<int, array<string, mixed>>  $detailItems  Items from DetailGenerator
     * @param  array<string, mixed>  $fcpbsData  Data from FCPBSGenerator
     * @return array{
     *     lines: array<int, array>,
     *     total_weight: float,
     *     total_cost: float,
     *     total_price: float,
     *     markup_ratio: float,
     *     price_per_mt: float
     * }
     */
    public function generate(array $detailItems, array $fcpbsData): array
    {
        // Extract key FCPBS values
        $otherChargesTotal = $fcpbsData['categories']['Q']['total_cost'] ?? 0;
        $otherChargesSellingPrice = $fcpbsData['categories']['Q']['selling_price'] ?? 0;

        // Sum all book prices from Detail (Detail!O208 equivalent)
        $totalBookPrice = 0;
        foreach ($detailItems as $item) {
            if (! ($item['is_header'] ?? false)) {
                $totalBookPrice += (float) ($item['book_price_total'] ?? 0);
            }
        }

        // Aggregate by sales code
        $salesCodes = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 'P', 'S'];
        $lines = [];
        $totalWeight = 0;
        $totalCost = 0;
        $totalPrice = 0;

        // First pass: aggregate weight, prices, and marked-up prices by sales code
        $aggregated = [];
        foreach ($salesCodes as $code) {
            $aggregated[$code] = [
                'weight' => 0,
                'prices_sum' => 0,       // Sum of book_price_total (Detail col P)
                'prices_mdup_sum' => 0,  // Sum of offer_price_total (Detail col R — after FCPBS markup)
            ];
        }

        foreach ($detailItems as $item) {
            if ($item['is_header'] ?? false) {
                continue;
            }

            $sc = $item['sales_code'] ?? 1;
            if (! isset($aggregated[$sc])) {
                $aggregated[$sc] = ['weight' => 0, 'prices_sum' => 0, 'prices_mdup_sum' => 0];
            }

            $aggregated[$sc]['weight'] += (float) ($item['total_weight'] ?? 0);
            $aggregated[$sc]['prices_sum'] += (float) ($item['book_price_total'] ?? 0);
            $aggregated[$sc]['prices_mdup_sum'] += (float) ($item['offer_price_total'] ?? 0);
        }

        // Calculate E36 (total cost across all codes) for proportional other-charges allocation
        $e36 = 0;
        foreach ($aggregated as $data) {
            $cost = $data['prices_sum'];
            if ($totalBookPrice > 0) {
                $cost += $data['prices_sum'] / $totalBookPrice * $otherChargesTotal;
            }
            $e36 += $cost;
        }

        // Second pass: build SAL lines
        foreach ($salesCodes as $code) {
            $data = $aggregated[$code];
            $description = self::SALES_DESCRIPTIONS[$code] ?? '';

            // Cost = SUMIF(prices) + proportional share of other charges
            // Excel: =SUMIF(salescodes,A,prices) + SUMIF(salescodes,A,prices)/Detail!$O$208 * FCPBS!$M$118
            $cost = $data['prices_sum'];
            if ($totalBookPrice > 0) {
                $cost += $data['prices_sum'] / $totalBookPrice * $otherChargesTotal;
            }

            // Price = marked-up prices + proportional share of other charges selling price
            // Excel: =SUMIF(salescodes,A,PricesMdUp) + FCPBS!$O$118 * E / $E$36
            $price = $data['prices_mdup_sum'];
            if ($e36 > 0) {
                $price += $otherChargesSellingPrice * $cost / $e36;
            }

            // Markup ratio
            $markupRatio = ($cost > 0) ? $price / $cost : 0;

            // AED per MT
            $pricePerMt = ($data['weight'] > 0 && $cost > 0) ? $price / $data['weight'] * 1000 : 0;

            $lines[] = [
                'code' => $code,
                'description' => $description,
                'weight_kg' => round($data['weight'], 3),
                'cost' => round($cost, 2),
                'markup' => round($markupRatio, 6),
                'price' => round($price, 2),
                'price_per_mt' => round($pricePerMt, 2),
            ];

            $totalWeight += $data['weight'];
            $totalCost += $cost;
            $totalPrice += $price;
        }

        $overallMarkup = ($totalCost > 0) ? $totalPrice / $totalCost : 0;
        $overallPricePerMt = ($totalWeight > 0) ? $totalPrice / $totalWeight * 1000 : 0;

        return [
            'lines' => $lines,
            'total_weight_kg' => round($totalWeight, 3),
            'total_cost' => round($totalCost, 2),
            'total_price' => round($totalPrice, 2),
            'markup_ratio' => round($overallMarkup, 6),
            'price_per_mt' => round($overallPricePerMt, 2),
        ];
    }
}
