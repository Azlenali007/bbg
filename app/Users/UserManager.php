<?php
declare(strict_types=1);

namespace App\Users;

use Database;
use PDO;

require_once BASE_PATH . '/includes/database.php';

class UserManager
{
    public static function findById(int $id): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findByUsernameOrEmail(string $identifier): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function generateApiKey(int $userId): string
    {
        $db = Database::getConnection();
        $newKey = bin2hex(random_bytes(32));
        $stmt = $db->prepare("UPDATE users SET api_key = ? WHERE id = ?");
        $stmt->execute([$newKey, $userId]);
        return $newKey;
    }
}
