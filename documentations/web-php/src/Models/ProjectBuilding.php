<?php
/**
 * QuickEst - ProjectBuilding Model
 *
 * Represents a building within a project (supports multi-building)
 */

namespace QuickEst\Models;

use QuickEst\Database\Connection;
use PDO;

class ProjectBuilding
{
    public ?int $id = null;
    public int $projectId;
    public string $buildingNumber = '';
    public string $buildingName = '';
    public string $revisionNumber = '00';
    public string $estimatedBy = '';

    // Input and calculated data as arrays
    public array $inputData = [];
    public ?array $calculatedData = null;

    // Summary fields
    public float $totalWeight = 0;
    public float $totalPrice = 0;
    public float $floorArea = 0;

    public string $status = 'draft';
    public ?string $createdAt = null;
    public ?string $updatedAt = null;
    public ?string $calculatedAt = null;

    /**
     * Find building by ID
     */
    public static function find(int $id): ?self
    {
        $db = Connection::getInstance();
        $stmt = $db->prepare("SELECT * FROM buildings WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * Find building by ID and project
     */
    public static function findByProject(int $id, int $projectId): ?self
    {
        $db = Connection::getInstance();
        $stmt = $db->prepare("SELECT * FROM buildings WHERE id = ? AND project_id = ?");
        $stmt->execute([$id, $projectId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * Get all buildings for a project
     */
    public static function allByProject(int $projectId): array
    {
        $db = Connection::getInstance();
        $stmt = $db->prepare("
            SELECT * FROM buildings
            WHERE project_id = ?
            ORDER BY building_number, building_name
        ");
        $stmt->execute([$projectId]);

        $buildings = [];
        while ($row = $stmt->fetch()) {
            $buildings[] = self::fromRow($row);
        }

        return $buildings;
    }

    /**
     * Create new building
     */
    public static function create(array $data): self
    {
        $db = Connection::getInstance();

        $inputData = $data['input_data'] ?? [];

        // Calculate floor area from input if available
        $floorArea = 0;
        if (!empty($inputData['spans']) && !empty($inputData['bays'])) {
            $width = self::parseSpanBay($inputData['spans']);
            $length = self::parseSpanBay($inputData['bays']);
            $floorArea = $width * $length;
        }

        $stmt = $db->prepare("
            INSERT INTO buildings (
                project_id, building_number, building_name, revision_number,
                estimated_by, input_data, floor_area, status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['project_id'],
            $data['building_number'] ?? '',
            $data['building_name'] ?? '',
            $data['revision_number'] ?? '00',
            $data['estimated_by'] ?? '',
            json_encode($inputData),
            $floorArea,
            $data['status'] ?? 'draft'
        ]);

        return self::find($db->lastInsertId());
    }

    /**
     * Save building changes
     */
    public function save(): bool
    {
        $db = Connection::getInstance();

        if ($this->id) {
            $stmt = $db->prepare("
                UPDATE buildings SET
                    building_number = ?,
                    building_name = ?,
                    revision_number = ?,
                    estimated_by = ?,
                    input_data = ?,
                    calculated_data = ?,
                    total_weight = ?,
                    total_price = ?,
                    floor_area = ?,
                    status = ?,
                    calculated_at = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");

            return $stmt->execute([
                $this->buildingNumber,
                $this->buildingName,
                $this->revisionNumber,
                $this->estimatedBy,
                json_encode($this->inputData),
                $this->calculatedData ? json_encode($this->calculatedData) : null,
                $this->totalWeight,
                $this->totalPrice,
                $this->floorArea,
                $this->status,
                $this->calculatedAt,
                $this->id
            ]);
        }

        return false;
    }

    /**
     * Update calculated data
     */
    public function updateCalculatedData(array $calculatedData): bool
    {
        $this->calculatedData = $calculatedData;
        $this->calculatedAt = date('Y-m-d H:i:s');
        $this->status = 'calculated';

        // Extract summary values
        if (isset($calculatedData['summary'])) {
            $this->totalWeight = $calculatedData['summary']['totalWeight'] ?? 0;
            $this->totalPrice = $calculatedData['summary']['totalPrice'] ?? 0;
        }

        if (isset($calculatedData['dimensions'])) {
            $width = $calculatedData['dimensions']['width'] ?? 0;
            $length = $calculatedData['dimensions']['length'] ?? 0;
            $this->floorArea = $width * $length;
        }

        return $this->save();
    }

    /**
     * Delete building
     */
    public function delete(): bool
    {
        $db = Connection::getInstance();
        $stmt = $db->prepare("DELETE FROM buildings WHERE id = ?");
        return $stmt->execute([$this->id]);
    }

    /**
     * Duplicate building
     */
    public function duplicate(): self
    {
        return self::create([
            'project_id' => $this->projectId,
            'building_number' => $this->buildingNumber . ' (Copy)',
            'building_name' => $this->buildingName . ' (Copy)',
            'revision_number' => '00',
            'estimated_by' => $this->estimatedBy,
            'input_data' => $this->inputData,
            'status' => 'draft'
        ]);
    }

    /**
     * Get parent project
     */
    public function getProject(): ?Project
    {
        return Project::find($this->projectId);
    }

    /**
     * Parse span/bay string to get total dimension
     * e.g., "2@24" => 48, "3@8+2@10" => 44
     */
    private static function parseSpanBay(string $value): float
    {
        $total = 0;
        $parts = explode('+', $value);

        foreach ($parts as $part) {
            $part = trim($part);
            if (strpos($part, '@') !== false) {
                [$count, $size] = explode('@', $part);
                $total += (float)$count * (float)$size;
            } else {
                $total += (float)$part;
            }
        }

        return $total;
    }

    /**
     * Create from database row
     */
    private static function fromRow(array $row): self
    {
        $building = new self();
        $building->id = (int)$row['id'];
        $building->projectId = (int)$row['project_id'];
        $building->buildingNumber = $row['building_number'] ?? '';
        $building->buildingName = $row['building_name'] ?? '';
        $building->revisionNumber = $row['revision_number'] ?? '00';
        $building->estimatedBy = $row['estimated_by'] ?? '';
        $building->inputData = json_decode($row['input_data'] ?? '{}', true) ?: [];
        $building->calculatedData = $row['calculated_data'] ? json_decode($row['calculated_data'], true) : null;
        $building->totalWeight = (float)($row['total_weight'] ?? 0);
        $building->totalPrice = (float)($row['total_price'] ?? 0);
        $building->floorArea = (float)($row['floor_area'] ?? 0);
        $building->status = $row['status'];
        $building->createdAt = $row['created_at'];
        $building->updatedAt = $row['updated_at'];
        $building->calculatedAt = $row['calculated_at'];

        return $building;
    }

    /**
     * Convert to array
     */
    public function toArray(bool $includeCalculated = true): array
    {
        $data = [
            'id' => $this->id,
            'projectId' => $this->projectId,
            'buildingNumber' => $this->buildingNumber,
            'buildingName' => $this->buildingName,
            'revisionNumber' => $this->revisionNumber,
            'estimatedBy' => $this->estimatedBy,
            'inputData' => $this->inputData,
            'totalWeight' => $this->totalWeight,
            'totalPrice' => $this->totalPrice,
            'floorArea' => $this->floorArea,
            'status' => $this->status,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'calculatedAt' => $this->calculatedAt
        ];

        if ($includeCalculated && $this->calculatedData) {
            $data['calculatedData'] = $this->calculatedData;
        }

        return $data;
    }
}
