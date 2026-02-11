<?php
/**
 * QuickEst - Accessory Calculator
 *
 * Calculates accessories: skylights, doors, louvers, ventilators
 * Replicates VBA AddAcc_Click() procedure
 */

namespace QuickEst\Services;

use QuickEst\Models\BillOfMaterials;
use QuickEst\Database\ProductLookup;

class AccessoryCalculator {

    /**
     * Accessory code mapping from description
     */
    private array $accessoryCodeMap = [
        // Skylights
        'Skylight 3250mm (GRP,Single Skin )' => 'SKY1S',
        'Skylight 3250mm (GRP, Double Skin 35 mm thk)' => 'SKY2S35',
        'Skylight 3250mm (GRP, Double Skin 50 mm thk)' => 'SKY2S50',
        'Skylight 3250mm (GRP, Double Skin 75 mm thk)' => 'SKY2S75',
        'Skylight 3250mm (GRP, Double Skin 100 mm thk)' => 'SKY2S100',

        // Personnel Doors
        'Personnel Door (900x2100)' => 'PD09',
        'Personnel Door (1200x2100)' => 'PD12',
        'Personnel Door Double (1800x2100)' => 'PD18',

        // Sliding Doors - Top Sliding
        'Slide door 3mX3m Steel Only with Framed Opening (Top Sliding)' => 'SD3T',
        'Slide door 4mX4m Steel Only with Framed Opening (Top Sliding)' => 'SD4T',
        'Slide door 5mX5m Steel Only with Framed Opening (Top Sliding)' => 'SD5T',
        'Slide door 6mX6m Steel Only with Framed Opening (Top Sliding)' => 'SD6T',

        // Sliding Doors - Dual Sliding
        'Slide door 3mX3m Steel Only with Framed Opening (Dual Sliding)' => 'SD3D',
        'Slide door 4mX4m Steel Only with Framed Opening (Dual Sliding)' => 'SD4D',
        'Slide door 5mX5m Steel Only with Framed Opening (Dual Sliding)' => 'SD5D',
        'Slide door 6mX6m Steel Only with Framed Opening (Dual Sliding)' => 'SD6D',

        // Louvers
        'Louver 600x600' => 'LV66',
        'Louver 900x900' => 'LV99',
        'Louver 1200x900' => 'LV129',

        // Ventilators
        'Ridge Ventilator' => 'RV',
        'Turbo Ventilator' => 'TV',
    ];

    /**
     * Sliding door sizes (area in m²)
     */
    private array $doorSizes = [
        'SD3T' => 9,   // 3x3
        'SD3D' => 9,
        'SD4T' => 16,  // 4x4
        'SD4D' => 16,
        'SD5T' => 25,  // 5x5
        'SD5D' => 25,
        'SD6T' => 36,  // 6x6
        'SD6D' => 36,
    ];

    /**
     * Skylight codes that require wire mesh
     */
    private array $skylightWithMesh = [
        'SKY1S', 'SKY2S35', 'SKY2S50', 'SKY2S75', 'SKY2S100'
    ];

    /**
     * Calculate accessory materials
     *
     * @param array $params Accessory parameters:
     *   - description: string
     *   - salesCode: int (default 1)
     *   - items: array of ['description' => string, 'quantity' => int]
     *   - wallTopSkin: string (for door sheeting)
     *   - wallCore: string (for door sheeting)
     *   - wallBotSkin: string (for door sheeting)
     */
    public function calculate(array $params): BillOfMaterials {
        $bom = new BillOfMaterials();

        $description = $params['description'] ?? 'Accessories';
        $salesCode = intval($params['salesCode'] ?? 1);
        $items = $params['items'] ?? [];

        // Wall sheeting parameters for door panels
        $wallTopSkin = $params['wallTopSkin'] ?? 'S5OW';
        $wallCore = $params['wallCore'] ?? '-';
        $wallBotSkin = $params['wallBotSkin'] ?? '-';

        // Process each accessory item
        $headerAdded = false;
        $totalDoorArea = 0;
        $addWireMesh = false;
        $wireMeshQty = 0;

        foreach ($items as $item) {
            $itemDesc = $item['description'] ?? '';
            $qty = intval($item['quantity'] ?? 0);

            if (empty($itemDesc) || $qty <= 0) {
                continue;
            }

            // Get code from description
            $code = $this->getAccessoryCode($itemDesc);

            if (empty($code)) {
                continue;
            }

            // Add header on first item
            if (!$headerAdded) {
                $bom->addCode($description, '-', $salesCode, '', '');
                $headerAdded = true;
            }

            // Add the accessory item
            $bom->addCode($itemDesc, $code, $salesCode, 1, $qty);

            // Check for wire mesh requirement (skylights)
            if ($this->needsWireMesh($code)) {
                $addWireMesh = true;
                // Wire mesh area: skylight qty * 3.8m * 1.219m
                $wireMeshQty += $qty * 3.8 * 1.219;
            }

            // Check for door sheeting (sliding doors)
            if ($this->isSlidingDoor($code)) {
                $doorArea = $this->getDoorArea($code) * $qty;
                $totalDoorArea += $doorArea;
            }
        }

        // Add wire mesh for skylights
        if ($addWireMesh && $wireMeshQty > 0) {
            $bom->addCode('GI Wire Mesh for Skylights', 'WRM', $salesCode, 1, round($wireMeshQty, 2));
        }

        // Add door sheeting if sandwich panel
        if ($totalDoorArea > 0) {
            $this->addDoorSheeting(
                $bom,
                $salesCode,
                $totalDoorArea,
                $wallTopSkin,
                $wallCore,
                $wallBotSkin
            );
        }

        // Close section
        if ($headerAdded) {
            $bom->addCode('', '-', $salesCode, '', '');
        }

        return $bom;
    }

    /**
     * Get accessory code from description
     */
    private function getAccessoryCode(string $description): string {
        // Direct lookup
        if (isset($this->accessoryCodeMap[$description])) {
            return $this->accessoryCodeMap[$description];
        }

        // Partial match for descriptions with variations
        foreach ($this->accessoryCodeMap as $desc => $code) {
            if (stripos($description, $desc) !== false) {
                return $code;
            }
        }

        // Try database lookup
        return ProductLookup::codeOf($description) ?? '';
    }

    /**
     * Check if skylight needs wire mesh
     */
    private function needsWireMesh(string $code): bool {
        return in_array($code, $this->skylightWithMesh);
    }

    /**
     * Check if code is a sliding door
     */
    private function isSlidingDoor(string $code): bool {
        return isset($this->doorSizes[$code]);
    }

    /**
     * Get door area from code
     */
    private function getDoorArea(string $code): float {
        return $this->doorSizes[$code] ?? 0;
    }

    /**
     * Add door sheeting materials
     */
    private function addDoorSheeting(
        BillOfMaterials $bom,
        int $salesCode,
        float $doorArea,
        string $wallTopSkin,
        string $wallCore,
        string $wallBotSkin
    ): void {
        // Generate sandwich panel code
        $swpCode = "PU{$wallTopSkin}{$wallCore}{$wallBotSkin}";

        if ($wallCore !== '-' && $wallBotSkin !== '-') {
            // Sandwich panel configuration
            $bom->addCode('Door Sheeting', '-', $salesCode, '', '');
            $bom->addCode("Mammut SWP Code: {$swpCode}", $wallTopSkin, $salesCode, 1, $doorArea);
            $bom->addCode('', $wallCore, $salesCode, 1, $doorArea);
            $bom->addCode('', $wallBotSkin, $salesCode, 1, $doorArea);
            $bom->addCode($swpCode, '-', $salesCode, '', '');
        } else {
            // Single skin configuration
            $bom->addCode('Door Sheeting', $wallTopSkin, $salesCode, 1, $doorArea);
        }
    }

    /**
     * Get all available accessories for UI
     */
    public static function getAvailableAccessories(): array {
        return [
            'skylights' => [
                'Skylight 3250mm (GRP,Single Skin )' => 'Skylight 3250mm GRP Single Skin',
                'Skylight 3250mm (GRP, Double Skin 35 mm thk)' => 'Skylight 3250mm Double Skin 35mm',
                'Skylight 3250mm (GRP, Double Skin 50 mm thk)' => 'Skylight 3250mm Double Skin 50mm',
                'Skylight 3250mm (GRP, Double Skin 75 mm thk)' => 'Skylight 3250mm Double Skin 75mm',
                'Skylight 3250mm (GRP, Double Skin 100 mm thk)' => 'Skylight 3250mm Double Skin 100mm',
            ],
            'personnel_doors' => [
                'Personnel Door (900x2100)' => 'Personnel Door 900x2100',
                'Personnel Door (1200x2100)' => 'Personnel Door 1200x2100',
                'Personnel Door Double (1800x2100)' => 'Personnel Door Double 1800x2100',
            ],
            'sliding_doors' => [
                'Slide door 3mX3m Steel Only with Framed Opening (Top Sliding)' => 'Sliding Door 3x3m Top',
                'Slide door 4mX4m Steel Only with Framed Opening (Top Sliding)' => 'Sliding Door 4x4m Top',
                'Slide door 5mX5m Steel Only with Framed Opening (Top Sliding)' => 'Sliding Door 5x5m Top',
                'Slide door 6mX6m Steel Only with Framed Opening (Top Sliding)' => 'Sliding Door 6x6m Top',
                'Slide door 3mX3m Steel Only with Framed Opening (Dual Sliding)' => 'Sliding Door 3x3m Dual',
                'Slide door 4mX4m Steel Only with Framed Opening (Dual Sliding)' => 'Sliding Door 4x4m Dual',
                'Slide door 5mX5m Steel Only with Framed Opening (Dual Sliding)' => 'Sliding Door 5x5m Dual',
                'Slide door 6mX6m Steel Only with Framed Opening (Dual Sliding)' => 'Sliding Door 6x6m Dual',
            ],
            'louvers' => [
                'Louver 600x600' => 'Louver 600x600',
                'Louver 900x900' => 'Louver 900x900',
                'Louver 1200x900' => 'Louver 1200x900',
            ],
            'ventilators' => [
                'Ridge Ventilator' => 'Ridge Ventilator',
                'Turbo Ventilator' => 'Turbo Ventilator',
            ],
        ];
    }
}
