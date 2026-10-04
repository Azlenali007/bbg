<?php
declare(strict_types=1);

require_once __DIR__ . '/admin-auth.php';
require_once __DIR__ . '/auth.php';

class Permissions
{
    public static function canManageSettings(): bool
    {
        $role = $_SESSION['admin_role'] ?? null;
        return $role === 'superadmin';
    }

    public static function canManageFinancials(): bool
    {
        $role = $_SESSION['admin_role'] ?? null;
        return in_array($role, ['superadmin', 'admin'], true);
    }

    public static function canManageUsers(): bool
    {
        $role = $_SESSION['admin_role'] ?? null;
        return in_array($role, ['superadmin', 'admin'], true);
    }

    public static function canManageServices(): bool
    {
        $role = $_SESSION['admin_role'] ?? null;
        return in_array($role, ['superadmin', 'admin'], true);
    }

    public static function canHandleTickets(): bool
    {
        $role = $_SESSION['admin_role'] ?? null;
        return in_array($role, ['superadmin', 'admin', 'support'], true);
    }

    public static function userOwnsOrder(int $orderId, int $userId): bool
    {
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$orderId, $userId]);
        return (bool)$stmt->fetch();
    }

    public static function userOwnsTicket(int $ticketId, int $userId): bool
    {
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM tickets WHERE id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$ticketId, $userId]);
        return (bool)$stmt->fetch();
    }
}
