<?php
/**
 * QuickEst - Quote Item Model
 *
 * Represents a single line item in the Bill of Materials
 * Maps to a row in the Detail sheet
 */

namespace QuickEst\Models;

class QuoteItem {

    public int $lineNumber = 0;
    public string $dbCode = '';          // Database code (e.g., 'BU', 'Z20P')
    public int $salesCode = 1;           // Sales code for grouping
    public string $costCode = '';        // ERP cost code
    public string $description = '';     // Item description
    public float $size = 0;              // Size/dimension (length, area, etc.)
    public string $unit = '';            // Unit of measure (kg, M, M2, EA, etc.)
    public float $quantity = 0;          // Quantity
    public float $unitWeight = 0;        // Weight per unit
    public float $totalWeight = 0;       // Total weight (qty * unit weight)
    public float $materialCost = 0;      // Raw material cost
    public float $manufacturingCost = 0; // Manufacturing cost
    public float $overheadCost = 0;      // Overhead cost
    public float $unitPrice = 0;         // Price per unit
    public float $totalPrice = 0;        // Total price
    public float $surfaceArea = 0;       // Surface area for painting
    public string $phaseNumber = '';     // Phase number for ERP

    public bool $isHeader = false;       // Is this a section header?
    public bool $isSeparator = false;    // Is this a separator line?

    /**
     * Create a header item
     */
    public static function createHeader(string $description, int $salesCode = 1): self {
        $item = new self();
        $item->description = $description;
        $item->salesCode = $salesCode;
        $item->isHeader = true;
        return $item;
    }

    /**
     * Create a separator item
     */
    public static function createSeparator(): self {
        $item = new self();
        $item->dbCode = '-';
        $item->isSeparator = true;
        return $item;
    }

    /**
     * Create item from database lookup
     */
    public static function createFromCode(
        string $code,
        float $size,
        float $quantity,
        int $salesCode = 1,
        string $description = ''
    ): self {
        $item = new self();
        $item->dbCode = $code;
        $item->size = $size;
        $item->quantity = $quantity;
        $item->salesCode = $salesCode;

        // Lookup from database
        $product = \Database::findProduct($code);
        if ($product) {
            $item->description = $description ?: $product['description'];
            $item->unit = $product['unit'];
            $item->unitWeight = floatval($product['weight']);
            $item->materialCost = floatval($product['material_cost']);
            $item->manufacturingCost = floatval($product['manuf_cost']);
            $item->overheadCost = floatval($product['overhead_cost']);
            $item->unitPrice = floatval($product['price']);
            $item->costCode = $product['erp_code'];
            $item->phaseNumber = $product['phase_number'];
        }

        // Calculate totals
        $item->calculateTotals();

        return $item;
    }

    /**
     * Calculate total weight and price
     */
    public function calculateTotals(): void {
        // Calculate based on unit type
        switch (strtoupper($this->unit)) {
            case 'M':
                // Linear items: size is length, qty is number of pieces
                $this->totalWeight = $this->unitWeight * $this->size * $this->quantity;
                $this->totalPrice = $this->unitPrice * $this->size * $this->quantity;
                break;

            case 'M2':
                // Area items: quantity is the area
                $this->totalWeight = $this->unitWeight * $this->quantity;
                $this->totalPrice = $this->unitPrice * $this->quantity;
                break;

            case 'KG':
                // Weight-based items
                $this->totalWeight = $this->quantity;
                $this->totalPrice = $this->unitPrice * $this->quantity;
                break;

            case 'EA':
            case 'NOS':
            case 'SET':
                // Count items
                $this->totalWeight = $this->unitWeight * $this->quantity;
                $this->totalPrice = $this->unitPrice * $this->quantity;
                break;

            default:
                $this->totalWeight = $this->unitWeight * $this->quantity;
                $this->totalPrice = $this->unitPrice * $this->quantity;
        }
    }

    /**
     * Convert to array for display
     */
    public function toArray(): array {
        return [
            'lineNumber' => $this->lineNumber,
            'dbCode' => $this->dbCode,
            'salesCode' => $this->salesCode,
            'costCode' => $this->costCode,
            'description' => $this->description,
            'size' => $this->size,
            'unit' => $this->unit,
            'quantity' => $this->quantity,
            'unitWeight' => $this->unitWeight,
            'totalWeight' => $this->totalWeight,
            'materialCost' => $this->materialCost,
            'manufacturingCost' => $this->manufacturingCost,
            'overheadCost' => $this->overheadCost,
            'unitPrice' => $this->unitPrice,
            'totalPrice' => $this->totalPrice,
            'surfaceArea' => $this->surfaceArea,
            'phaseNumber' => $this->phaseNumber,
            'isHeader' => $this->isHeader,
            'isSeparator' => $this->isSeparator,
        ];
    }
}
