<?php
/**
 * QuickEst - Building Model
 *
 * Represents all input parameters for a pre-engineered building
 * Maps to the Input sheet in Excel
 */

namespace QuickEst\Models;

class Building {

    // Project Information
    public string $projectName = '';
    public string $buildingName = '';
    public string $customerName = '';
    public string $projectNumber = '';
    public string $buildingNumber = '';
    public string $revisionNumber = '';
    public string $date = '';
    public string $estimatedBy = '';
    public string $location = '';

    // Building Dimensions (Basic section)
    public string $spans = '';           // e.g., "2@24" - 2 spans of 24m
    public string $bays = '';            // e.g., "6@6" - 6 bays of 6m
    public string $slopes = '';          // e.g., "1@0.1" - slope ratio
    public float $backEaveHeight = 0;    // BEH
    public float $frontEaveHeight = 0;   // FEH
    public string $frameType = 'Clear Span';  // Clear Span, Multi Span, Lean To
    public float $minThickness = 6;      // Minimum plate thickness (mm)
    public string $baseType = 'Pinned Base';  // Pinned Base, Fixed Base
    public string $doubleWelded = 'No';  // Yes/No
    public string $leftEndwallType = 'Bearing Frame';  // Bearing Frame, Main Frame, False Rafter, MF 1/2 Loaded
    public string $rightEndwallType = 'Bearing Frame';
    public string $leftEndwallSpans = '';
    public string $rightEndwallSpans = '';
    public string $roofSagRods = '0';    // Number or 'A' for auto
    public string $wallSagRods = '0';
    public float $roofSagRodDia = 12;    // mm
    public float $wallSagRodDia = 12;    // mm
    public string $bracingType = 'Cables';  // Cables, Rods, Angles

    // Finish Options
    public string $buFinish = 'Red Oxide Primer';  // Built-up finish
    public string $cfFinish = 'Galvanized';        // Cold-formed finish

    // Eave Conditions
    public string $backEaveCondition = 'Gutter+Dwnspts';  // Gutter+Dwnspts, Curved, Curved+VGutter, Eave Trim
    public string $frontEaveCondition = 'Gutter+Dwnspts';

    // Loads
    public float $deadLoad = 0.1;        // kN/m2
    public float $liveLoadPurlin = 0.57; // kN/m2
    public float $liveLoadFrame = 0.57;  // kN/m2
    public float $additionalLoad = 0;    // kN/m2
    public float $windSpeed = 130;       // km/h

    // Purlins & Girts
    public int $purlinSize = 200;        // 200, 250, 360
    public string $purlinProfile = 'Z-Section';

    // Roof Sheeting
    public string $roofTopSkin = 'S5OW';      // Panel code
    public string $roofCore = '-';            // Core code or '-' for single skin
    public string $roofBotSkin = '-';         // Bottom skin or '-'
    public string $roofInsulation = 'None';
    public string $roofLiner = 'None';
    public string $roofPanelProfile = 'M45-250';  // M45-250, M45-150

    // Wall Sheeting
    public string $wallTopSkin = 'S5OW';
    public string $wallCore = '-';
    public string $wallBotSkin = '-';
    public string $wallInsulation = 'None';
    public string $wallLiner = 'None';

    // Trim sizes
    public string $trimSizes = '0.5 AZ';  // 0.5 AZ, 0.7 AZ, 0.7 AL

    // Openings (array of openings)
    public array $openings = [];

    // Accessories
    public array $accessories = [];

    // Mezzanine
    public ?array $mezzanine = null;

    // Crane
    public ?array $crane = null;

    // Partition
    public ?array $partition = null;

    // Canopy
    public ?array $canopy = null;

    // Monitor
    public ?array $monitor = null;

    // Fascia
    public ?array $fascia = null;

    // Structural Steel (for custom SS items)
    public array $structuralSteel = [];

    // Freight destination
    public string $freightDestination = '';

    // Calculated values (populated by CalculationEngine)
    public float $buildingWidth = 0;
    public float $buildingLength = 0;
    public float $roofArea = 0;
    public float $wallArea = 0;
    public float $endwallArea = 0;
    public float $rafterLength = 0;
    public float $peakHeight = 0;

    /**
     * Create Building from array (e.g., from form submission)
     */
    public static function fromArray(array $data): self {
        $building = new self();

        foreach ($data as $key => $value) {
            if (property_exists($building, $key)) {
                $building->$key = $value;
            }
        }

        return $building;
    }

    /**
     * Convert to array
     */
    public function toArray(): array {
        return get_object_vars($this);
    }

    /**
     * Validate required inputs
     */
    public function validate(): array {
        $errors = [];

        if (empty($this->spans)) {
            $errors[] = 'Spans are required';
        }
        if (empty($this->bays)) {
            $errors[] = 'Bays are required';
        }
        if ($this->backEaveHeight <= 0) {
            $errors[] = 'Back eave height must be greater than 0';
        }
        if ($this->frontEaveHeight <= 0) {
            $errors[] = 'Front eave height must be greater than 0';
        }
        if ($this->windSpeed <= 0) {
            $errors[] = 'Wind speed must be greater than 0';
        }

        return $errors;
    }

    /**
     * Get wind load in kN/m2 (V^2/20000)
     */
    public function getWindLoad(): float {
        return pow($this->windSpeed, 2) / 20000;
    }

    /**
     * Get total purlin load
     */
    public function getTotalPurlinLoad(): float {
        return $this->deadLoad + $this->liveLoadPurlin + $this->additionalLoad;
    }

    /**
     * Get total frame load
     */
    public function getTotalFrameLoad(): float {
        return $this->deadLoad + $this->liveLoadFrame + $this->additionalLoad;
    }
}
