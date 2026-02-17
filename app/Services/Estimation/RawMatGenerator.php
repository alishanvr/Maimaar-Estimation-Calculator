<?php

namespace App\Services\Estimation;

class RawMatGenerator
{
    /**
     * Category mapping by product code prefix.
     *
     * @var array<string, list<string>>
     */
    /**
     * Order matters: categories with longer/more-specific prefixes must come
     * before categories with short prefixes that could match the same codes.
     * For example, 'CRANE'/'CR'/'CON' must be checked before 'C'.
     */
    private const CATEGORY_PREFIXES = [
        'Primary Steel' => ['BU', 'HR', 'CON', 'PL'],
        'Fasteners & Bolts' => ['HSB', 'AB', 'BOLT', 'SS2', 'SS4', 'CS2', 'CS4'],
        'Crane Components' => ['CRANE', 'RUNWAY', 'CR'],
        'Mezzanine' => ['MEZZ', 'MZ'],
        'Liner Panels' => ['LINER', 'PU'],
        'Roof/Wall Sheeting' => ['S5', 'S7', 'A5', 'A7', 'CORE', 'SHEET'],
        'Trim & Flashing' => ['TRIM', 'FLASH', 'RC', 'WC', 'TT', 'ET', 'ST'],
        'Doors & Windows' => ['DOOR', 'WINDOW', 'LOUVER'],
        'Gutters & Downspouts' => ['GUTTER', 'DS', 'DOWNSPOUT', 'EG'],
        'Secondary Steel' => ['Z', 'C', 'PURLIN', 'GIRT', 'EAV', 'BASE'],
    ];

    /**
     * Desired category sort order.
     *
     * @var array<string, int>
     */
    private const CATEGORY_ORDER = [
        'Primary Steel' => 1,
        'Secondary Steel' => 2,
        'Roof/Wall Sheeting' => 3,
        'Fasteners & Bolts' => 4,
        'Trim & Flashing' => 5,
        'Doors & Windows' => 6,
        'Gutters & Downspouts' => 7,
        'Crane Components' => 8,
        'Mezzanine' => 9,
        'Liner Panels' => 10,
        'Other' => 11,
    ];

    /**
     * Generate RAWMAT (Raw Material) aggregation data from detail items.
     *
     * Groups detail items by product code, sums quantities and weights, categorizes
     * by code prefix, and returns a procurement-friendly summary.
     *
     * @param  array<int, array<string, mixed>>  $detailItems  Detail sheet items
     * @return array{
     *     items: list<array>,
     *     summary: array{total_items_before: int, unique_materials: int, total_weight_kg: float, category_count: int},
     *     categories: array<string, array{count: int, weight_kg: float}>
     * }
     */
    public function generate(array $detailItems): array
    {
        $totalItemsBefore = 0;

        /** @var array<string, array{code: string, cost_code: string, description: string, unit: string, quantity: float, unit_weight: float, total_weight: float, category: string, sources: array<string, bool>}> */
        $aggregated = [];

        foreach ($detailItems as $item) {
            // Skip header rows
            if (! empty($item['is_header'])) {
                continue;
            }

            $code = trim((string) ($item['code'] ?? ''));

            // Skip items with no real code
            if ($code === '' || $code === '-') {
                continue;
            }

            $totalItemsBefore++;

            $qty = (float) ($item['qty'] ?? 0);
            $size = (float) ($item['size'] ?? 0);
            $weightPerUnit = (float) ($item['weight_per_unit'] ?? 0);
            $description = (string) ($item['description'] ?? '');
            $costCode = (string) ($item['cost_code'] ?? '');
            $unit = (string) ($item['unit'] ?? '');
            $salesCode = (string) ($item['sales_code'] ?? '');

            // Compute total units and total weight for this line
            $lineQty = $qty * max($size, 1);
            $lineWeight = $weightPerUnit * $qty * max($size, 1);

            if (! isset($aggregated[$code])) {
                $aggregated[$code] = [
                    'code' => $code,
                    'cost_code' => $costCode,
                    'description' => $description,
                    'unit' => $unit,
                    'quantity' => 0.0,
                    'unit_weight' => $weightPerUnit,
                    'total_weight' => 0.0,
                    'category' => $this->categorizeItem($code),
                    'sources' => [],
                ];
            }

            $aggregated[$code]['quantity'] += $lineQty;
            $aggregated[$code]['total_weight'] += $lineWeight;

            // Track source sections
            if ($salesCode !== '' && $salesCode !== '0') {
                $aggregated[$code]['sources'][$salesCode] = true;
            }
        }

        // Sort by category order, then by code alphabetically
        uasort($aggregated, function (array $a, array $b): int {
            $orderA = self::CATEGORY_ORDER[$a['category']] ?? 99;
            $orderB = self::CATEGORY_ORDER[$b['category']] ?? 99;

            if ($orderA !== $orderB) {
                return $orderA <=> $orderB;
            }

            return strcmp($a['code'], $b['code']);
        });

        // Build final items list with sequential numbering and source strings
        $items = [];
        $no = 1;
        $categoryStats = [];
        $totalWeight = 0.0;

        foreach ($aggregated as $entry) {
            $sourceList = array_keys($entry['sources']);
            sort($sourceList);

            $items[] = [
                'no' => $no++,
                'code' => $entry['code'],
                'cost_code' => $entry['cost_code'],
                'description' => $entry['description'],
                'unit' => $entry['unit'],
                'quantity' => round($entry['quantity'], 2),
                'unit_weight' => round($entry['unit_weight'], 4),
                'total_weight' => round($entry['total_weight'], 2),
                'category' => $entry['category'],
                'sources' => implode(', ', $sourceList),
            ];

            $cat = $entry['category'];
            if (! isset($categoryStats[$cat])) {
                $categoryStats[$cat] = ['count' => 0, 'weight_kg' => 0.0];
            }
            $categoryStats[$cat]['count']++;
            $categoryStats[$cat]['weight_kg'] += $entry['total_weight'];

            $totalWeight += $entry['total_weight'];
        }

        // Round category weights
        foreach ($categoryStats as &$stat) {
            $stat['weight_kg'] = round($stat['weight_kg'], 2);
        }
        unset($stat);

        return [
            'items' => $items,
            'summary' => [
                'total_items_before' => $totalItemsBefore,
                'unique_materials' => count($items),
                'total_weight_kg' => round($totalWeight, 2),
                'category_count' => count($categoryStats),
            ],
            'categories' => $categoryStats,
        ];
    }

    /**
     * Flattened and sorted prefix → category lookup, built once on first use.
     * Sorted by prefix length descending so longer (more specific) prefixes
     * are matched before shorter ones (e.g. 'PURLIN' before 'PU', 'CRANE' before 'CR' before 'C').
     *
     * @var list<array{prefix: string, category: string}>|null
     */
    private ?array $sortedPrefixes = null;

    /**
     * Categorize a material by its product code prefix.
     */
    private function categorizeItem(string $code): string
    {
        if ($this->sortedPrefixes === null) {
            $this->sortedPrefixes = [];
            foreach (self::CATEGORY_PREFIXES as $category => $prefixes) {
                foreach ($prefixes as $prefix) {
                    $this->sortedPrefixes[] = ['prefix' => $prefix, 'category' => $category];
                }
            }
            usort($this->sortedPrefixes, fn (array $a, array $b): int => strlen($b['prefix']) <=> strlen($a['prefix']));
        }

        $upperCode = strtoupper($code);

        foreach ($this->sortedPrefixes as $entry) {
            if (str_starts_with($upperCode, $entry['prefix'])) {
                return $entry['category'];
            }
        }

        return 'Other';
    }
}
