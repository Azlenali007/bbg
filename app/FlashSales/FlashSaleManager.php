<?php
declare(strict_types=1);

namespace App\FlashSales;

use Database;
use Logger;
use Throwable;

require_once BASE_PATH . '/includes/database.php';
require_once BASE_PATH . '/includes/logger.php';

class FlashSaleManager
{
    public static function getActiveSales(): array
    {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT f.*, 
                   s.name as service_name, s.rate as original_service_rate,
                   c.name as category_name,
                   TIMESTAMPDIFF(SECOND, NOW(), f.end_time) as remaining_seconds
            FROM flash_sales f
            LEFT JOIN services s ON f.service_id = s.id
            LEFT JOIN categories c ON f.category_id = c.id
            WHERE f.is_active = 1 
              AND NOW() >= f.start_time 
              AND NOW() <= f.end_time
            ORDER BY f.end_time ASC
        ");
        return $stmt->fetchAll();
    }

    public static function getActiveSaleForService(int $serviceId, ?int $categoryId = null): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT f.*, TIMESTAMPDIFF(SECOND, NOW(), f.end_time) as remaining_seconds
            FROM flash_sales f
            WHERE f.is_active = 1
              AND NOW() >= f.start_time
              AND NOW() <= f.end_time
              AND (f.service_id = ? OR (f.service_id IS NULL AND f.category_id = ?))
            ORDER BY f.service_id DESC, f.discount_percentage DESC
            LIMIT 1
        ");
        $stmt->execute([$serviceId, $categoryId]);
        $sale = $stmt->fetch();
        return $sale ?: null;
    }

    public static function getAllSales(): array
    {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT f.*, 
                   s.name as service_name,
                   c.name as category_name,
                   TIMESTAMPDIFF(SECOND, NOW(), f.end_time) as remaining_seconds
            FROM flash_sales f
            LEFT JOIN services s ON f.service_id = s.id
            LEFT JOIN categories c ON f.category_id = c.id
            ORDER BY f.id DESC
        ");
        return $stmt->fetchAll();
    }

    public static function createSale(array $data): array
    {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("
                INSERT INTO flash_sales (
                    service_id, category_id, title, description,
                    discount_percentage, special_rate, start_time, end_time,
                    banner_image, is_active, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                !empty($data['service_id']) ? (int)$data['service_id'] : null,
                !empty($data['category_id']) ? (int)$data['category_id'] : null,
                $data['title'],
                $data['description'] ?? null,
                (float)($data['discount_percentage'] ?? 0),
                !empty($data['special_rate']) ? (float)$data['special_rate'] : null,
                $data['start_time'],
                $data['end_time'],
                $data['banner_image'] ?? null,
                !empty($data['is_active']) ? 1 : 0
            ]);

            return ['success' => true, 'id' => (int)$db->lastInsertId()];
        } catch (Throwable $e) {
            Logger::error("Failed to create flash sale: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function deleteSale(int $id): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM flash_sales WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
