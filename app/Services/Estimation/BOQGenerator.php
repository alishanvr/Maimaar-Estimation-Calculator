<?php

namespace App\Services\Estimation;

class BOQGenerator
{
    /**
     * BOQ line item definitions matching the 9 items in BOQ sheet.
     *
     * @var array<int, array{name: string, description: string}>
     */
    private const BOQ_ITEMS = [
        1 => ['name' => 'primary', 'description' => 'Primary steel: All Built-up & Hot rolled steel including cleaning & painting'],
        2 => ['name' => 'secondary', 'description' => 'Secondary (Cold form purlins, girts, sheeting angles)'],
        3 => ['name' => 'sandwich', 'description' => 'Sandwich panels'],
        4 => ['name' => 'single_skin', 'description' => 'Single skin Panels, trims & flashings'],
        5 => ['name' => 'accessories', 'description' => 'Standard Sheeting accessories (sheeting fasteners, bead mastic, foam closure etc), Gutter & downspouts (if applicable)'],
        6 => ['name' => 'bolts', 'description' => 'Anchor bolts, connection bolts, sag rods, cable bracing'],
        7 => ['name' => 'special_accessories', 'description' => 'Accessories (excluding insulation & translucent panels)'],
        8 => ['name' => 'insulation', 'description' => 'Fiberglass Insulation'],
        9 => ['name' => 'translucent', 'description' => 'Translucent panels w/ wiremesh protection'],
    ];

    /**
     * Generate BOQ (Bill of Quantities) data.
     *
     * The BOQ sheet provides a customer-facing 9-item summary built from FCPBS categories.
     * Each BOQ item aggregates weight and price from specific FCPBS categories.
     *
     * Excel BOQ mapping:
     *   1. Primary = FCPBS A (Main Frames) + FCPBS B (Blasting/Painting)
     *   2. Secondary = FCPBS C (Secondary Members)
     *   3. Sandwich = FCPBS G (Sandwich Panels)
     *   4. Single Skin = FCPBS F (Single Skin) + FCPBS H (Trims)
     *   5. Accessories = FCPBS I (Panels Standard Buyouts)
     *   6. Bolts/Bracing = FCPBS D (Steel Standard Buyouts)
     *   7. Special Accessories = FCPBS J minus insulation/translucent items
     *   8. Insulation = insulation items from FCPBS J
     *   9. Translucent = translucent items from FCPBS F
     *
     * @param  array<string, mixed>  $fcpbsData  Full FCPBS output
     * @param  array<string, mixed>  $freightData  Freight calculation output
     * @param  array<string, mixed>  $input  Input data
     * @return array{
     *     items: array<int, array>,
     *     total_weight_mt: float,
     *     total_price: float,
     *     price_breakdown: array,
     *     transport_breakdown: array,
     *     charges_breakdown: array
     * }
     */
    public function generate(array $fcpbsData, array $freightData, array $input): array
    {
        $categories = $fcpbsData['categories'] ?? [];

        // Price breakdown excluding transport and charges (BOQ cols J-L, rows 25-33)
        $priceBreakdown = $this->buildPriceBreakdown($categories);

        // Transport breakdown (BOQ cols J-L, rows 11-20)
        $transportBreakdown = $this->buildTransportBreakdown($freightData, $priceBreakdown);

        // Charges breakdown (BOQ cols J-L, rows 38-47)
        $chargesBreakdown = $this->buildChargesBreakdown($fcpbsData, $priceBreakdown);

        // Build 9 BOQ line items
        $items = [];
        $totalWeight = 0;
        $totalPrice = 0;

        foreach (self::BOQ_ITEMS as $num => $def) {
            $pb = $priceBreakdown[$num] ?? ['weight_mt' => 0, 'price' => 0];
            $tb = $transportBreakdown[$num] ?? ['price' => 0];
            $cb = $chargesBreakdown[$num] ?? ['price' => 0];

            $weight = $pb['weight_mt'];
            $price = $pb['price'] + $tb['price'] + $cb['price'];
            $unitRate = ($weight > 0) ? $price / $weight : 0;

            $items[] = [
                'sl_no' => $num,
                'description' => $def['description'],
                'unit' => 'MT',
                'quantity' => round($weight, 4),
                'unit_rate' => round($unitRate, 2),
                'total_price' => round($price, 2),
            ];

            $totalWeight += $weight;
            $totalPrice += $price;
        }

        return [
            'items' => $items,
            'total_weight_mt' => round($totalWeight, 4),
            'total_price' => round($totalPrice, 2),
            'price_breakdown' => $priceBreakdown,
            'transport_breakdown' => $transportBreakdown,
            'charges_breakdown' => $chargesBreakdown,
        ];
    }

    /**
     * Build material price breakdown from FCPBS categories.
     * Excel: BOQ rows 25-34, columns J-L
     *
     * @return array<int, array{weight_mt: float, price: float}>
     */
    private function buildPriceBreakdown(array $categories): array
    {
        $catWeight = fn (string $key): float => ($categories[$key]['weight_kg'] ?? 0) / 1000;
        $catPrice = fn (string $key): float => (float) ($categories[$key]['selling_price'] ?? 0);

        return [
            // 1. Primary = A (Main Frames) + B (Blasting & Painting)
            1 => [
                'weight_mt' => $catWeight('A') + $catWeight('B'),
                'price' => $catPrice('A') + $catPrice('B'),
            ],
            // 2. Secondary = C (Secondary Members)
            2 => [
                'weight_mt' => $catWeight('C'),
                'price' => $catPrice('C'),
            ],
            // 3. Sandwich = G (Sandwich Panels)
            3 => [
                'weight_mt' => $catWeight('G'),
                'price' => $catPrice('G'),
            ],
            // 4. Single skin + trims = F (single skin) - translucent + H (trims)
            4 => [
                'weight_mt' => $catWeight('F'),
                'price' => $catPrice('F'),
            ],
            // 5. Standard sheeting accessories = I (Panels Standard Buyouts) trim/gutter portion
            5 => [
                'weight_mt' => ($categories['H']['weight_kg'] ?? 0) / 1000 + ($categories['I']['weight_kg'] ?? 0) / 1000,
                'price' => $catPrice('H') + $catPrice('I'),
            ],
            // 6. Bolts/bracing = D (Steel Standard Buyouts)
            6 => [
                'weight_mt' => $catWeight('D'),
                'price' => $catPrice('D'),
            ],
            // 7. Accessories = J (Panels Accessories + Special Buyouts) minus insulation/translucent
            7 => [
                'weight_mt' => $catWeight('J'),
                'price' => $catPrice('J'),
            ],
            // 8. Insulation (from J items with insulation cost codes)
            8 => [
                'weight_mt' => 0,
                'price' => 0,
            ],
            // 9. Translucent panels (from F items with translucent cost codes)
            9 => [
                'weight_mt' => 0,
                'price' => 0,
            ],
        ];
    }

    /**
     * Build transport cost breakdown proportionally distributed.
     * Excel: BOQ rows 11-20, columns J-L
     *
     * Transport cost uses FCPBS categories M (Container & Skids) + O (Freight)
     * selling prices so BOQ totals align with the FCPBS total.
     *
     * @return array<int, array{loads: float, price: float}>
     */
    private function buildTransportBreakdown(array $freightData, array $priceBreakdown): array
    {
        // Use FCPBS selling prices for M+O so BOQ total matches FCPBS total
        $categories = $freightData['_fcpbs_categories'] ?? [];
        $totalTransport = (float) ($categories['M']['selling_price'] ?? 0)
            + (float) ($categories['O']['selling_price'] ?? 0);

        // Fallback to raw freight data if FCPBS categories not passed
        if ($totalTransport <= 0) {
            $totalTransport = ($freightData['total_freight_cost'] ?? 0)
                + ($freightData['container_cost'] ?? 0);
        }

        $totalMaterialPrice = 0;
        foreach ($priceBreakdown as $pb) {
            $totalMaterialPrice += $pb['price'];
        }

        $breakdown = [];
        for ($i = 1; $i <= 9; $i++) {
            $proportion = ($totalMaterialPrice > 0) ? ($priceBreakdown[$i]['price'] ?? 0) / $totalMaterialPrice : 0;
            $breakdown[$i] = [
                'loads' => 0,
                'price' => round($totalTransport * $proportion, 2),
            ];
        }

        return $breakdown;
    }

    /**
     * Build other charges breakdown proportionally distributed.
     * Excel: BOQ rows 38-47
     *
     * @return array<int, array{price: float}>
     */
    private function buildChargesBreakdown(array $fcpbsData, array $priceBreakdown): array
    {
        $otherChargesPrice = (float) ($fcpbsData['categories']['Q']['selling_price'] ?? 0);
        $totalMaterialPrice = 0;
        foreach ($priceBreakdown as $pb) {
            $totalMaterialPrice += $pb['price'];
        }

        $breakdown = [];
        for ($i = 1; $i <= 9; $i++) {
            $proportion = ($totalMaterialPrice > 0) ? ($priceBreakdown[$i]['price'] ?? 0) / $totalMaterialPrice : 0;
            $breakdown[$i] = [
                'price' => round($otherChargesPrice * $proportion, 2),
            ];
        }

        return $breakdown;
    }
}
