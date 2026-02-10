<?php

namespace App\Services\Estimation;

class JAFGenerator
{
    /**
     * Special requirements checklist items for JAF form.
     *
     * @var array<int, string>
     */
    private const SPECIAL_REQUIREMENTS = [
        1 => 'Non standard inventory',
        2 => 'More than 2 coats paint system',
        3 => 'Penalty on delivery or/and erection',
        4 => 'Special fast delivery (Less than 8 weeks)',
        5 => 'Non standard payment terms (Explain below)',
        6 => 'Cladding as a special buyout',
        7 => 'Special quality plan required',
        8 => 'Unusually complex special buyouts',
        9 => '',
        10 => '',
        11 => 'Erection by Mammut (other than in UAE)',
        12 => 'Non standard contract (i.e. not Mammut template)',
        13 => 'Performance bond by Mammut',
        14 => 'High Engineering complexity (Explain below)',
        15 => 'Double side welding (other than crane beams) Specify MT',
        16 => '250mm deep or TMCP secondary members',
        17 => 'Marketing Fees (Mark-up must be min. of 1.05)',
        18 => 'Special Color Cladding (Painted in Dubai)',
        19 => 'Excess L/C amount',
    ];

    /**
     * Generate JAF (Job Acceptance Form) data.
     *
     * The JAF is a formal approval document referencing FCPBS totals.
     * It displays project summary, pricing, markup, and value-added metrics.
     *
     * Key Excel references:
     *   - Requested Bottom Line Mark-Up: FCPBS!N120 (= O120/M120 ≈ 0.970)
     *   - Value Added "L" line: FCPBS!S92 (AED/MT at FOB level)
     *   - Value Added "R" line: FCPBS!S120 (AED/MT at supply level)
     *   - Total MT: FCPBS!H120/1000
     *   - Supply Price: FCPBS!O120
     *   - Erection Price: FCPBS!O121
     *   - Primary MT: FCPBS!H22/1000
     *
     * @param  array<string, mixed>  $fcpbsData  Full FCPBS output
     * @param  array<string, mixed>  $input  Input data
     * @return array<string, mixed>
     */
    public function generate(array $fcpbsData, array $input): array
    {
        $categories = $fcpbsData['categories'] ?? [];

        // Key metrics from FCPBS
        $totalWeightKg = (float) ($fcpbsData['total_weight_kg'] ?? 0);
        $totalWeightMT = $totalWeightKg / 1000;
        $totalPrice = (float) ($fcpbsData['total_price'] ?? 0);
        $fobPrice = (float) ($fcpbsData['fob_price'] ?? 0);

        // Steel subtotal (A+B+C+D)
        $steelSubtotal = $fcpbsData['steel_subtotal'] ?? [];
        $primaryWeightMT = ($categories['A']['weight_kg'] ?? 0) / 1000;

        // FCPBS row 92: FOB (Steel + Panels subtotals)
        $fobMaterialCost = (float) ($steelSubtotal['material_cost'] ?? 0) +
            (float) (($fcpbsData['panels_subtotal'] ?? [])['material_cost'] ?? 0);

        // Value added at line "L" = FOB selling price - FOB material cost, per MT
        // Excel: FCPBS!S92
        $fobSellingPrice = $fobPrice;
        $fobRawMatCost = $fobMaterialCost;
        $valueAddedL = ($totalWeightKg > 0) ? 1000 * ($fobSellingPrice - $fobRawMatCost) / $totalWeightKg : 0;

        // Value added at line "R" = Total supply price - FOB material cost, per MT
        // Excel: FCPBS!S120 = (O120 - J92) / H120 * 1000
        $valueAddedR = ($totalWeightKg > 0) ? 1000 * ($totalPrice - $fobRawMatCost) / $totalWeightKg : 0;

        // Bottom line markup = total selling price / total cost
        // Excel: FCPBS!N120
        $totalCost = 0;
        foreach ($categories as $cat) {
            $totalCost += (float) ($cat['total_cost'] ?? 0);
        }
        $bottomLineMarkup = ($totalCost > 0) ? $totalPrice / $totalCost : 0;

        // Erection price (if applicable)
        $erectionPrice = (float) ($input['erection_price'] ?? 0);

        // Price per MT
        $pricePerMT = ($totalWeightMT > 0) ? $totalPrice / $totalWeightMT : 0;

        // Minimum delivery commitment: 10 + ROUNDUP(steel_weight_MT / 150)
        $steelWeightKg = (float) ($steelSubtotal['weight_kg'] ?? 0);
        $minDeliveryWeeks = 10 + (int) ceil($steelWeightKg / 1000 / 150);

        // Contract value in USD (AED / 3.67)
        $contractValueUSD = ($totalPrice + $erectionPrice) / 3.67;

        return [
            'project_info' => [
                'quote_number' => $input['quote_number'] ?? '',
                'building_number' => $input['building_number'] ?? 1,
                'customer_name' => $input['customer_name'] ?? '',
                'revision_number' => $input['revision_number'] ?? 0,
                'date' => $input['date'] ?? now()->format('Y-m-d'),
                'sales_office' => $input['sales_office'] ?? '',
            ],
            'pricing' => [
                'bottom_line_markup' => round($bottomLineMarkup, 8),
                'value_added_l' => round($valueAddedL, 2),
                'value_added_r' => round($valueAddedR, 2),
                'total_weight_mt' => round($totalWeightMT, 4),
                'primary_weight_mt' => round($primaryWeightMT, 4),
                'supply_price_aed' => round($totalPrice, 2),
                'erection_price_aed' => round($erectionPrice, 2),
                'total_contract_aed' => round($totalPrice + $erectionPrice, 2),
                'contract_value_usd' => round($contractValueUSD, 0),
                'price_per_mt' => round($pricePerMT, 2),
                'min_delivery_weeks' => $minDeliveryWeeks,
            ],
            'building_info' => [
                'num_non_identical_buildings' => (int) ($input['num_buildings'] ?? 1),
                'num_all_buildings' => (int) ($input['num_buildings'] ?? 1),
                'scope' => $this->determineScope($categories),
            ],
            'special_requirements' => self::SPECIAL_REQUIREMENTS,
            'revision_history' => [],
        ];
    }

    /**
     * Determine scope type: Steel Only, Cladding Only, or Both.
     *
     * @param  array<string, array<string, mixed>>  $categories
     */
    private function determineScope(array $categories): string
    {
        $steelWeight = ($categories['A']['weight_kg'] ?? 0) + ($categories['C']['weight_kg'] ?? 0) + ($categories['D']['weight_kg'] ?? 0);
        $panelsWeight = ($categories['F']['weight_kg'] ?? 0) + ($categories['G']['weight_kg'] ?? 0) + ($categories['H']['weight_kg'] ?? 0);

        if ($steelWeight > 0 && $panelsWeight > 0) {
            return 'Both';
        }
        if ($steelWeight > 0) {
            return 'Steel Only';
        }
        if ($panelsWeight > 0) {
            return 'Cladding Only';
        }

        return 'Both';
    }
}
