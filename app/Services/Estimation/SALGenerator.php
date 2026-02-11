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

        // Build a map of FCPBS category key → markup factor so we can derive offer prices
        $categoryMarkups = [];
        foreach ($fcpbsData['categories'] ?? [] as $catKey => $catData) {
            $catCost = (float) ($catData['total_cost'] ?? 0);
            $catSelling = (float) ($catData['selling_price'] ?? 0);
            $categoryMarkups[$catKey] = ($catCost > 0) ? $catSelling / $catCost : 1.0;
        }

        // Sum all book prices from Detail (Detail!O208 equivalent)
        // Compute total_weight and book_price_total from detail item fields inline
        $totalBookPrice = 0;
        foreach ($detailItems as $item) {
            if (! ($item['is_header'] ?? false)) {
                $size = (float) ($item['size'] ?? 0);
                $qty = (float) ($item['qty'] ?? 0);
                $rate = (float) ($item['rate'] ?? 0);
                $totalBookPrice += $rate * $size * $qty;
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
                'prices_sum' => 0,       // Sum of book_price_total (rate * size * qty)
                'prices_mdup_sum' => 0,  // Sum of offer_price_total (book_price * category markup)
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

            $size = (float) ($item['size'] ?? 0);
            $qty = (float) ($item['qty'] ?? 0);
            $weightPerUnit = (float) ($item['weight_per_unit'] ?? 0);
            $rate = (float) ($item['rate'] ?? 0);

            $itemWeight = $weightPerUnit * $size * $qty;
            $itemBookPrice = $rate * $size * $qty;

            // Determine this item's FCPBS category to get the markup
            $costCode = (string) ($item['cost_code'] ?? '');
            $catKey = $this->guessFcpbsCategory($costCode, (string) ($item['item_code'] ?? ''));
            $markup = $categoryMarkups[$catKey] ?? 1.0;
            $itemOfferPrice = $itemBookPrice * $markup;

            $aggregated[$sc]['weight'] += $itemWeight;
            $aggregated[$sc]['prices_sum'] += $itemBookPrice;
            $aggregated[$sc]['prices_mdup_sum'] += $itemOfferPrice;
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

    /**
     * FCPBS category definitions with cost code mappings.
     * Mirrors FCPBSGenerator::CATEGORIES so SAL can determine which
     * FCPBS category a detail item belongs to for markup lookup.
     *
     * @var array<string, array<int, int>>
     */
    private const CATEGORY_COST_CODES = [
        'A' => [10111, 10211, 10212, 10311, 10312, 10313, 10314, 10315, 10316, 10317, 10318, 10411, 10511, 10512],
        'B' => [10601, 10602, 10603, 10604, 10605],
        'C' => [11111, 11211, 11212, 11213, 11214, 11215, 11216, 11217, 11218],
        'D' => [12111, 12211, 12212, 12213, 12311, 12312, 12411, 12412, 12413, 12414],
        'F' => [20111, 20112, 20113, 20121, 20131, 20141, 20151, 20161],
        'G' => [20211, 20311, 20411, 20511, 20512],
        'H' => [21111, 21211, 21311, 21411, 21511, 21611],
        'I' => [22111, 22211, 22212, 22311, 22312, 22321],
        'J' => [23111, 23112, 23121, 23122, 23211, 23212, 23213, 23214, 23215, 23216, 23217, 23218, 23311, 23312, 23313, 23314],
        'M' => [30111, 30112],
        'O' => [40111, 40112, 40113, 40114, 40115, 40116, 40211, 40212, 40213, 40214, 40215, 40216],
        'Q' => [50111, 50112, 50113, 50114, 50115, 50211, 50212, 50311],
        'T' => [60111],
    ];

    /**
     * Fallback product-code → FCPBS category mapping.
     * Mirrors FCPBSGenerator::PRODUCT_CATEGORY_MAP.
     *
     * @var array<string, string>
     */
    private const PRODUCT_CATEGORY_MAP = [
        'BU' => 'A', 'BuLeng' => 'A', 'DSW' => 'A', 'ConPlates' => 'A', 'BUPortal' => 'A',
        'Z15G' => 'C', 'Z18G' => 'C', 'Z20G' => 'C', 'Z25G' => 'C',
        'Gang' => 'C', 'Bang' => 'C', 'CFClip' => 'C', 'CFClip1' => 'C', 'CFClip2' => 'C',
        'EWC' => 'C', 'T200' => 'C', 'T150' => 'C', 'T125' => 'C', 'BrGu' => 'C',
        'HSB12' => 'D', 'HSB16' => 'D', 'HSB20' => 'D', 'HSB1250' => 'D',
        'HSB2060' => 'D', 'HSB2480' => 'D', 'AB16' => 'D', 'AB24' => 'D',
        'MFC1' => 'D', 'MFC2' => 'D', 'MFC3' => 'D', 'FMC1' => 'D', 'FMC2' => 'D',
        'SR12' => 'D', 'SR16' => 'D',
        'CBR' => 'D', 'RBR22' => 'D', 'FBA' => 'D', 'FBA2' => 'D', 'FBA3' => 'D', 'CRA' => 'D',
        'GT' => 'H', 'RP' => 'H', 'ET' => 'H', 'CP' => 'H', 'EG' => 'H',
        'CT' => 'H', 'DS' => 'H', 'RS' => 'H', 'GSTR' => 'H',
        'VGG' => 'H', 'VGS' => 'H', 'VGEC' => 'H', 'PB' => 'H',
        'BM1' => 'I', 'BM2' => 'I',
        'ContSkid' => 'M',
        'Freight' => 'O',
    ];

    /**
     * Determine which FCPBS category a detail item belongs to.
     *
     * Uses the same matching logic as FCPBSGenerator:
     *   1. If item has a cost_code, match against CATEGORY_COST_CODES
     *   2. Otherwise, fall back to PRODUCT_CATEGORY_MAP by item_code
     *
     * @param  string  $costCode  The item's cost_code (e.g. "10111")
     * @param  string  $itemCode  The item's product/item code (e.g. "BU", "Z20G")
     * @return string FCPBS category key (e.g. "A", "C", "D") or "A" as default
     */
    private function guessFcpbsCategory(string $costCode, string $itemCode): string
    {
        // Match by cost code first
        if ($costCode !== '') {
            $numericCode = (int) $costCode;
            foreach (self::CATEGORY_COST_CODES as $catKey => $codes) {
                if (in_array($numericCode, $codes)) {
                    return $catKey;
                }
            }
        }

        // Fall back to product code mapping
        if ($itemCode !== '' && $itemCode !== '-') {
            // Exact match
            if (isset(self::PRODUCT_CATEGORY_MAP[$itemCode])) {
                return self::PRODUCT_CATEGORY_MAP[$itemCode];
            }

            // Prefix match (e.g. 'GTS' matches 'GT')
            foreach (self::PRODUCT_CATEGORY_MAP as $prefix => $cat) {
                if (strlen($prefix) >= 2 && str_starts_with($itemCode, $prefix)) {
                    return $cat;
                }
            }
        }

        // Default to 'A' (Main Frames) when no match found
        return 'A';
    }
}
