<?php
/**
 * QuickEst - Database Configuration
 *
 * This file handles loading JSON data files that were exported from Excel
 */

class Database {
    private static $products = null;
    private static $structuralSteel = null;
    private static $lookups = null;

    private static $dataPath;

    public static function init($basePath = null) {
        self::$dataPath = $basePath ?? dirname(__DIR__) . '/data';
    }

    /**
     * Get all products from MBSDB
     */
    public static function getProducts(): array {
        if (self::$products === null) {
            self::$products = self::loadJson('products.json');
        }
        return self::$products;
    }

    /**
     * Get all structural steel items from SSDB
     */
    public static function getStructuralSteel(): array {
        if (self::$structuralSteel === null) {
            self::$structuralSteel = self::loadJson('structural_steel.json');
        }
        return self::$structuralSteel;
    }

    /**
     * Get lookup tables from DB sheet
     */
    public static function getLookups(): array {
        if (self::$lookups === null) {
            self::$lookups = self::loadJson('lookups.json');
        }
        return self::$lookups;
    }

    /**
     * Load JSON file
     */
    private static function loadJson(string $filename): array {
        $path = self::$dataPath . '/' . $filename;
        if (!file_exists($path)) {
            throw new Exception("Data file not found: {$filename}");
        }
        $content = file_get_contents($path);
        return json_decode($content, true) ?? [];
    }

    /**
     * Find product by code
     */
    public static function findProduct(string $code): ?array {
        $products = self::getProducts();
        foreach ($products as $product) {
            if (strcasecmp($product['code'], $code) === 0) {
                return $product;
            }
        }
        return null;
    }

    /**
     * Find structural steel item by code
     */
    public static function findStructuralSteel(string $code): ?array {
        $items = self::getStructuralSteel();
        foreach ($items as $item) {
            if (strcasecmp($item['code'], $code) === 0) {
                return $item;
            }
        }
        return null;
    }
}

// Initialize with default path
Database::init();
