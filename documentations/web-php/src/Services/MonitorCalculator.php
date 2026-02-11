<?php
/**
 * QuickEst - Roof Monitor Calculator
 *
 * Calculates roof monitor (clerestory) materials
 * Replicates VBA AddRoofMonitor procedure
 */

namespace QuickEst\Services;

use QuickEst\Models\BillOfMaterials;
use QuickEst\Helpers\ListParser;
use QuickEst\Database\ProductLookup;

class MonitorCalculator {

    /**
     * Calculate roof monitor materials
     *
     * @param array $params Monitor parameters:
     *   - description: string
     *   - salesCode: int
     *   - monitorType: string (Curve-CF/Straight-CF/Curve-HR/Straight-HR)
     *   - baySpacing: string (e.g., "6@6")
     *   - openingWidth: float (mm) - throat width
     *   - monitorLength: float (m)
     *   - roofSheeting: string
     *   - wallSheeting: string
     *   - profileWidth: float (m) - panel profile width
     */
    public function calculate(array $params): BillOfMaterials {
        $bom = new BillOfMaterials();

        // Extract parameters
        $description = $params['description'] ?? 'Roof Monitor';
        $salesCode = intval($params['salesCode'] ?? 1);
        $monitorType = $params['monitorType'] ?? 'Curve-CF';
        $baySpacingStr = $params['baySpacing'] ?? '6@6';
        $openingWidth = floatval($params['openingWidth'] ?? 1000); // mm
        $monitorLength = floatval($params['monitorLength'] ?? 36); // m
        $roofSheeting = $params['roofSheeting'] ?? 'S5OW';
        $wallSheeting = $params['wallSheeting'] ?? 'S5OW';
        $profileWidth = floatval($params['profileWidth'] ?? 1); // m

        // Parse bay spacing
        $baySpacing = ListParser::parseList($baySpacingStr);
        $numBays = ListParser::getTotalCount($baySpacing);
        $bayWidth = $baySpacing[0]['value'] ?? 6;

        // Calculate monitor frame length (Ls) based on opening width
        // Ls is roughly opening width + frame depth
        $ls = $openingWidth + 800; // mm

        // Monitor count based on bays
        $monitorCount = $numBays + 1;

        // Determine calculation type
        switch ($monitorType) {
            case 'Curve-CF':
                return $this->calculateCurveColdFormed($bom, $params, $monitorCount, $numBays, $ls, $monitorLength, $salesCode);
            case 'Straight-CF':
                return $this->calculateStraightColdFormed($bom, $params, $monitorCount, $numBays, $ls, $monitorLength, $salesCode);
            case 'Curve-HR':
                return $this->calculateCurveHotRolled($bom, $params, $monitorCount, $numBays, $ls, $monitorLength, $salesCode);
            case 'Straight-HR':
                return $this->calculateStraightHotRolled($bom, $params, $monitorCount, $numBays, $ls, $monitorLength, $salesCode);
            default:
                return $this->calculateCurveColdFormed($bom, $params, $monitorCount, $numBays, $ls, $monitorLength, $salesCode);
        }
    }

    /**
     * Calculate Curved Eave Cold Formed Monitor
     */
    private function calculateCurveColdFormed(
        BillOfMaterials $bom,
        array $params,
        int $monitorCount,
        int $numBays,
        float $ls,
        float $monitorLength,
        int $salesCode
    ): BillOfMaterials {
        $openingWidth = floatval($params['openingWidth'] ?? 1000);
        $roofSheeting = $params['roofSheeting'] ?? 'S5OW';
        $wallSheeting = $params['wallSheeting'] ?? 'S5OW';
        $profileWidth = floatval($params['profileWidth'] ?? 1);
        $baySpacingStr = $params['baySpacing'] ?? '6@6';

        if ($openingWidth > 1000) {
            // Exceeds cold-formed limitation
            return $this->calculateCurveHotRolled($bom, $params, $monitorCount, $numBays, $ls, $monitorLength, $salesCode);
        }

        $bom->addCode("Roof Monitor - Curved Eave/Cold Formed", '-', $salesCode, '', '');

        // Maximum purlin spacing
        $pSpaceMax = round(0.5 * ($ls - 900), 0);

        // Number of purlins
        $nPurlins = ($pSpaceMax > 1550) ? 8 : 6;
        $nGirts = 2;

        // Number of clips
        $nClips = ($nPurlins + $nGirts) * ($numBays + 1);

        // Monitor frame (C20G)
        $bom->addCode('Monitor Frame', 'C20G', $salesCode, 1, $monitorCount * 5.99);

        // Clips
        $bom->addCode('', 'RMClip1', $salesCode, 1, $nClips);

        // M12 bolts
        $bom->addCode('', 'HSB12', $salesCode, 1, $monitorCount * 68);

        // Bent angle
        $bom->addCode('', 'LA', $salesCode, 1, $monitorLength * 4);

        // Purlins
        $this->addPurlins($bom, $baySpacingStr, $salesCode, 8 * $numBays);

        // Wire mesh
        $bom->addCode('', 'WRM', $salesCode, 1, $monitorLength * 1.7);

        // Foam closure
        $bom->addCode('', 'FCM45', $salesCode, 1, $monitorLength * 2);

        // Bead mastic
        $bom->addCode('', 'BM2', $salesCode, 1, $monitorLength * 2);

        // Sheeting screws
        $bom->addCode('', 'CS2', $salesCode, 1, ($monitorLength * 18) / 0.25);

        // Ridge panel
        $ridgeCode = ProductLookup::codeOf($roofSheeting) ?: $roofSheeting;
        $bom->addCode('Ridge Panel', $ridgeCode, $salesCode, 1, ($monitorLength * 1) / $profileWidth);

        // Curved panel
        $bom->addCode('Curved Panel', $ridgeCode, $salesCode, 1, ($monitorLength * 2) / $profileWidth);

        // Drip trim
        $bom->addCode('', 'DTS1', $salesCode, 1, $monitorLength * 2);

        // Roof panel area
        $roofPanelArea = round((2 * ($ls - 750) * $monitorLength) / 1000, 2);
        $bom->addCode('Roof Panels', $roofSheeting, $salesCode, 1, $roofPanelArea);

        // Sidewall panel area
        $bom->addCode('Sidewall Panels', $wallSheeting, $salesCode, 1, $monitorLength * 1.3);

        // Endwall panel area
        $bom->addCode('Endwall Panels', $wallSheeting, $salesCode, 1, 7);

        // Gable trim
        $bom->addCode('', 'GTS1', $salesCode, 1, 7);

        // Curve trim
        $bom->addCode('', 'CTS1', $salesCode, 1, 4);

        // Peak box
        $bom->addCode('', 'PeakBox', $salesCode, 1, 2);

        // PRV screws
        $bom->addCode('', 'PRVS', $salesCode, 1, 80);
        $bom->addCode('', 'SS2', $salesCode, 1, 80);

        // Flowable mastic
        $bom->addCode('', 'FLM', $salesCode, 1, 2);

        // Braced bay angle
        $bom->addCode('', 'FBA', $salesCode, 1, 13);

        // Closure trim
        $bom->addCode('', 'ClT', $salesCode, 1, 1.8);

        // Gable angle
        $bom->addCode('', 'Gang', $salesCode, 1, 3);

        $bom->addCode('', '-', $salesCode, '', '');
        return $bom;
    }

    /**
     * Calculate Straight Eave Cold Formed Monitor
     */
    private function calculateStraightColdFormed(
        BillOfMaterials $bom,
        array $params,
        int $monitorCount,
        int $numBays,
        float $ls,
        float $monitorLength,
        int $salesCode
    ): BillOfMaterials {
        $openingWidth = floatval($params['openingWidth'] ?? 1000);
        $roofSheeting = $params['roofSheeting'] ?? 'S5OW';
        $wallSheeting = $params['wallSheeting'] ?? 'S5OW';
        $profileWidth = floatval($params['profileWidth'] ?? 1);
        $baySpacingStr = $params['baySpacing'] ?? '6@6';

        if ($openingWidth > 1000) {
            return $this->calculateStraightHotRolled($bom, $params, $monitorCount, $numBays, $ls, $monitorLength, $salesCode);
        }

        $bom->addCode("Roof Monitor - Straight Eave/Cold Formed", '-', $salesCode, '', '');

        // Maximum purlin spacing
        $pSpaceMax = round(0.5 * ($ls - 375), 0);

        // Number of purlins
        $nPurlins = ($pSpaceMax > 1550) ? 8 : 6;

        // Number of clips
        $nClips = $nPurlins * ($numBays + 1);

        // Monitor frame (C20G)
        $bom->addCode('Monitor Frame', 'C20G', $salesCode, 1, $monitorCount * 4.22);

        // Clips
        $bom->addCode('', 'RMClip2', $salesCode, 1, $nClips);

        // M12 bolts
        $bom->addCode('', 'HSB12', $salesCode, 1, $monitorCount * 44);

        // Bent angle
        $bom->addCode('', 'LA', $salesCode, 1, $monitorLength * 4);

        // Purlins
        $this->addPurlins($bom, $baySpacingStr, $salesCode, 4 * $numBays);

        // Wire mesh
        $bom->addCode('', 'WRM', $salesCode, 1, $monitorLength * 2.1);

        // Foam closure
        $bom->addCode('', 'FCM45', $salesCode, 1, $monitorLength * 2);

        // Bead mastic
        $bom->addCode('', 'BM2', $salesCode, 1, $monitorLength * 2);

        // Sheeting screws
        $bom->addCode('', 'CS2', $salesCode, 1, ($monitorLength * 12) / 0.25);

        // Ridge panel
        $ridgeCode = ProductLookup::codeOf($roofSheeting) ?: $roofSheeting;
        $bom->addCode('Ridge Panel', $ridgeCode, $salesCode, 1, ($monitorLength * 1) / $profileWidth);

        // Roof panel area
        $roofPanelArea = round((2 * ($ls - 80) * $monitorLength) / 1000, 2);
        $bom->addCode('Roof Panels', $roofSheeting, $salesCode, 1, $roofPanelArea);

        // Endwall panels
        $bom->addCode('Endwall Panels', $wallSheeting, $salesCode, 1, 4.5);

        // Gable trim
        $bom->addCode('', 'GTS1', $salesCode, 1, 8.6);

        // Peak box
        $bom->addCode('', 'PeakBox', $salesCode, 1, 2);

        // PRV screws
        $bom->addCode('', 'PRVS', $salesCode, 1, 80);
        $bom->addCode('', 'SS2', $salesCode, 1, 80);

        // Braced bay angle
        $bom->addCode('', 'FBA', $salesCode, 1, 13);

        // Closure trim
        $bom->addCode('', 'ClT', $salesCode, 1, 1.8);

        // Gable angle
        $bom->addCode('', 'Gang', $salesCode, 1, 3);

        $bom->addCode('', '-', $salesCode, '', '');
        return $bom;
    }

    /**
     * Calculate Curved Eave Hot Rolled Monitor
     */
    private function calculateCurveHotRolled(
        BillOfMaterials $bom,
        array $params,
        int $monitorCount,
        int $numBays,
        float $ls,
        float $monitorLength,
        int $salesCode
    ): BillOfMaterials {
        $roofSheeting = $params['roofSheeting'] ?? 'S5OW';
        $wallSheeting = $params['wallSheeting'] ?? 'S5OW';
        $baySpacingStr = $params['baySpacing'] ?? '6@6';
        $monitorFrameLength = floatval($params['monitorFrameLength'] ?? 8000); // mm

        $bom->addCode("Roof Monitor - Curved Eave/Hot-Rolled", '-', $salesCode, '', '');

        // Maximum purlin spacing
        $pSpaceMax = round(0.5 * ($ls - 900), 0);
        $nPurlins = ($pSpaceMax > 1550) ? 8 : 6;
        $nGirts = 2;

        // Total length of HR sections
        $monitorTotalFrameLength = ($monitorFrameLength * $monitorCount) / 1000;
        $bom->addCode('Monitor Frame (IPE)', 'IPEa', $salesCode, 1, $monitorTotalFrameLength);

        // End bay purlin and girts
        $nEndBayPurlinGirts = ($nPurlins + $nGirts) * 2;
        $this->addPurlins($bom, $baySpacingStr, $salesCode, $nEndBayPurlinGirts);

        // Interior bay purlin and girts
        $nInteriorBayPurlinGirts = ($nPurlins + $nGirts) * ($monitorCount - 3);
        if ($nInteriorBayPurlinGirts > 0) {
            $this->addPurlins($bom, $baySpacingStr, $salesCode, $nInteriorBayPurlinGirts);
        }

        // Clips
        $clipWeight = ($nPurlins + $nGirts) * $monitorCount;
        $bom->addCode('', 'RMClip1', $salesCode, 1, $clipWeight);

        // Connection bolts
        $nConnectionBolts = ((6 * ($nPurlins + $nGirts)) + 24) * $monitorCount;
        $bom->addCode('', 'HSB12', $salesCode, 1, $nConnectionBolts);

        // Bent angle
        $bom->addCode('', 'LA', $salesCode, 1, $monitorLength * 4);

        // Wire mesh
        $bom->addCode('', 'WRM', $salesCode, 1, $monitorLength * 1.7);

        // Foam closure
        $bom->addCode('', 'FCM45', $salesCode, 1, $monitorLength * 2);

        // Roof and wall sheeting
        $roofPanelArea = round((2 * ($ls - 750) * $monitorLength) / 1000, 2);
        $bom->addCode('Roof Panels', $roofSheeting, $salesCode, 1, $roofPanelArea);
        $bom->addCode('Wall Panels', $wallSheeting, $salesCode, 1, $monitorLength * 1.3 + 7);

        // Trims
        $bom->addCode('', 'GTS1', $salesCode, 1, 7);
        $bom->addCode('', 'CTS1', $salesCode, 1, 4);
        $bom->addCode('', 'DTS1', $salesCode, 1, $monitorLength * 2);

        // Fasteners
        $bom->addCode('', 'CS2', $salesCode, 1, ($monitorLength * 18) / 0.25);

        $bom->addCode('', '-', $salesCode, '', '');
        return $bom;
    }

    /**
     * Calculate Straight Eave Hot Rolled Monitor
     */
    private function calculateStraightHotRolled(
        BillOfMaterials $bom,
        array $params,
        int $monitorCount,
        int $numBays,
        float $ls,
        float $monitorLength,
        int $salesCode
    ): BillOfMaterials {
        $roofSheeting = $params['roofSheeting'] ?? 'S5OW';
        $wallSheeting = $params['wallSheeting'] ?? 'S5OW';
        $baySpacingStr = $params['baySpacing'] ?? '6@6';
        $monitorFrameLength = floatval($params['monitorFrameLength'] ?? 6000); // mm

        $bom->addCode("Roof Monitor - Straight Eave/Hot-Rolled", '-', $salesCode, '', '');

        // Maximum purlin spacing
        $pSpaceMax = round(0.5 * ($ls - 375), 0);
        $nPurlins = ($pSpaceMax > 1550) ? 8 : 6;

        // Total length of HR sections
        $monitorTotalFrameLength = ($monitorFrameLength * $monitorCount) / 1000;
        $bom->addCode('Monitor Frame (IPE)', 'IPEa', $salesCode, 1, $monitorTotalFrameLength);

        // Purlins
        $nPurlinGirts = $nPurlins * $monitorCount;
        $this->addPurlins($bom, $baySpacingStr, $salesCode, $nPurlinGirts);

        // Clips
        $bom->addCode('', 'RMClip2', $salesCode, 1, $nPurlins * $monitorCount);

        // Connection bolts
        $nConnectionBolts = (6 * $nPurlins + 16) * $monitorCount;
        $bom->addCode('', 'HSB12', $salesCode, 1, $nConnectionBolts);

        // Wire mesh
        $bom->addCode('', 'WRM', $salesCode, 1, $monitorLength * 2.1);

        // Foam closure
        $bom->addCode('', 'FCM45', $salesCode, 1, $monitorLength * 2);

        // Roof and wall sheeting
        $roofPanelArea = round((2 * ($ls - 80) * $monitorLength) / 1000, 2);
        $bom->addCode('Roof Panels', $roofSheeting, $salesCode, 1, $roofPanelArea);
        $bom->addCode('Endwall Panels', $wallSheeting, $salesCode, 1, 4.5);

        // Trims
        $bom->addCode('', 'GTS1', $salesCode, 1, 8.6);

        // Fasteners
        $bom->addCode('', 'CS2', $salesCode, 1, ($monitorLength * 12) / 0.25);

        $bom->addCode('', '-', $salesCode, '', '');
        return $bom;
    }

    /**
     * Add purlins based on bay spacing
     */
    private function addPurlins(
        BillOfMaterials $bom,
        string $baySpacingStr,
        int $salesCode,
        int $qty
    ): void {
        $baySpacing = ListParser::parseList($baySpacingStr);
        $bayWidth = $baySpacing[0]['value'] ?? 6;

        // Calculate purlin design index
        $pdIndex = 1.25 * 0.67 * pow($bayWidth, 2); // Assuming typical load
        $pdCode = ProductLookup::getPurlinCode($pdIndex);

        $size = $bayWidth + 0.107;
        if ($bayWidth > 6.5) $size += 0.599;
        if ($bayWidth > 9) $size += 0.706;

        $bom->addCode('Purlins', $pdCode, $salesCode, $size, $qty);
    }
}
