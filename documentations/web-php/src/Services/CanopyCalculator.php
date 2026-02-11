<?php
/**
 * QuickEst - Canopy Calculator
 *
 * Calculates canopy, roof extension, and fascia materials
 * Replicates VBA AddCanopy_Click() procedure
 */

namespace QuickEst\Services;

use QuickEst\Models\BillOfMaterials;
use QuickEst\Helpers\ListParser;
use QuickEst\Database\ProductLookup;

class CanopyCalculator {

    /**
     * Calculate canopy materials
     *
     * @param array $params Canopy parameters:
     *   - description: string
     *   - salesCode: int
     *   - type: string (Canopy/Roof Extension/Fascia)
     *   - location: string (Back Sidewall/Front Sidewall/Left Endwall/Right Endwall)
     *   - height: float (m) - column/post height
     *   - width: float (m) - canopy projection width
     *   - colSpacing: string (e.g., "6@6")
     *   - roofSheeting: string
     *   - drainage: string (Eave Trim/Gutter+Dwnspts)
     *   - soffit: string (optional soffit sheeting)
     *   - wallSheeting: string (for fascia)
     *   - liveLoad: float (kN/m2)
     *   - windSpeed: float (km/h)
     *   - buFinish: string
     *   - cfFinish: string
     */
    public function calculate(array $params): BillOfMaterials {
        $bom = new BillOfMaterials();

        // Extract parameters
        $description = $params['description'] ?? 'Canopy';
        $salesCode = intval($params['salesCode'] ?? 1);
        $type = $params['type'] ?? 'Canopy';
        $location = $params['location'] ?? 'Front Sidewall';
        $height = floatval($params['height'] ?? 3);
        $width = floatval($params['width'] ?? 3);
        $colSpacingStr = $params['colSpacing'] ?? '6@6';
        $roofSheeting = $params['roofSheeting'] ?? 'S5OW';
        $drainage = $params['drainage'] ?? 'Gutter+Dwnspts';
        $soffit = $params['soffit'] ?? 'None';
        $wallSheeting = $params['wallSheeting'] ?? 'None';
        $liveLoad = floatval($params['liveLoad'] ?? 0.57);
        $windSpeed = floatval($params['windSpeed'] ?? 130);
        $cfFinish = $params['cfFinish'] ?? 'Galvanized';

        // Parse column spacing
        $spacing = ListParser::parseList($colSpacingStr);
        $expandedSpacing = ListParser::expandList($spacing);

        // Calculate total length
        $totalLength = ListParser::getTotalSum($spacing);
        $numBays = count($expandedSpacing);
        $totalPosts = ListParser::getTotalCount($spacing);

        // Wind load
        $windLoad = pow($windSpeed, 2) / 20000;

        // Total load
        $totalLoad = $liveLoad + 0.15; // Dead load ~0.15
        if ($windLoad > $totalLoad) {
            $totalLoad = $windLoad;
        }

        // Determine calculation type
        $typeFirstChar = strtoupper(substr($type, 0, 1));

        if ($typeFirstChar === 'R' && $width <= 1.5) {
            // Roof Extension
            return $this->calculateRoofExtension($bom, $params, $spacing, $totalLength, $numBays, $totalLoad, $salesCode);
        } elseif ($typeFirstChar === 'C') {
            // Canopy
            return $this->calculateCanopy($bom, $params, $spacing, $expandedSpacing, $totalLength, $numBays, $totalPosts, $totalLoad, $salesCode);
        } elseif ($typeFirstChar === 'F') {
            // Fascia
            return $this->calculateFascia($bom, $params, $spacing, $expandedSpacing, $totalLength, $numBays, $totalPosts, $windSpeed, $salesCode);
        }

        // Default to canopy
        return $this->calculateCanopy($bom, $params, $spacing, $expandedSpacing, $totalLength, $numBays, $totalPosts, $totalLoad, $salesCode);
    }

    /**
     * Calculate roof extension materials
     */
    private function calculateRoofExtension(
        BillOfMaterials $bom,
        array $params,
        array $spacing,
        float $totalLength,
        int $numBays,
        float $totalLoad,
        int $salesCode
    ): BillOfMaterials {
        $location = $params['location'] ?? 'Front Sidewall';
        $width = floatval($params['width'] ?? 1.2);
        $height = floatval($params['height'] ?? 3);
        $roofSheeting = $params['roofSheeting'] ?? 'S5OW';
        $soffit = $params['soffit'] ?? 'None';
        $drainage = $params['drainage'] ?? 'Eave Trim';

        $fullDesc = "Roof Extension: {$location}, Length={$totalLength}m, Width={$width}m";
        $bom->addCode($fullDesc, '-', $salesCode, '', '');

        $purlinBolts = 0;
        $purlinLines = 1;

        if ($location === 'Back Sidewall' || $location === 'Front Sidewall') {
            // Sidewall Roof Extension
            $bom->addCode('', 'IPEa', $salesCode, 0.2 + $width, ListParser::getTotalCount($spacing) + 1);
            $bom->addCode('', 'MFC1', $salesCode, 1, 2 * ListParser::getTotalCount($spacing) + 4);
            $bom->addCode('', 'HSB16', $salesCode, 1, 8 * ListParser::getTotalCount($spacing) + 6);

            // Purlins for end bays and interior
            foreach ($spacing as $sp) {
                $bayWidth = $sp['value'];
                $pdIndex = 0.65 * $totalLoad * pow($bayWidth, 2);
                $pdCode = ProductLookup::getPurlinCode($pdIndex);

                $size = $bayWidth + 0.107;
                if ($bayWidth > 6.5) $size += 0.599;
                if ($bayWidth > 9) $size += 0.706;

                $qty = $sp['count'] * $purlinLines;
                $bom->addCode('Purlins', $pdCode, $salesCode, $size, $qty);
                $purlinBolts += $qty * 2;
            }
        } else {
            // Endwall Roof Extension
            $purlinLines = intval($totalLength / 1.5) + 1;
            $pdIndex = $totalLoad * pow($width, 2);
            $pdCode = ProductLookup::getPurlinCode($pdIndex);
            $size = $width + 0.706;

            $bom->addCode('Purlins', $pdCode, $salesCode, $size, $purlinLines);
            $bom->addCode('', 'Gang', $salesCode, 1, $totalLength);
            $purlinBolts = 6 * $purlinLines;
        }

        $bom->addCode('', 'HSB12', $salesCode, 1, $purlinBolts);

        // Sheeting
        $this->addSheetingAndTrims($bom, $salesCode, $roofSheeting, $soffit, $drainage, $width, $totalLength, $height, $purlinLines);

        $bom->addCode('', '-', $salesCode, '', '');
        return $bom;
    }

    /**
     * Calculate canopy materials
     */
    private function calculateCanopy(
        BillOfMaterials $bom,
        array $params,
        array $spacing,
        array $expandedSpacing,
        float $totalLength,
        int $numBays,
        int $totalPosts,
        float $totalLoad,
        int $salesCode
    ): BillOfMaterials {
        $location = $params['location'] ?? 'Front Sidewall';
        $width = floatval($params['width'] ?? 3);
        $height = floatval($params['height'] ?? 3);
        $roofSheeting = $params['roofSheeting'] ?? 'S5OW';
        $soffit = $params['soffit'] ?? 'None';
        $drainage = $params['drainage'] ?? 'Gutter+Dwnspts';

        $fullDesc = "Canopy: {$location}, Length={$totalLength}m, Height={$height}m, Width={$width}m";
        $bom->addCode($fullDesc, '-', $salesCode, '', '');

        // Calculate rafter/post codes based on load
        $postCodes = [];
        $postQtys = [];

        foreach ($expandedSpacing as $index => $bayWidth) {
            $postIndex = $totalLoad * pow($width, 2) * $bayWidth;

            $code = 'IPEa';
            if ($postIndex > 49) $code = 'UB2';
            if ($postIndex > 60) $code = 'UB3';
            if ($postIndex > 100) $code = 'UB4';

            $postCodes[$index] = $code;
        }

        // Group posts by code
        $groupedPosts = [];
        foreach ($postCodes as $code) {
            if (!isset($groupedPosts[$code])) {
                $groupedPosts[$code] = 0;
            }
            $groupedPosts[$code]++;
        }

        // Add rafters
        $rafterDesc = "Rafters";
        foreach ($groupedPosts as $code => $qty) {
            $size = $height + $width + 0.2;
            $bom->addCode($rafterDesc, $code, $salesCode, $size, $qty);
            $rafterDesc = '';
        }

        // Connections
        $bom->addCode('', 'MFC1', $salesCode, 1, $totalPosts);
        $bom->addCode('', 'HSB16', $salesCode, 1, 8 * $totalPosts);

        // Purlins
        $purlinLines = intval($width / 1.5) + 1;
        $purlinBolts = 0;

        // End bay purlins
        $firstBay = $expandedSpacing[0];
        $pdIndex = 1.25 * $totalLoad * pow($firstBay, 2);
        $pdCode = ProductLookup::getPurlinCode($pdIndex);

        $size = $firstBay + 0.107;
        if ($firstBay > 6.5) $size += 0.599;
        if ($firstBay > 9) $size += 0.706;

        $qty = $purlinLines;
        if (count($expandedSpacing) == 1 && $totalPosts > 1) {
            $qty = 2 * $purlinLines;
        }

        $bom->addCode('End Bay Purlins', $pdCode, $salesCode, $size, $qty);
        $purlinBolts += $qty * 2;

        // Interior bay purlins
        if (count($expandedSpacing) > 1) {
            $intPurlinDesc = "Interior Bay Purlins";
            for ($i = 1; $i < count($expandedSpacing) - 1; $i++) {
                $bayWidth = $expandedSpacing[$i];
                $pdIndex = $totalLoad * pow($bayWidth, 2);
                $pdCode = ProductLookup::getPurlinCode($pdIndex);

                $size = $bayWidth + 0.107;
                if ($bayWidth > 6.5) $size += 0.599;
                if ($bayWidth > 9) $size += 0.706;

                $bom->addCode($intPurlinDesc, $pdCode, $salesCode, $size, $purlinLines);
                $purlinBolts += $purlinLines * 8;
                $intPurlinDesc = '';
            }

            // Last bay
            $lastBay = $expandedSpacing[count($expandedSpacing) - 1];
            $pdIndex = 1.25 * $totalLoad * pow($lastBay, 2);
            $pdCode = ProductLookup::getPurlinCode($pdIndex);

            $size = $lastBay + 0.107;
            if ($lastBay > 6.5) $size += 0.599;
            if ($lastBay > 9) $size += 0.706;

            $bom->addCode('', $pdCode, $salesCode, $size, $purlinLines);
            $purlinBolts += $purlinLines * 2;
        }

        $bom->addCode('', 'HSB12', $salesCode, 1, $purlinBolts);

        // Sheeting
        $this->addSheetingAndTrims($bom, $salesCode, $roofSheeting, $soffit, $drainage, $width, $totalLength, $height, $purlinLines);

        $bom->addCode('', '-', $salesCode, '', '');
        return $bom;
    }

    /**
     * Calculate fascia materials
     */
    private function calculateFascia(
        BillOfMaterials $bom,
        array $params,
        array $spacing,
        array $expandedSpacing,
        float $totalLength,
        int $numBays,
        int $totalPosts,
        float $windSpeed,
        int $salesCode
    ): BillOfMaterials {
        $location = $params['location'] ?? 'Front Sidewall';
        $width = floatval($params['width'] ?? 1);
        $height = floatval($params['height'] ?? 2);
        $wallSheeting = $params['wallSheeting'] ?? 'S5OW';

        $fullDesc = "Fascia: {$location}, Length={$totalLength}m, Height={$height}m";
        $bom->addCode($fullDesc, '-', $salesCode, '', '');

        // Calculate post codes based on wind load
        $postCodes = [];
        foreach ($expandedSpacing as $index => $bayWidth) {
            $postIndex = $windSpeed * ($height + $width) * $bayWidth;

            $code = 'IPEa';
            if ($postIndex > 2500) $code = 'UB2';
            if ($postIndex > 3500) $code = 'UB2';
            if ($postIndex > 6000) $code = 'UB3';

            $postCodes[$index] = $code;
        }

        // Group posts by code
        $groupedPosts = [];
        foreach ($postCodes as $code) {
            if (!isset($groupedPosts[$code])) {
                $groupedPosts[$code] = 0;
            }
            $groupedPosts[$code]++;
        }

        // Add posts
        $postDesc = "Posts";
        foreach ($groupedPosts as $code => $qty) {
            $size = $height + $width + 0.2;
            $bom->addCode($postDesc, $code, $salesCode, $size, $qty);
            $postDesc = '';
        }

        // Connections
        $bom->addCode('', 'MFC1', $salesCode, 1, $totalPosts);
        $bom->addCode('', 'HSB16', $salesCode, 1, 8 * $totalPosts);

        // Girts
        $girtLines = intval(($height + $width) / 1.7) + 1;
        if ($height <= 1.2) {
            $girtLines = 3;
        }

        $girtClips = 0;
        $girtBolts = 0;
        $girtDesc = "Fascia Girts";

        foreach ($spacing as $sp) {
            $bayWidth = $sp['value'];
            $windLoad = pow($windSpeed, 2) / 20000;
            $pdIndex = 2 * $windLoad * pow($bayWidth, 2);
            $pdCode = ProductLookup::getGirtCode($pdIndex);

            $qty = $sp['count'] * $girtLines;
            if ($qty > 0) {
                $bom->addCode($girtDesc, $pdCode, $salesCode, $bayWidth, $qty);
                $girtClips += 2 * $qty;
                $girtBolts += $qty * 8;
                $girtDesc = '';
            }
        }

        $bom->addCode('', 'HSB12', $salesCode, 1, $girtBolts);
        if ($girtClips > 0) {
            $bom->addCode('', 'CFClip', $salesCode, 1, $girtClips);
        }

        // Wall sheeting
        if ($wallSheeting !== 'None' && !empty($wallSheeting)) {
            $wallCode = ProductLookup::codeOf($wallSheeting) ?: $wallSheeting;
            $wallArea = $totalLength * ($height + $width);
            $bom->addCode('Fascia Sheeting', $wallCode, $salesCode, 1, $wallArea);

            // Trims
            $bom->addCode('', 'TTS1', $salesCode, 1, 2 * $totalLength + 4 * ($height + $width));

            // Fasteners
            $fasteners = 4 * $wallArea;
            $screwCode = (stripos($wallSheeting, 'A') !== false) ? 'SS2' : 'CS2';
            $bom->addCode('', $screwCode, $salesCode, 1, $fasteners);
        }

        $bom->addCode('', '-', $salesCode, '', '');
        return $bom;
    }

    /**
     * Add sheeting and trims
     */
    private function addSheetingAndTrims(
        BillOfMaterials $bom,
        int $salesCode,
        string $roofSheeting,
        string $soffit,
        string $drainage,
        float $width,
        float $totalLength,
        float $height,
        int $purlinLines
    ): void {
        $sheetingDesc = "Sheeting & Trims";
        $fastenerQty = 0;

        // Roof sheeting
        if ($roofSheeting !== 'None' && !empty($roofSheeting)) {
            $roofCode = ProductLookup::codeOf($roofSheeting) ?: $roofSheeting;
            $roofArea = $width * $totalLength;
            $bom->addCode($sheetingDesc, $roofCode, $salesCode, 1, $roofArea);
            $sheetingDesc = '';
            $fastenerQty += ($purlinLines * 3 + 3) * $totalLength;
        }

        // Drainage
        if ($drainage === 'Eave Trim') {
            $bom->addCode('', 'ETS1', $salesCode, 1, $totalLength);
        } elseif ($drainage === 'Gutter+Dwnspts') {
            $bom->addCode('', 'EGS1', $salesCode, 1, $totalLength);
            $numDownspouts = intval($totalLength / 12) + 1;
            $bom->addCode('', 'DSS1', $salesCode, $height, $numDownspouts);
        }

        // Soffit
        if ($soffit !== 'None' && !empty($soffit)) {
            $soffitCode = ProductLookup::codeOf($soffit) ?: $soffit;
            $soffitArea = $width * $totalLength;
            $bom->addCode($sheetingDesc, $soffitCode, $salesCode, 1, $soffitArea);
            $sheetingDesc = '';
            $bom->addCode('', 'STS1', $salesCode, 1, $totalLength); // Sill trim
            $fastenerQty += $totalLength * 9;
        }

        // Fasteners
        if ($fastenerQty > 0) {
            $screwCode = 'CS2';
            if (stripos($roofSheeting . $soffit, 'A') !== false) {
                $screwCode = 'SS2';
            }
            $bom->addCode('', $screwCode, $salesCode, 1, $fastenerQty);
        }
    }
}
