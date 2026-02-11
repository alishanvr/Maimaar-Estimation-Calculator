<?php
/**
 * QuickEst - Test Calculation Script
 *
 * Simple test to verify the calculation engine works
 */

// Set up paths
define('BASE_PATH', dirname(__DIR__));

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'QuickEst\\';
    $baseDir = BASE_PATH . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Load database
require_once BASE_PATH . '/config/database.php';
Database::init(BASE_PATH . '/data');

echo "=== QuickEst PHP Calculation Test ===\n\n";

// Test 1: ListParser
echo "Test 1: ListParser\n";
echo "-------------------\n";

use QuickEst\Helpers\ListParser;

$spansText = "2@24";
$parsed = ListParser::parseList($spansText);
$expanded = ListParser::expandList($parsed);
$total = ListParser::getTotalSum($parsed);

echo "Input: '{$spansText}'\n";
echo "Parsed: " . json_encode($parsed) . "\n";
echo "Expanded: " . json_encode($expanded) . "\n";
echo "Total: {$total}m\n\n";

$baysText = "6@6";
$parsed = ListParser::parseList($baysText);
$total = ListParser::getTotalSum($parsed);
echo "Bays Input: '{$baysText}'\n";
echo "Total: {$total}m\n\n";

// Test 2: Database Lookup
echo "Test 2: Database Lookup\n";
echo "-----------------------\n";

use QuickEst\Database\ProductLookup;

$codes = ['BU', 'Z20P', 'S5OW', 'HSB12'];
foreach ($codes as $code) {
    $product = ProductLookup::getProduct($code);
    if ($product) {
        echo "{$code}: {$product['description']} - Weight: {$product['weight']} {$product['unit']}\n";
    } else {
        echo "{$code}: NOT FOUND\n";
    }
}
echo "\n";

// Test 3: Building Model
echo "Test 3: Building Model\n";
echo "----------------------\n";

use QuickEst\Models\Building;

$building = Building::fromArray([
    'projectName' => 'Test Project',
    'buildingName' => 'Warehouse',
    'spans' => '1@24',
    'bays' => '6@6',
    'backEaveHeight' => 8,
    'frontEaveHeight' => 8,
    'windSpeed' => 130,
    'deadLoad' => 0.1,
    'liveLoadPurlin' => 0.57,
    'liveLoadFrame' => 0.57,
    'roofTopSkin' => 'S5OW',
    'wallTopSkin' => 'S5OW',
    'bracingType' => 'Cables',
    'baseType' => 'Pinned Base',
    'leftEndwallType' => 'Bearing Frame',
    'rightEndwallType' => 'Bearing Frame',
]);

$errors = $building->validate();
if (empty($errors)) {
    echo "Building validation: PASSED\n";
    echo "Wind Load: " . $building->getWindLoad() . " kN/m2\n";
    echo "Total Purlin Load: " . $building->getTotalPurlinLoad() . " kN/m2\n";
} else {
    echo "Validation errors: " . implode(', ', $errors) . "\n";
}
echo "\n";

// Test 4: Full Calculation
echo "Test 4: Full Calculation\n";
echo "------------------------\n";

use QuickEst\Services\CalculationEngine;

$engine = new CalculationEngine();
$bom = $engine->calculate($building);

$dims = $engine->getDimensions();
echo "Building Dimensions:\n";
echo "  Width: {$dims['width']}m\n";
echo "  Length: {$dims['length']}m\n";
echo "  Eave Height: {$dims['backEaveHeight']}m\n";
echo "  Peak Height: {$dims['peakHeight']}m\n";
echo "  Rafter Length: {$dims['rafterLength']}m\n";
echo "  No. of Frames: {$dims['nFrames']}\n";
echo "  No. of Spans: {$dims['nSpans']}\n";
echo "  No. of Bays: {$dims['nBays']}\n\n";

echo "Bill of Materials Summary:\n";
echo "  Total Items: " . $bom->getItemCount() . "\n";
echo "  Total Weight: " . number_format($bom->getTotalWeight(), 2) . " kg\n";
echo "  Total Price: " . number_format($bom->getTotalPrice(), 2) . " AED\n\n";

// Show first 20 items
echo "First 20 BOM Items:\n";
echo str_repeat("-", 100) . "\n";
echo sprintf("%-5s %-10s %-40s %10s %10s %12s\n",
    "No.", "Code", "Description", "Qty", "Weight", "Price");
echo str_repeat("-", 100) . "\n";

$count = 0;
foreach ($bom->items as $item) {
    if ($count >= 20) break;

    if ($item->isHeader) {
        echo "\n** {$item->description} **\n";
    } elseif (!$item->isSeparator) {
        echo sprintf("%-5d %-10s %-40s %10.2f %10.2f %12.2f\n",
            $item->lineNumber,
            $item->dbCode,
            substr($item->description, 0, 40),
            $item->quantity,
            $item->totalWeight,
            $item->totalPrice
        );
    }
    $count++;
}

echo "\n\n=== Test Complete ===\n";
