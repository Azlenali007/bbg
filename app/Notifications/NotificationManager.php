<?php
declare(strict_types=1);

namespace App\Notifications;

use Database;
use Logger;
use Throwable;

require_once BASE_PATH . '/includes/database.php';
require_once BASE_PATH . '/includes/logger.php';

class NotificationManager
{
    public static function getUserNotifications(int $userId, int $limit = 20): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? OR user_id IS NULL
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->bindValue(1, $userId, \PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getUnreadCount(int $userId): int
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM notifications 
            WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0
        ");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    public static function markAsRead(int $id, int $userId): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE notifications 
            SET is_read = 1 
            WHERE id = ? AND (user_id = ? OR user_id IS NULL)
        ");
        return $stmt->execute([$id, $userId]);
    }

    public static function markAllAsRead(int $userId): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE notifications 
            SET is_read = 1 
            WHERE user_id = ? OR user_id IS NULL
        ");
        return $stmt->execute([$userId]);
    }

    public static function create(?int $userId, string $title, string $message, string $type = 'info'): bool
    {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("
                INSERT INTO notifications (user_id, title, message, type, is_read, created_at)
                VALUES (?, ?, ?, ?, 0, NOW())
            ");
            return $stmt->execute([$userId, $title, $message, $type]);
        } catch (Throwable $e) {
            Logger::error("Failed to create notification: " . $e->getMessage());
            return false;
        }
    }
}
