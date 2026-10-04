<?php
declare(strict_types=1);

namespace App\Banners;

use Database;
use Logger;
use Throwable;

require_once BASE_PATH . '/includes/database.php';
require_once BASE_PATH . '/includes/logger.php';

class BannerManager
{
    public static function getActiveBanners(): array
    {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT * FROM banners 
            WHERE is_active = 1
              AND (start_date IS NULL OR start_date <= NOW())
              AND (end_date IS NULL OR end_date >= NOW())
            ORDER BY sort_order ASC, id DESC
        ");
        return $stmt->fetchAll();
    }

    public static function getAllBanners(): array
    {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM banners ORDER BY sort_order ASC, id DESC");
        return $stmt->fetchAll();
    }

    public static function createBanner(array $data): array
    {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("
                INSERT INTO banners (
                    title, subtitle, image_url, cta_text, cta_url,
                    sort_order, is_active, start_date, end_date, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $data['title'],
                $data['subtitle'] ?? null,
                $data['image_url'] ?? null,
                $data['cta_text'] ?? 'Get Started',
                $data['cta_url'] ?? '/register.php',
                (int)($data['sort_order'] ?? 0),
                !empty($data['is_active']) ? 1 : 0,
                !empty($data['start_date']) ? $data['start_date'] : null,
                !empty($data['end_date']) ? $data['end_date'] : null
            ]);
            return ['success' => true, 'id' => (int)$db->lastInsertId()];
        } catch (Throwable $e) {
            Logger::error("Failed to create banner: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function deleteBanner(int $id): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM banners WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
