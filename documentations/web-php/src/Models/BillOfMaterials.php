<?php
/**
 * QuickEst - Bill of Materials Model
 *
 * Contains the complete list of items for a building estimate
 * Maps to the Detail sheet in Excel
 */

namespace QuickEst\Models;

class BillOfMaterials {

    /** @var QuoteItem[] */
    public array $items = [];

    private int $lineCounter = 0;

    /**
     * Add an item to the BOM
     */
    public function addItem(QuoteItem $item): void {
        $this->lineCounter++;
        $item->lineNumber = $this->lineCounter;
        $this->items[] = $item;
    }

    /**
     * Add a header row
     */
    public function addHeader(string $description, int $salesCode = 1): void {
        $item = QuoteItem::createHeader($description, $salesCode);
        $this->addItem($item);
    }

    /**
     * Add a separator row
     */
    public function addSeparator(): void {
        $item = QuoteItem::createSeparator();
        $this->addItem($item);
    }

    /**
     * Add item from code (InsCode equivalent)
     */
    public function addCode(
        string $description,
        string $code,
        int $salesCode,
        mixed $size,
        mixed $quantity
    ): void {
        // Convert to proper types
        $size = is_numeric($size) ? floatval($size) : 0;
        $quantity = is_numeric($quantity) ? floatval($quantity) : 0;
        // If description is provided, add header first
        if (!empty($description) && $code !== '-') {
            $this->addHeader($description, $salesCode);
        }

        // Add the item if code is valid
        if (!empty($code) && $code !== '-') {
            $item = QuoteItem::createFromCode($code, $size, $quantity, $salesCode);
            $this->addItem($item);
        } elseif ($code === '-') {
            $this->addSeparator();
        }
    }

    /**
     * Get total weight
     */
    public function getTotalWeight(): float {
        $total = 0;
        foreach ($this->items as $item) {
            if (!$item->isHeader && !$item->isSeparator) {
                $total += $item->totalWeight;
            }
        }
        return $total;
    }

    /**
     * Get total price
     */
    public function getTotalPrice(): float {
        $total = 0;
        foreach ($this->items as $item) {
            if (!$item->isHeader && !$item->isSeparator) {
                $total += $item->totalPrice;
            }
        }
        return $total;
    }

    /**
     * Get weight by category (sales code)
     */
    public function getWeightBySalesCode(int $salesCode): float {
        $total = 0;
        foreach ($this->items as $item) {
            if ($item->salesCode === $salesCode && !$item->isHeader && !$item->isSeparator) {
                $total += $item->totalWeight;
            }
        }
        return $total;
    }

    /**
     * Get total material cost
     */
    public function getTotalMaterialCost(): float {
        $total = 0;
        foreach ($this->items as $item) {
            if (!$item->isHeader && !$item->isSeparator) {
                $total += $item->materialCost * $item->quantity;
            }
        }
        return $total;
    }

    /**
     * Get total manufacturing cost
     */
    public function getTotalManufacturingCost(): float {
        $total = 0;
        foreach ($this->items as $item) {
            if (!$item->isHeader && !$item->isSeparator) {
                $total += $item->manufacturingCost * $item->quantity;
            }
        }
        return $total;
    }

    /**
     * Get count of items
     */
    public function getItemCount(): int {
        return count(array_filter($this->items, function($item) {
            return !$item->isHeader && !$item->isSeparator;
        }));
    }

    /**
     * Get items grouped by sales code
     */
    public function getItemsBySalesCode(): array {
        $grouped = [];
        foreach ($this->items as $item) {
            $code = $item->salesCode;
            if (!isset($grouped[$code])) {
                $grouped[$code] = [];
            }
            $grouped[$code][] = $item;
        }
        return $grouped;
    }

    /**
     * Convert to array for display/export
     */
    public function toArray(): array {
        return array_map(function($item) {
            return $item->toArray();
        }, $this->items);
    }

    /**
     * Export to CSV format
     */
    public function toCsv(): string {
        $lines = [];

        // Header row
        $lines[] = implode(',', [
            'Line', 'DB Code', 'Sales Code', 'Cost Code', 'Description',
            'Size', 'Unit', 'Qty', 'Unit Wt', 'Total Wt',
            'Unit Price', 'Total Price', 'Phase No'
        ]);

        // Data rows
        foreach ($this->items as $item) {
            $lines[] = implode(',', [
                $item->lineNumber,
                $item->dbCode,
                $item->salesCode,
                $item->costCode,
                '"' . str_replace('"', '""', $item->description) . '"',
                $item->size,
                $item->unit,
                $item->quantity,
                round($item->unitWeight, 4),
                round($item->totalWeight, 2),
                round($item->unitPrice, 2),
                round($item->totalPrice, 2),
                $item->phaseNumber
            ]);
        }

        // Summary
        $lines[] = '';
        $lines[] = ',,,Total Weight:,' . round($this->getTotalWeight(), 2) . ' kg';
        $lines[] = ',,,Total Price:,' . round($this->getTotalPrice(), 2) . ' AED';

        return implode("\n", $lines);
    }

    /**
     * Merge another BOM into this one
     */
    public function merge(BillOfMaterials $other): void {
        foreach ($other->items as $item) {
            $this->addItem(clone $item);
        }
    }

    /**
     * Clear all items
     */
    public function clear(): void {
        $this->items = [];
        $this->lineCounter = 0;
    }
}
