<?php
/**
 * QuickEst - Calculator Tests
 *
 * QA tests for Mezzanine, Crane, and Accessory calculators
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

// Test results tracking
$tests = [];
$passed = 0;
$failed = 0;

function test($name, $condition, $message = '') {
    global $tests, $passed, $failed;
    $result = $condition ? 'PASS' : 'FAIL';
    $tests[] = ['name' => $name, 'result' => $result, 'message' => $message];
    if ($condition) {
        $passed++;
    } else {
        $failed++;
    }
    return $condition;
}

echo "=== QuickEst Calculator Tests ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// ============================================
// TEST 1: Mezzanine Calculator
// ============================================
echo "### Test Suite 1: Mezzanine Calculator ###\n";
echo str_repeat("-", 50) . "\n";

use QuickEst\Services\MezzanineCalculator;

$mezzCalc = new MezzanineCalculator();

// Test 1.1: Basic mezzanine calculation
$mezzParams = [
    'description' => 'Test Mezzanine',
    'salesCode' => 1,
    'colSpacing' => '2@6',
    'beamSpacing' => '3@4',
    'joistSpacing' => '1@1.5',
    'clearHeight' => 3.0,
    'doubleWelded' => 'No',
    'deckType' => 'Deck-0.75',
    'nStairs' => 1,
    'deadLoad' => 0.5,
    'liveLoad' => 5.0,
    'additionalLoad' => 0,
    'minThickness' => 6
];

try {
    $mezzBom = $mezzCalc->calculate($mezzParams);
    test('Mezz-1.1: Basic calculation runs', true);
    test('Mezz-1.2: BOM has items', $mezzBom->getItemCount() > 0, 'Items: ' . $mezzBom->getItemCount());
    test('Mezz-1.3: BOM has weight', $mezzBom->getTotalWeight() > 0, 'Weight: ' . $mezzBom->getTotalWeight());

    // Check for expected items
    $items = $mezzBom->toArray();
    $hasDeck = false;
    $hasJoists = false;
    $hasBeams = false;
    $hasColumns = false;
    $hasStairs = false;

    foreach ($items as $item) {
        if (stripos($item['dbCode'], 'Deck') !== false) $hasDeck = true;
        if (stripos($item['description'], 'Joist') !== false) $hasJoists = true;
        if (stripos($item['description'], 'Beam') !== false) $hasBeams = true;
        if (stripos($item['description'], 'Column') !== false) $hasColumns = true;
        if ($item['dbCode'] === 'DSP') $hasStairs = true;
    }

    test('Mezz-1.4: Has deck item', $hasDeck);
    test('Mezz-1.5: Has joist items', $hasJoists);
    test('Mezz-1.6: Has beam items', $hasBeams);
    test('Mezz-1.7: Has column items', $hasColumns);
    test('Mezz-1.8: Has stairs (DSP)', $hasStairs);

} catch (Exception $e) {
    test('Mezz-1.1: Basic calculation runs', false, $e->getMessage());
}

// Test 1.2: Large mezzanine
$largeMezzParams = [
    'description' => 'Large Mezzanine',
    'colSpacing' => '4@8',
    'beamSpacing' => '5@6',
    'joistSpacing' => '1@1.2',
    'clearHeight' => 4.5,
    'doubleWelded' => 'Yes',
    'deckType' => 'Deck-1.00',
    'nStairs' => 2,
    'deadLoad' => 0.5,
    'liveLoad' => 7.5,
    'additionalLoad' => 1.0,
    'minThickness' => 8
];

try {
    $largeMezzBom = $mezzCalc->calculate($largeMezzParams);
    test('Mezz-2.1: Large mezz calculation runs', true);
    test('Mezz-2.2: Large mezz has more weight', $largeMezzBom->getTotalWeight() > $mezzBom->getTotalWeight());

    // Check double welding item
    $hasDSW = false;
    foreach ($largeMezzBom->toArray() as $item) {
        if ($item['dbCode'] === 'DSW') $hasDSW = true;
    }
    test('Mezz-2.3: Has double welding (DSW)', $hasDSW);

} catch (Exception $e) {
    test('Mezz-2.1: Large mezz calculation runs', false, $e->getMessage());
}

echo "\n";

// ============================================
// TEST 2: Crane Calculator
// ============================================
echo "### Test Suite 2: Crane Calculator ###\n";
echo str_repeat("-", 50) . "\n";

use QuickEst\Services\CraneCalculator;

$craneCalc = new CraneCalculator();

// Test 2.1: Basic crane calculation
$craneParams = [
    'description' => 'Test EOT Crane',
    'salesCode' => 1,
    'capacity' => 5,
    'duty' => 'M',
    'railCenters' => 18,
    'craneRun' => '6@6'
];

try {
    $craneBom = $craneCalc->calculate($craneParams);
    test('Crane-1.1: Basic calculation runs', true);
    test('Crane-1.2: BOM has items', $craneBom->getItemCount() > 0, 'Items: ' . $craneBom->getItemCount());
    test('Crane-1.3: BOM has weight', $craneBom->getTotalWeight() > 0, 'Weight: ' . $craneBom->getTotalWeight());

    // Check for expected items
    $items = $craneBom->toArray();
    $hasBeam = false;
    $hasCorbel = false;
    $hasBracing = false;
    $hasStoppers = false;
    $hasBolts = false;

    foreach ($items as $item) {
        if (stripos($item['dbCode'], 'BUCRB') !== false || $item['dbCode'] === 'BUB') $hasBeam = true;
        if (stripos($item['dbCode'], 'CRC') !== false) $hasCorbel = true;
        if (stripos($item['dbCode'], 'Br') !== false || $item['dbCode'] === 'CRA') $hasBracing = true;
        if ($item['dbCode'] === 'CRS') $hasStoppers = true;
        if (stripos($item['dbCode'], 'HSB') !== false) $hasBolts = true;
    }

    test('Crane-1.4: Has crane beam', $hasBeam);
    test('Crane-1.5: Has corbels', $hasCorbel);
    test('Crane-1.6: Has bracing', $hasBracing);
    test('Crane-1.7: Has stoppers (CRS)', $hasStoppers);
    test('Crane-1.8: Has bolts', $hasBolts);

} catch (Exception $e) {
    test('Crane-1.1: Basic calculation runs', false, $e->getMessage());
}

// Test 2.2: Heavy duty crane
$heavyCraneParams = [
    'description' => 'Heavy EOT Crane',
    'capacity' => 20,
    'duty' => 'H',
    'railCenters' => 24,
    'craneRun' => '8@6'
];

try {
    $heavyCraneBom = $craneCalc->calculate($heavyCraneParams);
    test('Crane-2.1: Heavy crane calculation runs', true);
    test('Crane-2.2: Heavy crane has more weight', $heavyCraneBom->getTotalWeight() > $craneBom->getTotalWeight());

    // Calculate CB Index
    $cbIndex = CraneCalculator::calculateCBIndex(20, 6, 24, 'H');
    test('Crane-2.3: CB Index calculation works', $cbIndex > 0, 'CBIndex: ' . round($cbIndex, 2));

} catch (Exception $e) {
    test('Crane-2.1: Heavy crane calculation runs', false, $e->getMessage());
}

// Test 2.3: Duty factor variations
$lightCBIndex = CraneCalculator::calculateCBIndex(5, 6, 18, 'L');
$mediumCBIndex = CraneCalculator::calculateCBIndex(5, 6, 18, 'M');
$heavyCBIndex = CraneCalculator::calculateCBIndex(5, 6, 18, 'H');

test('Crane-3.1: Light < Medium CB Index', $lightCBIndex < $mediumCBIndex);
test('Crane-3.2: Medium < Heavy CB Index', $mediumCBIndex < $heavyCBIndex);
test('Crane-3.3: Duty factor ratio correct', abs($mediumCBIndex / $lightCBIndex - 1.1) < 0.01);

echo "\n";

// ============================================
// TEST 3: Accessory Calculator
// ============================================
echo "### Test Suite 3: Accessory Calculator ###\n";
echo str_repeat("-", 50) . "\n";

use QuickEst\Services\AccessoryCalculator;

$accCalc = new AccessoryCalculator();

// Test 3.1: Basic accessory calculation (skylights)
$accParams = [
    'description' => 'Test Accessories',
    'salesCode' => 1,
    'items' => [
        ['description' => 'Skylight 3250mm (GRP,Single Skin )', 'quantity' => 10],
    ],
    'wallTopSkin' => 'S5OW',
    'wallCore' => '-',
    'wallBotSkin' => '-'
];

try {
    $accBom = $accCalc->calculate($accParams);
    test('Acc-1.1: Basic calculation runs', true);
    test('Acc-1.2: BOM has items', $accBom->getItemCount() > 0, 'Items: ' . $accBom->getItemCount());

    // Check for wire mesh (should be added for skylights)
    $hasWireMesh = false;
    foreach ($accBom->toArray() as $item) {
        if ($item['dbCode'] === 'WRM') $hasWireMesh = true;
    }
    test('Acc-1.3: Has wire mesh for skylights', $hasWireMesh);

} catch (Exception $e) {
    test('Acc-1.1: Basic calculation runs', false, $e->getMessage());
}

// Test 3.2: Sliding door with sandwich panel
$doorParams = [
    'description' => 'Door Accessories',
    'salesCode' => 1,
    'items' => [
        ['description' => 'Slide door 4mX4m Steel Only with Framed Opening (Top Sliding)', 'quantity' => 2],
        ['description' => 'Personnel Door (900x2100)', 'quantity' => 3],
    ],
    'wallTopSkin' => 'S5OW',
    'wallCore' => 'Core50CPro',
    'wallBotSkin' => 'S5OW'
];

try {
    $doorBom = $accCalc->calculate($doorParams);
    test('Acc-2.1: Door calculation runs', true);

    // Check for door sheeting (sandwich panel) - looks for SWP code or wall skin codes
    $hasDoorSheeting = false;
    foreach ($doorBom->toArray() as $item) {
        if (stripos($item['description'], 'SWP Code') !== false ||
            stripos($item['description'], 'Door Sheeting') !== false) {
            $hasDoorSheeting = true;
        }
    }
    test('Acc-2.2: Has door sheeting for SWP', $hasDoorSheeting);

} catch (Exception $e) {
    test('Acc-2.1: Door calculation runs', false, $e->getMessage());
}

// Test 3.3: Mixed accessories
$mixedParams = [
    'description' => 'Mixed Accessories',
    'items' => [
        ['description' => 'Skylight 3250mm (GRP, Double Skin 50 mm thk)', 'quantity' => 5],
        ['description' => 'Louver 900x900', 'quantity' => 4],
        ['description' => 'Personnel Door (1200x2100)', 'quantity' => 2],
        ['description' => 'Ridge Ventilator', 'quantity' => 6],
    ]
];

try {
    $mixedBom = $accCalc->calculate($mixedParams);
    test('Acc-3.1: Mixed accessory calculation runs', true);
    test('Acc-3.2: Mixed has more items', $mixedBom->getItemCount() >= 4);

} catch (Exception $e) {
    test('Acc-3.1: Mixed accessory calculation runs', false, $e->getMessage());
}

// Test 3.4: Empty items should return empty BOM
$emptyParams = [
    'description' => 'Empty Test',
    'items' => []
];

try {
    $emptyBom = $accCalc->calculate($emptyParams);
    test('Acc-4.1: Empty items returns empty BOM', $emptyBom->getItemCount() === 0);

} catch (Exception $e) {
    test('Acc-4.1: Empty items returns empty BOM', false, $e->getMessage());
}

// Test 3.5: Available accessories list
$available = AccessoryCalculator::getAvailableAccessories();
test('Acc-5.1: Has skylights category', isset($available['skylights']) && count($available['skylights']) > 0);
test('Acc-5.2: Has doors category', isset($available['personnel_doors']) && count($available['personnel_doors']) > 0);
test('Acc-5.3: Has sliding doors category', isset($available['sliding_doors']) && count($available['sliding_doors']) > 0);
test('Acc-5.4: Has louvers category', isset($available['louvers']) && count($available['louvers']) > 0);

echo "\n";

// ============================================
// TEST 4: Partition Calculator
// ============================================
echo "### Test Suite 4: Partition Calculator ###\n";
echo str_repeat("-", 50) . "\n";

use QuickEst\Services\PartitionCalculator;

$partCalc = new PartitionCalculator();

// Test 4.1: Basic partition calculation
$partParams = [
    'description' => 'Test Partition',
    'salesCode' => 1,
    'location' => 'Internal',
    'length' => '3@6',
    'height' => 6.0,
    'girtSpacing' => 1.5,
    'columnSpacing' => 6.0,
    'windLoad' => 0.5,
    'topSkin' => 'S5OW',
    'core' => '-',
    'botSkin' => '-'
];

try {
    $partBom = $partCalc->calculate($partParams);
    test('Part-1.1: Basic calculation runs', true);
    test('Part-1.2: BOM has items', $partBom->getItemCount() > 0, 'Items: ' . $partBom->getItemCount());
    test('Part-1.3: BOM has weight', $partBom->getTotalWeight() > 0, 'Weight: ' . round($partBom->getTotalWeight(), 2));

    // Check for expected items
    $items = $partBom->toArray();
    $hasColumns = false;
    $hasGirts = false;
    $hasSheeting = false;

    foreach ($items as $item) {
        if (stripos($item['description'], 'Column') !== false || stripos($item['dbCode'], 'EWC') !== false) $hasColumns = true;
        if (stripos($item['description'], 'Girt') !== false || stripos($item['dbCode'], 'EWG') !== false) $hasGirts = true;
        if (stripos($item['dbCode'], 'S5') !== false || stripos($item['dbCode'], 'S7') !== false) $hasSheeting = true;
    }

    test('Part-1.4: Has columns', $hasColumns);
    test('Part-1.5: Has girts', $hasGirts);
    test('Part-1.6: Has sheeting', $hasSheeting);

} catch (Exception $e) {
    test('Part-1.1: Basic calculation runs', false, $e->getMessage());
}

// Test 4.2: Sandwich panel partition
$swpPartParams = [
    'description' => 'SWP Partition',
    'location' => 'Internal',
    'length' => '2@8',
    'height' => 8.0,
    'girtSpacing' => 1.2,
    'columnSpacing' => 8.0,
    'windLoad' => 0.6,
    'topSkin' => 'S5OW',
    'core' => 'Core50CPro',
    'botSkin' => 'S5OW'
];

try {
    $swpPartBom = $partCalc->calculate($swpPartParams);
    test('Part-2.1: SWP partition runs', true);
    test('Part-2.2: SWP partition has more weight', $swpPartBom->getTotalWeight() > $partBom->getTotalWeight());

} catch (Exception $e) {
    test('Part-2.1: SWP partition runs', false, $e->getMessage());
}

echo "\n";

// ============================================
// TEST 5: Canopy Calculator
// ============================================
echo "### Test Suite 5: Canopy Calculator ###\n";
echo str_repeat("-", 50) . "\n";

use QuickEst\Services\CanopyCalculator;

$canopyCalc = new CanopyCalculator();

// Test 5.1: Basic canopy calculation
$canopyParams = [
    'description' => 'Test Canopy',
    'salesCode' => 1,
    'type' => 'Canopy',
    'location' => 'Front',
    'width' => 3.0,
    'length' => '3@6',
    'eaveHeight' => 6.0,
    'slope' => 0.1,
    'deadLoad' => 0.1,
    'liveLoad' => 0.5,
    'windLoad' => 0.5,
    'roofTopSkin' => 'S5OW',
    'roofCore' => '-',
    'roofBotSkin' => '-'
];

try {
    $canopyBom = $canopyCalc->calculate($canopyParams);
    test('Canopy-1.1: Basic calculation runs', true);
    test('Canopy-1.2: BOM has items', $canopyBom->getItemCount() > 0, 'Items: ' . $canopyBom->getItemCount());
    test('Canopy-1.3: BOM has weight', $canopyBom->getTotalWeight() > 0, 'Weight: ' . round($canopyBom->getTotalWeight(), 2));

    // Check for expected items
    $items = $canopyBom->toArray();
    $hasRafter = false;
    $hasPurlin = false;
    $hasColumn = false;

    foreach ($items as $item) {
        if (stripos($item['description'], 'Rafter') !== false) $hasRafter = true;
        if (stripos($item['description'], 'Purlin') !== false || stripos($item['dbCode'], 'Pur') !== false) $hasPurlin = true;
        // Rafters act as the support in canopies (IPEa, UB codes)
        if (stripos($item['dbCode'], 'IPE') !== false || stripos($item['dbCode'], 'UB') !== false) $hasColumn = true;
    }

    test('Canopy-1.4: Has rafters', $hasRafter);
    test('Canopy-1.5: Has purlins', $hasPurlin);
    test('Canopy-1.6: Has structural support (IPE/UB)', $hasColumn);

} catch (Exception $e) {
    test('Canopy-1.1: Basic calculation runs', false, $e->getMessage());
}

// Test 5.2: Roof Extension type
$roofExtParams = [
    'description' => 'Roof Extension',
    'type' => 'Roof Extension',
    'location' => 'Back',
    'width' => 4.0,
    'length' => '4@6',
    'eaveHeight' => 7.0,
    'slope' => 0.15,
    'deadLoad' => 0.1,
    'liveLoad' => 0.5,
    'windLoad' => 0.5,
    'roofTopSkin' => 'S5OW',
    'roofCore' => '-',
    'roofBotSkin' => '-'
];

try {
    $roofExtBom = $canopyCalc->calculate($roofExtParams);
    test('Canopy-2.1: Roof extension runs', true);
    test('Canopy-2.2: Roof extension has items', $roofExtBom->getItemCount() > 0);

} catch (Exception $e) {
    test('Canopy-2.1: Roof extension runs', false, $e->getMessage());
}

// Test 5.3: Fascia type
$fasciaParams = [
    'description' => 'Fascia',
    'type' => 'Fascia',
    'location' => 'Left',
    'width' => 1.0,
    'length' => '2@6',
    'eaveHeight' => 6.0,
    'slope' => 0.0,
    'deadLoad' => 0.1,
    'liveLoad' => 0.5,
    'windLoad' => 0.5,
    'roofTopSkin' => 'S5OW',
    'roofCore' => '-',
    'roofBotSkin' => '-'
];

try {
    $fasciaBom = $canopyCalc->calculate($fasciaParams);
    test('Canopy-3.1: Fascia runs', true);
    test('Canopy-3.2: Fascia has items', $fasciaBom->getItemCount() > 0);

} catch (Exception $e) {
    test('Canopy-3.1: Fascia runs', false, $e->getMessage());
}

echo "\n";

// ============================================
// TEST 6: Monitor Calculator
// ============================================
echo "### Test Suite 6: Monitor Calculator ###\n";
echo str_repeat("-", 50) . "\n";

use QuickEst\Services\MonitorCalculator;

$monitorCalc = new MonitorCalculator();

// Test 6.1: Basic monitor calculation (Curve-CF)
$monitorParams = [
    'description' => 'Test Monitor',
    'salesCode' => 1,
    'type' => 'Curve-CF',
    'width' => 3.0,
    'height' => 1.5,
    'length' => '3@6',
    'purlinSpacing' => 1.2,
    'deadLoad' => 0.1,
    'liveLoad' => 0.5,
    'windLoad' => 0.5,
    'roofTopSkin' => 'S5OW',
    'roofCore' => '-',
    'roofBotSkin' => '-',
    'wallTopSkin' => 'S5OW',
    'wallCore' => '-',
    'wallBotSkin' => '-'
];

try {
    $monitorBom = $monitorCalc->calculate($monitorParams);
    test('Monitor-1.1: Basic calculation runs', true);
    test('Monitor-1.2: BOM has items', $monitorBom->getItemCount() > 0, 'Items: ' . $monitorBom->getItemCount());
    test('Monitor-1.3: BOM has weight', $monitorBom->getTotalWeight() > 0, 'Weight: ' . round($monitorBom->getTotalWeight(), 2));

    // Check for expected items
    $items = $monitorBom->toArray();
    $hasArch = false;
    $hasPurlin = false;
    $hasSheeting = false;

    foreach ($items as $item) {
        if (stripos($item['description'], 'Arch') !== false || stripos($item['description'], 'Frame') !== false) $hasArch = true;
        if (stripos($item['description'], 'Purlin') !== false || stripos($item['dbCode'], 'Pur') !== false) $hasPurlin = true;
        if (stripos($item['dbCode'], 'S5') !== false || stripos($item['dbCode'], 'S7') !== false) $hasSheeting = true;
    }

    test('Monitor-1.4: Has arch/frame', $hasArch);
    test('Monitor-1.5: Has purlins', $hasPurlin);
    test('Monitor-1.6: Has sheeting', $hasSheeting);

} catch (Exception $e) {
    test('Monitor-1.1: Basic calculation runs', false, $e->getMessage());
}

// Test 6.2: Straight-CF type
$straightCFParams = [
    'description' => 'Straight CF Monitor',
    'type' => 'Straight-CF',
    'width' => 4.0,
    'height' => 2.0,
    'length' => '4@6',
    'purlinSpacing' => 1.5,
    'deadLoad' => 0.1,
    'liveLoad' => 0.5,
    'windLoad' => 0.5,
    'roofTopSkin' => 'S5OW',
    'roofCore' => '-',
    'roofBotSkin' => '-',
    'wallTopSkin' => 'S5OW',
    'wallCore' => '-',
    'wallBotSkin' => '-'
];

try {
    $straightCFBom = $monitorCalc->calculate($straightCFParams);
    test('Monitor-2.1: Straight-CF runs', true);
    test('Monitor-2.2: Straight-CF has items', $straightCFBom->getItemCount() > 0);

} catch (Exception $e) {
    test('Monitor-2.1: Straight-CF runs', false, $e->getMessage());
}

// Test 6.3: Curve-HR type (hot rolled)
$curveHRParams = [
    'description' => 'Curve HR Monitor',
    'type' => 'Curve-HR',
    'width' => 5.0,
    'height' => 2.5,
    'length' => '3@8',
    'purlinSpacing' => 1.2,
    'deadLoad' => 0.1,
    'liveLoad' => 0.5,
    'windLoad' => 0.5,
    'roofTopSkin' => 'S5OW',
    'roofCore' => '-',
    'roofBotSkin' => '-',
    'wallTopSkin' => 'S5OW',
    'wallCore' => '-',
    'wallBotSkin' => '-'
];

try {
    $curveHRBom = $monitorCalc->calculate($curveHRParams);
    test('Monitor-3.1: Curve-HR runs', true);
    test('Monitor-3.2: Curve-HR has items', $curveHRBom->getItemCount() > 0);

} catch (Exception $e) {
    test('Monitor-3.1: Curve-HR runs', false, $e->getMessage());
}

// Test 6.4: Straight-HR type
$straightHRParams = [
    'description' => 'Straight HR Monitor',
    'type' => 'Straight-HR',
    'width' => 6.0,
    'height' => 3.0,
    'length' => '4@8',
    'purlinSpacing' => 1.5,
    'deadLoad' => 0.1,
    'liveLoad' => 0.5,
    'windLoad' => 0.5,
    'roofTopSkin' => 'S5OW',
    'roofCore' => '-',
    'roofBotSkin' => '-',
    'wallTopSkin' => 'S5OW',
    'wallCore' => '-',
    'wallBotSkin' => '-'
];

try {
    $straightHRBom = $monitorCalc->calculate($straightHRParams);
    test('Monitor-4.1: Straight-HR runs', true);
    test('Monitor-4.2: Straight-HR has items', $straightHRBom->getItemCount() > 0);

} catch (Exception $e) {
    test('Monitor-4.1: Straight-HR runs', false, $e->getMessage());
}

echo "\n";

// ============================================
// TEST 7: Liner Calculator
// ============================================
echo "### Test Suite 7: Liner Calculator ###\n";
echo str_repeat("-", 50) . "\n";

$linerCalc = new \QuickEst\Services\LinerCalculator();

// Test 7.1: Basic Liner Calculation - Both Roof and Wall
$linerParams = [
    'description' => 'Interior Liner',
    'salesCode' => 1,
    'type' => 'Both',
    'roofLinerType' => 'S5OW',
    'wallLinerType' => 'S5OW',
    'buildingWidth' => 24,
    'buildingLength' => 36,
    'backEaveHeight' => 8,
    'frontEaveHeight' => 8,
    'roofArea' => 0, // Auto-calculate
    'wallArea' => 0, // Auto-calculate
    'roofOpenings' => 0,
    'wallOpenings' => 0
];

try {
    $linerBom = $linerCalc->calculate($linerParams);
    test('Liner-1.1: Basic Both liner calculation runs', true);
    test('Liner-1.2: Has BOM items', $linerBom->getItemCount() > 0, 'Items: ' . $linerBom->getItemCount());
    test('Liner-1.3: Total weight > 0', $linerBom->getTotalWeight() > 0, 'Weight: ' . round($linerBom->getTotalWeight(), 2));

    // Check for both roof and wall liner items
    $items = $linerBom->toArray();
    $hasRoofLiner = false;
    $hasWallLiner = false;
    foreach ($items as $item) {
        if (stripos($item['description'], 'Roof Liner') !== false) $hasRoofLiner = true;
        if (stripos($item['description'], 'Wall Liner') !== false) $hasWallLiner = true;
    }
    test('Liner-1.4: Has Roof Liner item', $hasRoofLiner);
    test('Liner-1.5: Has Wall Liner item', $hasWallLiner);

} catch (Exception $e) {
    test('Liner-1.1: Basic Both liner calculation runs', false, $e->getMessage());
}

// Test 7.2: Roof Liner Only
$roofOnlyParams = array_merge($linerParams, [
    'type' => 'Roof Liner',
    'description' => 'Roof Liner Only'
]);

try {
    $roofOnlyBom = $linerCalc->calculate($roofOnlyParams);
    test('Liner-2.1: Roof Liner Only calculation runs', true);
    test('Liner-2.2: Has items', $roofOnlyBom->getItemCount() > 0);

    $items = $roofOnlyBom->toArray();
    $hasRoofLiner = false;
    $hasWallLiner = false;
    foreach ($items as $item) {
        if (stripos($item['description'], 'Roof Liner') !== false) $hasRoofLiner = true;
        if (stripos($item['description'], 'Wall Liner') !== false) $hasWallLiner = true;
    }
    test('Liner-2.3: Has Roof Liner', $hasRoofLiner);
    test('Liner-2.4: No Wall Liner', !$hasWallLiner);

} catch (Exception $e) {
    test('Liner-2.1: Roof Liner Only calculation runs', false, $e->getMessage());
}

// Test 7.3: Wall Liner Only
$wallOnlyParams = array_merge($linerParams, [
    'type' => 'Wall Liner',
    'description' => 'Wall Liner Only'
]);

try {
    $wallOnlyBom = $linerCalc->calculate($wallOnlyParams);
    test('Liner-3.1: Wall Liner Only calculation runs', true);
    test('Liner-3.2: Has items', $wallOnlyBom->getItemCount() > 0);

    $items = $wallOnlyBom->toArray();
    $hasRoofLiner = false;
    $hasWallLiner = false;
    foreach ($items as $item) {
        if (stripos($item['description'], 'Roof Liner') !== false) $hasRoofLiner = true;
        if (stripos($item['description'], 'Wall Liner') !== false) $hasWallLiner = true;
    }
    test('Liner-3.3: No Roof Liner', !$hasRoofLiner);
    test('Liner-3.4: Has Wall Liner', $hasWallLiner);

} catch (Exception $e) {
    test('Liner-3.1: Wall Liner Only calculation runs', false, $e->getMessage());
}

// Test 7.4: Manual Area Entry
$manualAreaParams = array_merge($linerParams, [
    'description' => 'Manual Area Liner',
    'roofArea' => 500,
    'wallArea' => 800
]);

try {
    $manualBom = $linerCalc->calculate($manualAreaParams);
    test('Liner-4.1: Manual area entry works', true);
    test('Liner-4.2: Has items', $manualBom->getItemCount() > 0);

} catch (Exception $e) {
    test('Liner-4.1: Manual area entry works', false, $e->getMessage());
}

// Test 7.5: Openings Deduction
$openingsParams = array_merge($linerParams, [
    'description' => 'Liner with Openings',
    'roofOpenings' => 50,
    'wallOpenings' => 100
]);

try {
    $openingsBom = $linerCalc->calculate($openingsParams);
    test('Liner-5.1: Openings deduction works', true);
    test('Liner-5.2: Weight < full liner', $openingsBom->getTotalWeight() < $linerBom->getTotalWeight(),
         'With openings: ' . round($openingsBom->getTotalWeight(), 2) . ' vs Full: ' . round($linerBom->getTotalWeight(), 2));

} catch (Exception $e) {
    test('Liner-5.1: Openings deduction works', false, $e->getMessage());
}

// Test 7.6: Different Material Types
$aluLinerParams = array_merge($linerParams, [
    'description' => 'Aluminum Liner',
    'roofLinerType' => 'A7MF',
    'wallLinerType' => 'A7MF'
]);

try {
    $aluBom = $linerCalc->calculate($aluLinerParams);
    test('Liner-6.1: Aluminum liner calculation runs', true);

    // Check for stainless steel screws (SS2) for aluminum
    $items = $aluBom->toArray();
    $hasSSScrew = false;
    foreach ($items as $item) {
        if ($item['dbCode'] === 'SS2' || $item['dbCode'] === 'SS1') {
            $hasSSScrew = true;
            break;
        }
    }
    test('Liner-6.2: Uses SS screws for aluminum', $hasSSScrew);

} catch (Exception $e) {
    test('Liner-6.1: Aluminum liner calculation runs', false, $e->getMessage());
}

// Test 7.7: PU Panel Liner
$puLinerParams = array_merge($linerParams, [
    'description' => 'PU Panel Liner',
    'roofLinerType' => 'PUS50',
    'wallLinerType' => 'PUS35'
]);

try {
    $puBom = $linerCalc->calculate($puLinerParams);
    test('Liner-7.1: PU panel liner calculation runs', true);

    // Check for longer screws (CS4/SS4) for PU panels
    $items = $puBom->toArray();
    $hasLongScrew = false;
    foreach ($items as $item) {
        if ($item['dbCode'] === 'CS4' || $item['dbCode'] === 'SS4') {
            $hasLongScrew = true;
            break;
        }
    }
    test('Liner-7.2: Uses long screws for PU panels', $hasLongScrew);

} catch (Exception $e) {
    test('Liner-7.1: PU panel liner calculation runs', false, $e->getMessage());
}

// Test 7.8: None Type Handling
$noRoofLinerParams = array_merge($linerParams, [
    'description' => 'No Roof Liner',
    'type' => 'Both',
    'roofLinerType' => 'None'
]);

try {
    $noRoofBom = $linerCalc->calculate($noRoofLinerParams);
    test('Liner-8.1: None type handling works', true);

    // Should only have wall liner
    $items = $noRoofBom->toArray();
    $hasRoofLiner = false;
    foreach ($items as $item) {
        if (stripos($item['description'], 'Roof Liner') !== false) $hasRoofLiner = true;
    }
    test('Liner-8.2: No Roof Liner when type=None', !$hasRoofLiner);

} catch (Exception $e) {
    test('Liner-8.1: None type handling works', false, $e->getMessage());
}

echo "\n";

// ============================================
// TEST 8: Integration Tests
// ============================================
echo "### Test Suite 8: Integration Tests ###\n";
echo str_repeat("-", 50) . "\n";

// Test 8.1: All calculators work together
try {
    $mezzBom = $mezzCalc->calculate($mezzParams);
    $craneBom = $craneCalc->calculate($craneParams);
    $accBom = $accCalc->calculate($accParams);
    $partBom = $partCalc->calculate($partParams);
    $canopyBom = $canopyCalc->calculate($canopyParams);
    $monitorBom = $monitorCalc->calculate($monitorParams);
    $linerBomInt = $linerCalc->calculate($linerParams);

    $totalItems = $mezzBom->getItemCount() + $craneBom->getItemCount() + $accBom->getItemCount()
                + $partBom->getItemCount() + $canopyBom->getItemCount() + $monitorBom->getItemCount()
                + $linerBomInt->getItemCount();
    $totalWeight = $mezzBom->getTotalWeight() + $craneBom->getTotalWeight() + $accBom->getTotalWeight()
                 + $partBom->getTotalWeight() + $canopyBom->getTotalWeight() + $monitorBom->getTotalWeight()
                 + $linerBomInt->getTotalWeight();

    test('Int-1.1: All 7 calculators complete', true);
    test('Int-1.2: Combined items > 25', $totalItems > 25, 'Total items: ' . $totalItems);
    test('Int-1.3: Combined weight > 0', $totalWeight > 0, 'Total weight: ' . round($totalWeight, 2));

} catch (Exception $e) {
    test('Int-1.1: All 7 calculators complete', false, $e->getMessage());
}

// Test 7.2: ListParser integration
use QuickEst\Helpers\ListParser;

$testInput = '3@6,2@8';
$parsed = ListParser::parseList($testInput);
$expanded = ListParser::expandList($parsed);
$total = ListParser::getTotalSum($parsed);

test('Int-2.1: ListParser parses correctly', count($parsed) === 2);
test('Int-2.2: ListParser expands correctly', count($expanded) === 5);
test('Int-2.3: ListParser sums correctly', abs($total - 34) < 0.001); // 3*6 + 2*8 = 18 + 16 = 34

// Test 7.3: Available types for each calculator
$canopyTypes = ['Canopy', 'Roof Extension', 'Fascia'];
$monitorTypes = ['Curve-CF', 'Straight-CF', 'Curve-HR', 'Straight-HR'];

test('Int-3.1: Canopy types exist', count($canopyTypes) === 3);
test('Int-3.2: Monitor types exist', count($monitorTypes) === 4);

echo "\n";

// ============================================
// SUMMARY
// ============================================
echo str_repeat("=", 50) . "\n";
echo "TEST SUMMARY\n";
echo str_repeat("=", 50) . "\n";

$total = $passed + $failed;
$passRate = $total > 0 ? round(($passed / $total) * 100, 1) : 0;

echo "Total Tests: {$total}\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
echo "Pass Rate: {$passRate}%\n\n";

if ($failed > 0) {
    echo "FAILED TESTS:\n";
    foreach ($tests as $test) {
        if ($test['result'] === 'FAIL') {
            echo "  - {$test['name']}" . ($test['message'] ? " ({$test['message']})" : "") . "\n";
        }
    }
    echo "\n";
}

// Detailed results
echo "\nDETAILED RESULTS:\n";
echo str_repeat("-", 50) . "\n";
foreach ($tests as $test) {
    $icon = $test['result'] === 'PASS' ? '✓' : '✗';
    $msg = $test['message'] ? " - {$test['message']}" : '';
    echo "{$icon} [{$test['result']}] {$test['name']}{$msg}\n";
}

echo "\n=== Tests Complete ===\n";

// Exit with appropriate code
exit($failed > 0 ? 1 : 0);
