<?php
/**
 * QuickEst - Product Lookup Service
 *
 * Provides database lookup functions for product codes, weights, prices, etc.
 * Replicates VBA functions: weight(), DField(), CodeOf()
 */

namespace QuickEst\Database;

class ProductLookup {

    private static ?array $productIndex = null;
    private static ?array $ssIndex = null;

    /**
     * Build index for fast lookup
     */
    private static function buildIndex(): void {
        if (self::$productIndex === null) {
            self::$productIndex = [];
            $products = \Database::getProducts();
            foreach ($products as $product) {
                $code = strtoupper(trim($product['code']));
                self::$productIndex[$code] = $product;
            }
        }
    }

    private static function buildSsIndex(): void {
        if (self::$ssIndex === null) {
            self::$ssIndex = [];
            $items = \Database::getStructuralSteel();
            foreach ($items as $item) {
                $code = strtoupper(trim($item['code']));
                self::$ssIndex[$code] = $item;
            }
        }
    }

    /**
     * Get product by code
     */
    public static function getProduct(string $code): ?array {
        self::buildIndex();
        $code = strtoupper(trim($code));
        return self::$productIndex[$code] ?? null;
    }

    /**
     * Get structural steel item by code
     */
    public static function getStructuralSteel(string $code): ?array {
        self::buildSsIndex();
        $code = strtoupper(trim($code));
        return self::$ssIndex[$code] ?? null;
    }

    /**
     * Get weight per unit - Replicates VBA weight() function
     */
    public static function weight(string $code): float {
        $product = self::getProduct($code);
        if ($product) {
            return floatval($product['weight']);
        }
        return 0;
    }

    /**
     * Get specific field from product - Replicates VBA DField() function
     *
     * Columns: 1=code, 2=erp, 3=?, 4=description, 5=unit, 6=weight,
     *          7=material_cost, 8=manuf_cost, 9=overhead_cost, 10=price
     */
    public static function dField(string $code, int $column): mixed {
        $product = self::getProduct($code);
        if (!$product) {
            return null;
        }

        $mapping = [
            1 => 'code',
            2 => 'erp_code',
            4 => 'description',
            5 => 'unit',
            6 => 'weight',
            7 => 'material_cost',
            8 => 'manuf_cost',
            9 => 'overhead_cost',
            10 => 'price',
            18 => 'phase_number',
        ];

        if (isset($mapping[$column]) && isset($product[$mapping[$column]])) {
            return $product[$mapping[$column]];
        }

        return null;
    }

    /**
     * Get code from description - Replicates VBA CodeOf() function
     */
    public static function codeOf(string $description): string {
        self::buildIndex();

        // Try exact match first
        foreach (self::$productIndex as $code => $product) {
            if (strcasecmp($product['description'], $description) === 0) {
                return $code;
            }
        }

        // Try partial match
        $descLower = strtolower($description);
        foreach (self::$productIndex as $code => $product) {
            if (strpos(strtolower($product['description']), $descLower) !== false) {
                return $code;
            }
        }

        // Special case for "None"
        if (strtolower($description) === 'none') {
            return 'None';
        }

        return $description; // Return as-is if not found
    }

    /**
     * Get price per unit
     */
    public static function getPrice(string $code): float {
        $product = self::getProduct($code);
        if ($product) {
            return floatval($product['price']);
        }
        return 0;
    }

    /**
     * Get material cost
     */
    public static function getMaterialCost(string $code): float {
        $product = self::getProduct($code);
        if ($product) {
            return floatval($product['material_cost']);
        }
        return 0;
    }

    /**
     * Get description
     */
    public static function getDescription(string $code): string {
        $product = self::getProduct($code);
        if ($product) {
            return $product['description'];
        }
        return '';
    }

    /**
     * Get unit
     */
    public static function getUnit(string $code): string {
        $product = self::getProduct($code);
        if ($product) {
            return $product['unit'];
        }
        return '';
    }

    /**
     * Get ERP code
     */
    public static function getErpCode(string $code): string {
        $product = self::getProduct($code);
        if ($product) {
            return $product['erp_code'];
        }
        return '';
    }

    /**
     * Get phase number
     */
    public static function getPhaseNumber(string $code): string {
        $product = self::getProduct($code);
        if ($product) {
            return $product['phase_number'];
        }
        return '';
    }

    /**
     * Find purlin code by design index
     * Uses lookup table logic from Excel
     */
    public static function getPurlinCode(float $pdIndex, string $profile = 'Z'): string {
        // Purlin selection based on design index
        // This maps to the VLOOKUP in Sheet5
        $purlinTable = [
            ['maxIndex' => 50, 'code' => 'Z15P'],
            ['maxIndex' => 80, 'code' => 'Z20P'],
            ['maxIndex' => 140, 'code' => 'Z25P'],
            ['maxIndex' => 250, 'code' => 'Z30P'],
            ['maxIndex' => 400, 'code' => 'Z35P'],
            ['maxIndex' => 999999, 'code' => 'BUB'], // Built-up for large spans
        ];

        foreach ($purlinTable as $row) {
            if ($pdIndex <= $row['maxIndex']) {
                return $row['code'];
            }
        }

        return 'BUB';
    }

    /**
     * Find girt code by design index
     */
    public static function getGirtCode(float $pdIndex): string {
        // Girt selection based on design index
        $girtTable = [
            ['maxIndex' => 50, 'code' => 'Z15G'],
            ['maxIndex' => 80, 'code' => 'Z20G'],
            ['maxIndex' => 140, 'code' => 'Z25G'],
            ['maxIndex' => 250, 'code' => 'Z30G'],
            ['maxIndex' => 400, 'code' => 'Z35G'],
            ['maxIndex' => 999999, 'code' => 'BUB'],
        ];

        foreach ($girtTable as $row) {
            if ($pdIndex <= $row['maxIndex']) {
                return $row['code'];
            }
        }

        return 'BUB';
    }

    /**
     * Find endwall column code by design index
     */
    public static function getEWColumnCode(float $ewcIndex): string {
        // Endwall column selection
        $columnTable = [
            ['maxIndex' => 3, 'code' => 'IPEA'],
            ['maxIndex' => 8, 'code' => 'T150'],
            ['maxIndex' => 15, 'code' => 'T200'],
            ['maxIndex' => 999999, 'code' => 'BUC'],
        ];

        foreach ($columnTable as $row) {
            if ($ewcIndex <= $row['maxIndex']) {
                return $row['code'];
            }
        }

        return 'BUC';
    }

    /**
     * Find joist code by design index
     */
    public static function getJoistCode(float $jdIndex): string {
        $joistTable = [
            ['maxIndex' => 50, 'code' => 'Z15J'],
            ['maxIndex' => 80, 'code' => 'Z20J'],
            ['maxIndex' => 140, 'code' => 'Z25J'],
            ['maxIndex' => 250, 'code' => 'BUB'],
            ['maxIndex' => 999999, 'code' => 'BUB'],
        ];

        foreach ($joistTable as $row) {
            if ($jdIndex <= $row['maxIndex']) {
                return $row['code'];
            }
        }

        return 'BUB';
    }

    /**
     * Get all products matching a pattern
     */
    public static function searchProducts(string $pattern): array {
        self::buildIndex();
        $results = [];
        $pattern = strtolower($pattern);

        foreach (self::$productIndex as $code => $product) {
            if (strpos(strtolower($code), $pattern) !== false ||
                strpos(strtolower($product['description']), $pattern) !== false) {
                $results[] = $product;
            }
        }

        return $results;
    }
}
