<?php
declare(strict_types=1);

namespace App\Services;

use Database;
use Logger;
use PDO;
use Throwable;

require_once BASE_PATH . '/includes/database.php';
require_once BASE_PATH . '/includes/logger.php';

class ServiceManager
{
    public static function getCategories(bool $onlyActive = true): array
    {
        $db = Database::getConnection();
        $sql = "
            SELECT c.*, COUNT(s.id) as service_count 
            FROM categories c
            LEFT JOIN services s ON c.id = s.category_id " . ($onlyActive ? "AND s.status = 'active'" : "") . "
            " . ($onlyActive ? "WHERE c.status = 'active'" : "") . "
            GROUP BY c.id
            ORDER BY c.sort_order ASC, c.name ASC
        ";
        return $db->query($sql)->fetchAll();
    }

    public static function getActiveServices(?int $categoryId = null, ?string $search = null): array
    {
        $db = Database::getConnection();
        $sql = "
            SELECT s.*, c.name as category_name, c.icon as category_icon, p.name as provider_name
            FROM services s
            JOIN categories c ON s.category_id = c.id
            LEFT JOIN providers p ON s.provider_id = p.id
            WHERE s.status = 'active' AND c.status = 'active'
        ";
        $params = [];

        if ($categoryId !== null && $categoryId > 0) {
            $sql .= " AND s.category_id = ?";
            $params[] = $categoryId;
        }

        if ($search !== null && trim($search) !== '') {
            $sql .= " AND (s.name LIKE ? OR s.id = ? OR c.name LIKE ?)";
            $term = '%' . trim($search) . '%';
            $params[] = $term;
            $params[] = is_numeric($search) ? (int)$search : 0;
            $params[] = $term;
        }

        $sql .= " ORDER BY c.sort_order ASC, s.sort_order ASC, s.id ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getServiceById(int $id): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT s.*, c.name as category_name, p.name as provider_name, p.api_url as provider_url
            FROM services s
            JOIN categories c ON s.category_id = c.id
            LEFT JOIN providers p ON s.provider_id = p.id
            WHERE s.id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $service = $stmt->fetch();
        return $service ?: null;
    }

    public static function createService(array $data): array
    {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("
                INSERT INTO services (
                    category_id, provider_id, provider_service_id, name, type,
                    rate, original_rate, min_quantity, max_quantity,
                    refill_supported, cancel_supported, dripfeed_supported,
                    description, status, sort_order, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?, NOW()
                )
            ");
            $stmt->execute([
                $data['category_id'],
                !empty($data['provider_id']) ? (int)$data['provider_id'] : null,
                $data['provider_service_id'] ?? null,
                $data['name'],
                $data['type'] ?? 'default',
                (float)$data['rate'],
                (float)($data['original_rate'] ?? $data['rate']),
                (int)$data['min_quantity'],
                (int)$data['max_quantity'],
                !empty($data['refill_supported']) ? 1 : 0,
                !empty($data['cancel_supported']) ? 1 : 0,
                !empty($data['dripfeed_supported']) ? 1 : 0,
                $data['description'] ?? null,
                $data['status'] ?? 'active',
                (int)($data['sort_order'] ?? 0)
            ]);

            return ['success' => true, 'id' => (int)$db->lastInsertId()];
        } catch (Throwable $e) {
            Logger::error("Failed to create service: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function updateService(int $id, array $data): array
    {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("
                UPDATE services SET
                    category_id = ?, provider_id = ?, provider_service_id = ?,
                    name = ?, type = ?, rate = ?, min_quantity = ?, max_quantity = ?,
                    refill_supported = ?, cancel_supported = ?, dripfeed_supported = ?,
                    description = ?, status = ?, sort_order = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $data['category_id'],
                !empty($data['provider_id']) ? (int)$data['provider_id'] : null,
                $data['provider_service_id'] ?? null,
                $data['name'],
                $data['type'] ?? 'default',
                (float)$data['rate'],
                (int)$data['min_quantity'],
                (int)$data['max_quantity'],
                !empty($data['refill_supported']) ? 1 : 0,
                !empty($data['cancel_supported']) ? 1 : 0,
                !empty($data['dripfeed_supported']) ? 1 : 0,
                $data['description'] ?? null,
                $data['status'] ?? 'active',
                (int)($data['sort_order'] ?? 0),
                $id
            ]);

            return ['success' => true];
        } catch (Throwable $e) {
            Logger::error("Failed to update service #{$id}: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function deleteService(int $id): bool
    {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("DELETE FROM services WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Throwable $e) {
            Logger::error("Failed to delete service #{$id}: " . $e->getMessage());
            return false;
        }
    }

    public static function createCategory(string $name, string $icon = 'cube', int $sortOrder = 0): int
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO categories (name, icon, sort_order, status, created_at) VALUES (?, ?, ?, 'active', NOW())");
        $stmt->execute([$name, $icon, $sortOrder]);
        return (int)$db->lastInsertId();
    }
}
