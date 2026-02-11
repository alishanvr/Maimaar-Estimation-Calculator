<?php
/**
 * QuickEst - Project Model
 *
 * Handles project data with multiple buildings support
 */

namespace QuickEst\Models;

use QuickEst\Database\Connection;
use PDO;

class Project
{
    public ?int $id = null;
    public int $userId;
    public string $projectNumber = '';
    public string $projectName = '';
    public string $customerName = '';
    public string $location = '';
    public string $description = '';
    public string $status = 'draft';
    public ?string $createdAt = null;
    public ?string $updatedAt = null;

    // Loaded buildings
    private array $buildings = [];
    private bool $buildingsLoaded = false;

    /**
     * Find project by ID
     */
    public static function find(int $id): ?self
    {
        $db = Connection::getInstance();
        $stmt = $db->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * Find project by ID and user
     */
    public static function findByUser(int $id, int $userId): ?self
    {
        $db = Connection::getInstance();
        $stmt = $db->prepare("SELECT * FROM projects WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * Get all projects for a user
     */
    public static function allByUser(int $userId, array $filters = []): array
    {
        $db = Connection::getInstance();

        $sql = "SELECT * FROM projects WHERE user_id = ?";
        $params = [$userId];

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (project_name LIKE ? OR customer_name LIKE ? OR project_number LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $sql .= " ORDER BY updated_at DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
            if (!empty($filters['offset'])) {
                $sql .= " OFFSET " . (int)$filters['offset'];
            }
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        $projects = [];
        while ($row = $stmt->fetch()) {
            $projects[] = self::fromRow($row);
        }

        return $projects;
    }

    /**
     * Get project count for user
     */
    public static function countByUser(int $userId, array $filters = []): int
    {
        $db = Connection::getInstance();

        $sql = "SELECT COUNT(*) as count FROM projects WHERE user_id = ?";
        $params = [$userId];

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (project_name LIKE ? OR customer_name LIKE ? OR project_number LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetch()['count'];
    }

    /**
     * Create new project
     */
    public static function create(array $data): self
    {
        $db = Connection::getInstance();

        $stmt = $db->prepare("
            INSERT INTO projects (user_id, project_number, project_name, customer_name, location, description, status)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['user_id'],
            $data['project_number'] ?? '',
            $data['project_name'],
            $data['customer_name'] ?? '',
            $data['location'] ?? '',
            $data['description'] ?? '',
            $data['status'] ?? 'draft'
        ]);

        $project = self::find($db->lastInsertId());

        // Log history
        self::logHistory($project->id, null, $data['user_id'], 'created', [
            'project_name' => $data['project_name']
        ]);

        return $project;
    }

    /**
     * Save project changes
     */
    public function save(): bool
    {
        $db = Connection::getInstance();

        if ($this->id) {
            $stmt = $db->prepare("
                UPDATE projects SET
                    project_number = ?,
                    project_name = ?,
                    customer_name = ?,
                    location = ?,
                    description = ?,
                    status = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");

            return $stmt->execute([
                $this->projectNumber,
                $this->projectName,
                $this->customerName,
                $this->location,
                $this->description,
                $this->status,
                $this->id
            ]);
        }

        return false;
    }

    /**
     * Delete project and all its buildings
     */
    public function delete(): bool
    {
        $db = Connection::getInstance();

        // Log before delete
        self::logHistory($this->id, null, $this->userId, 'deleted', [
            'project_name' => $this->projectName
        ]);

        $stmt = $db->prepare("DELETE FROM projects WHERE id = ?");
        return $stmt->execute([$this->id]);
    }

    /**
     * Get buildings for this project
     */
    public function getBuildings(): array
    {
        if (!$this->buildingsLoaded && $this->id) {
            $this->buildings = ProjectBuilding::allByProject($this->id);
            $this->buildingsLoaded = true;
        }

        return $this->buildings;
    }

    /**
     * Add a building to this project
     */
    public function addBuilding(array $data): ProjectBuilding
    {
        $data['project_id'] = $this->id;
        $building = ProjectBuilding::create($data);

        // Log history
        self::logHistory($this->id, $building->id, $this->userId, 'building_added', [
            'building_name' => $building->buildingName
        ]);

        $this->buildingsLoaded = false; // Reset to reload
        return $building;
    }

    /**
     * Get project summary (aggregated from all buildings)
     */
    public function getSummary(): array
    {
        $db = Connection::getInstance();

        $stmt = $db->prepare("
            SELECT
                COUNT(*) as building_count,
                COALESCE(SUM(total_weight), 0) as total_weight,
                COALESCE(SUM(total_price), 0) as total_price,
                COALESCE(SUM(floor_area), 0) as total_area
            FROM buildings
            WHERE project_id = ?
        ");
        $stmt->execute([$this->id]);

        return $stmt->fetch();
    }

    /**
     * Get project history
     */
    public function getHistory(): array
    {
        $db = Connection::getInstance();

        $stmt = $db->prepare("
            SELECT h.*, u.username
            FROM project_history h
            LEFT JOIN users u ON h.user_id = u.id
            WHERE h.project_id = ?
            ORDER BY h.created_at DESC
            LIMIT 50
        ");
        $stmt->execute([$this->id]);

        return $stmt->fetchAll();
    }

    /**
     * Log project history
     */
    public static function logHistory(int $projectId, ?int $buildingId, int $userId, string $action, array $details = []): void
    {
        $db = Connection::getInstance();

        $stmt = $db->prepare("
            INSERT INTO project_history (project_id, building_id, user_id, action, details)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $projectId,
            $buildingId,
            $userId,
            $action,
            json_encode($details)
        ]);
    }

    /**
     * Create from database row
     */
    private static function fromRow(array $row): self
    {
        $project = new self();
        $project->id = (int)$row['id'];
        $project->userId = (int)$row['user_id'];
        $project->projectNumber = $row['project_number'] ?? '';
        $project->projectName = $row['project_name'];
        $project->customerName = $row['customer_name'] ?? '';
        $project->location = $row['location'] ?? '';
        $project->description = $row['description'] ?? '';
        $project->status = $row['status'];
        $project->createdAt = $row['created_at'];
        $project->updatedAt = $row['updated_at'];

        return $project;
    }

    /**
     * Convert to array
     */
    public function toArray(bool $includeBuildings = false): array
    {
        $data = [
            'id' => $this->id,
            'userId' => $this->userId,
            'projectNumber' => $this->projectNumber,
            'projectName' => $this->projectName,
            'customerName' => $this->customerName,
            'location' => $this->location,
            'description' => $this->description,
            'status' => $this->status,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'summary' => $this->getSummary()
        ];

        if ($includeBuildings) {
            $data['buildings'] = array_map(fn($b) => $b->toArray(), $this->getBuildings());
        }

        return $data;
    }
}
