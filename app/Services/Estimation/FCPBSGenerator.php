<?php

namespace App\Services\Estimation;

class FCPBSGenerator
{
    /**
     * Fallback mapping: product code → FCPBS category when cost_code is empty.
     * Based on VBA Excel structure where each product belongs to a specific category.
     *
     * @var array<string, string>
     */
    private const PRODUCT_CATEGORY_MAP = [
        // A - Main Frames
        'BU' => 'A', 'BuLeng' => 'A', 'DSW' => 'A', 'ConPlates' => 'A',
        'BUPortal' => 'A',
        // C - Secondary Members (purlins, girts, bracing)
        'Z15G' => 'C', 'Z18G' => 'C', 'Z20G' => 'C', 'Z25G' => 'C',
        'Gang' => 'C', 'Bang' => 'C', 'CFClip' => 'C', 'CFClip1' => 'C', 'CFClip2' => 'C',
        'EWC' => 'C', 'T200' => 'C', 'T150' => 'C', 'T125' => 'C',
        'BrGu' => 'C',
        // D - Steel Standard Buyouts (bolts, sag rods, bracing hardware)
        'HSB12' => 'D', 'HSB16' => 'D', 'HSB20' => 'D', 'HSB1250' => 'D',
        'HSB2060' => 'D', 'HSB2480' => 'D', 'AB16' => 'D', 'AB24' => 'D',
        'MFC1' => 'D', 'MFC2' => 'D', 'MFC3' => 'D', 'FMC1' => 'D', 'FMC2' => 'D',
        'SR12' => 'D', 'SR16' => 'D',
        'CBR' => 'D', 'RBR22' => 'D', 'FBA' => 'D', 'FBA2' => 'D', 'FBA3' => 'D', 'CRA' => 'D',
        // H - Trims
        'GT' => 'H', 'RP' => 'H', 'ET' => 'H', 'CP' => 'H', 'EG' => 'H',
        'CT' => 'H', 'DS' => 'H', 'RS' => 'H', 'GSTR' => 'H',
        'VGG' => 'H', 'VGS' => 'H', 'VGEC' => 'H', 'PB' => 'H',
        // F - Liner Panels (single skin liner sheeting)
        'S5OW' => 'F', 'A5OW' => 'F', 'S7OW' => 'F', 'A7OW' => 'F',
        'PUA' => 'F', 'PUS' => 'F',
        // I - Panels Standard Buyouts (screws, sealants, fasteners)
        'BM1' => 'I', 'BM2' => 'I',
        'CS1' => 'I', 'CS2' => 'I', 'CS4' => 'I',
        'SS1' => 'I', 'SS2' => 'I', 'SS4' => 'I',
        // M - Container & Skids
        'ContSkid' => 'M',
        // O - Freight
        'Freight' => 'O',
    ];

    /**
     * FCPBS category definitions with cost code mappings.
     * Each category aggregates Detail items by their cost/breakdown codes.
     *
     * @var array<string, array{name: string, codes: array<int, int>}>
     */
    private const CATEGORIES = [
        'A' => ['name' => 'Main Frames', 'codes' => [10111, 10211, 10212, 10311, 10312, 10313, 10314, 10315, 10316, 10317, 10318, 10411, 10511, 10512]],
        'B' => ['name' => 'Blasting & Painting', 'codes' => [10601, 10602, 10603, 10604, 10605]],
        'C' => ['name' => 'Secondary Members', 'codes' => [11111, 11211, 11212, 11213, 11214, 11215, 11216, 11217, 11218]],
        'D' => ['name' => 'Steel Standard Buyouts', 'codes' => [12111, 12211, 12212, 12213, 12311, 12312, 12411, 12412, 12413, 12414]],
        'F' => ['name' => 'Single Skin Panels', 'codes' => [20111, 20112, 20113, 20121, 20131, 20141, 20151, 20161]],
        'G' => ['name' => 'Sandwich Panels', 'codes' => [20211, 20311, 20411, 20511, 20512]],
        'H' => ['name' => 'Trims', 'codes' => [21111, 21211, 21311, 21411, 21511, 21611]],
        'I' => ['name' => 'Panels Standard Buyouts', 'codes' => [22111, 22211, 22212, 22311, 22312, 22321]],
        'J' => ['name' => 'Panels Accessories + Special Buyouts', 'codes' => [23111, 23112, 23121, 23122, 23211, 23212, 23213, 23214, 23215, 23216, 23217, 23218, 23311, 23312, 23313, 23314]],
        'M' => ['name' => 'Container & Skids', 'codes' => [30111, 30112]],
        'O' => ['name' => 'Freight', 'codes' => [40111, 40112, 40113, 40114, 40115, 40116, 40211, 40212, 40213, 40214, 40215, 40216]],
        'Q' => ['name' => 'Other Charges', 'codes' => [50111, 50112, 50113, 50114, 50115, 50211, 50212, 50311]],
        'T' => ['name' => 'Erection', 'codes' => [60111]],
    ];

    /**
     * Generate FCPBS data from detail items.
     *
     * @param  array<int, array<string, mixed>>  $detailItems  Items from DetailGenerator
     * @param  array<string, float>  $markups  Category markup factors: ['steel' => 0.8089, 'panels' => 1.0, 'ssl' => 1.0, ...]
     * @return array{
     *     categories: array<string, array>,
     *     steel_subtotal: array,
     *     panels_subtotal: array,
     *     fob_price: float,
     *     total_price: float,
     *     total_weight_kg: float
     * }
     */
    public function generate(array $detailItems, array $markups = []): array
    {
        // Default markups from the Excel sample.
        // Use isset() instead of !empty() so that an explicit 0 is honoured (not treated as "use default").
        $steelMarkup = isset($markups['steel']) && $markups['steel'] !== '' ? (float) $markups['steel'] : 0.80885358250258;
        $panelsMarkup = isset($markups['panels']) && $markups['panels'] !== '' ? (float) $markups['panels'] : 1.0;
        // TODO: $sslMarkup and $financeMarkup are reserved for future SSL/Finance category calculations.
        $sslMarkup = isset($markups['ssl']) && $markups['ssl'] !== '' ? (float) $markups['ssl'] : 1.0;
        $financeMarkup = isset($markups['finance']) && $markups['finance'] !== '' ? (float) $markups['finance'] : 1.0;

        $categories = [];
        $totalWeight = 0.0;

        foreach (self::CATEGORIES as $catKey => $catDef) {
            $catWeight = 0.0;
            $catMaterialCost = 0.0;
            $catManufCost = 0.0;
            $catOhCost = 0.0;
            $catTotalCost = 0.0;
            $catQuantity = 0.0;

            // Determine markup for this category
            $markup = match (true) {
                in_array($catKey, ['A', 'B', 'C', 'D']) => $steelMarkup,
                in_array($catKey, ['F', 'G', 'H', 'I', 'J']) => $panelsMarkup,
                $catKey === 'M' => 1.0,
                $catKey === 'O' => 1.0,
                $catKey === 'Q' => 1.0,
                $catKey === 'T' => 1.0,
                default => $steelMarkup,
            };

            // Aggregate from detail items
            foreach ($detailItems as $item) {
                if ($item['is_header'] ?? false) {
                    continue;
                }

                $itemCostCode = $item['cost_code'] ?? '';
                $itemCode = $item['code'] ?? '';

                // Match by cost code, or fall back to product code mapping
                $matchesByCostCode = $itemCostCode !== '' && in_array((int) $itemCostCode, $catDef['codes']);
                $matchesByProductCode = $itemCostCode === '' && $this->getProductCategory($itemCode) === $catKey;

                if ($matchesByCostCode || $matchesByProductCode) {
                    $weight = (float) ($item['weight_per_unit'] ?? 0) * (float) ($item['size'] ?? 1) * (float) ($item['qty'] ?? 0);
                    $cost = (float) ($item['rate'] ?? 0) * (float) ($item['size'] ?? 1) * (float) ($item['qty'] ?? 0);

                    $catWeight += $weight;
                    $catMaterialCost += $cost;
                    $catManufCost += $cost * 0.3;  // Approximate manufacturing = 30% of material
                    $catOhCost += $cost * 0.3;      // Approximate overhead = 30% of material
                    $catTotalCost += $cost * 1.6;    // Total = material + manufacturing + overhead
                    $catQuantity += (float) ($item['qty'] ?? 0);
                }
            }

            $sellingPrice = $catTotalCost * $markup;
            $valueAdded = $sellingPrice - $catMaterialCost;
            $pricePerMT = ($catWeight > 0) ? 1000 * $sellingPrice / $catWeight : 0;
            $vaPerMT = ($catWeight > 0) ? 1000 * $valueAdded / $catWeight : 0;

            $categories[$catKey] = [
                'key' => $catKey,
                'name' => $catDef['name'],
                'quantity' => round($catQuantity, 3),
                'weight_kg' => round($catWeight, 3),
                'weight_pct' => 0, // Will be calculated after totals
                'material_cost' => round($catMaterialCost, 2),
                'manufacturing_cost' => round($catManufCost, 2),
                'overhead_cost' => round($catOhCost, 2),
                'total_cost' => round($catTotalCost, 2),
                'markup' => $markup,
                'selling_price' => round($sellingPrice, 2),
                'selling_price_pct' => 0,
                'price_per_mt' => round($pricePerMT, 2),
                'value_added' => round($valueAdded, 2),
                'va_per_mt' => round($vaPerMT, 2),
            ];

            $totalWeight += $catWeight;
        }

        // Calculate weight percentages
        foreach ($categories as &$cat) {
            $cat['weight_pct'] = ($totalWeight > 0) ? round(100 * $cat['weight_kg'] / $totalWeight, 2) : 0;
        }

        // Calculate subtotals
        $steelCats = ['A', 'B', 'C', 'D'];
        $panelsCats = ['F', 'G', 'H', 'I', 'J'];

        $steelSubtotal = $this->calculateSubtotal($categories, $steelCats);
        $panelsSubtotal = $this->calculateSubtotal($categories, $panelsCats);
        $fobPrice = $steelSubtotal['selling_price'] + $panelsSubtotal['selling_price'];

        $totalSellingPrice = 0;
        foreach ($categories as $cat) {
            $totalSellingPrice += $cat['selling_price'];
        }

        // Calculate selling price percentages
        foreach ($categories as &$cat) {
            $cat['selling_price_pct'] = ($totalSellingPrice > 0) ? round(100 * $cat['selling_price'] / $totalSellingPrice, 2) : 0;
        }

        return [
            'categories' => $categories,
            'steel_subtotal' => $steelSubtotal,
            'panels_subtotal' => $panelsSubtotal,
            'fob_price' => round($fobPrice, 2),
            'total_price' => round($totalSellingPrice, 2),
            'total_weight_kg' => round($totalWeight, 3),
            'total_weight_mt' => round($totalWeight / 1000, 4),
        ];
    }

    /**
     * Calculate subtotal for a group of categories.
     *
     * @param  array<string, array>  $categories
     * @param  array<int, string>  $catKeys
     * @return array<string, float>
     */
    private function calculateSubtotal(array $categories, array $catKeys): array
    {
        $subtotal = [
            'weight_kg' => 0,
            'material_cost' => 0,
            'manufacturing_cost' => 0,
            'overhead_cost' => 0,
            'total_cost' => 0,
            'selling_price' => 0,
            'value_added' => 0,
        ];

        foreach ($catKeys as $key) {
            if (isset($categories[$key])) {
                $subtotal['weight_kg'] += $categories[$key]['weight_kg'];
                $subtotal['material_cost'] += $categories[$key]['material_cost'];
                $subtotal['manufacturing_cost'] += $categories[$key]['manufacturing_cost'];
                $subtotal['overhead_cost'] += $categories[$key]['overhead_cost'];
                $subtotal['total_cost'] += $categories[$key]['total_cost'];
                $subtotal['selling_price'] += $categories[$key]['selling_price'];
                $subtotal['value_added'] += $categories[$key]['value_added'];
            }
        }

        return array_map(fn ($v) => round($v, 2), $subtotal);
    }

    /**
     * Resolve FCPBS category from product code.
     * Handles exact matches and prefix matches (e.g. 'GTS' matches 'GT' prefix).
     */
    private function getProductCategory(string $productCode): ?string
    {
        if ($productCode === '' || $productCode === '-') {
            return null;
        }

        // Exact match first
        if (isset(self::PRODUCT_CATEGORY_MAP[$productCode])) {
            return self::PRODUCT_CATEGORY_MAP[$productCode];
        }

        // Prefix match for trim codes with suffixes (e.g. GTS, RPS, ETA, CPS, etc.)
        foreach (self::PRODUCT_CATEGORY_MAP as $prefix => $cat) {
            if (strlen($prefix) >= 2 && str_starts_with($productCode, $prefix)) {
                return $cat;
            }
        }

        return null;
    }
}
