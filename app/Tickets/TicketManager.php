<?php
declare(strict_types=1);

namespace App\Tickets;

use Database;
use Logger;
use Throwable;

require_once BASE_PATH . '/includes/database.php';
require_once BASE_PATH . '/includes/logger.php';

class TicketManager
{
    public static function createTicket(
        int $userId,
        string $subject,
        string $category,
        string $priority,
        string $message
    ): array {
        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare("
                INSERT INTO tickets (user_id, subject, category, priority, status, last_reply_at, created_at)
                VALUES (?, ?, ?, ?, 'open', NOW(), NOW())
            ");
            $stmt->execute([$userId, $subject, $category, $priority]);
            $ticketId = (int)$db->lastInsertId();

            $msgStmt = $db->prepare("
                INSERT INTO ticket_messages (ticket_id, user_id, is_admin, message, created_at)
                VALUES (?, ?, 0, ?, NOW())
            ");
            $msgStmt->execute([$ticketId, $userId, $message]);

            $db->commit();
            return ['success' => true, 'ticket_id' => $ticketId];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to create ticket: " . $e->getMessage());
            return ['success' => false, 'message' => 'Unable to create ticket. Please try again.'];
        }
    }

    public static function addReply(int $ticketId, int $userId, bool $isAdmin, string $message): bool
    {
        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $msgStmt = $db->prepare("
                INSERT INTO ticket_messages (ticket_id, user_id, is_admin, message, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $msgStmt->execute([$ticketId, $userId, $isAdmin ? 1 : 0, $message]);

            $newStatus = $isAdmin ? 'answered' : 'user_reply';
            $ticketUpdate = $db->prepare("
                UPDATE tickets 
                SET status = ?, last_reply_at = NOW(), updated_at = NOW() 
                WHERE id = ?
            ");
            $ticketUpdate->execute([$newStatus, $ticketId]);

            $db->commit();
            return true;
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error("Failed to add ticket reply: " . $e->getMessage());
            return false;
        }
    }

    public static function getUserTickets(int $userId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM tickets WHERE user_id = ? ORDER BY last_reply_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function getTicketDetails(int $ticketId, ?int $userId = null): ?array
    {
        $db = Database::getConnection();
        $sql = "
            SELECT t.*, u.username, u.email 
            FROM tickets t
            JOIN users u ON t.user_id = u.id
            WHERE t.id = ?
        ";
        $params = [$ticketId];
        if ($userId !== null) {
            $sql .= " AND t.user_id = ?";
            $params[] = $userId;
        }
        $sql .= " LIMIT 1";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $ticket = $stmt->fetch();
        if (!$ticket) {
            return null;
        }

        $msgStmt = $db->prepare("
            SELECT m.*, 
                   CASE WHEN m.is_admin = 1 THEN 'Support Staff' ELSE u.username END as author_name
            FROM ticket_messages m
            LEFT JOIN users u ON m.user_id = u.id
            WHERE m.ticket_id = ?
            ORDER BY m.id ASC
        ");
        $msgStmt->execute([$ticketId]);
        $ticket['messages'] = $msgStmt->fetchAll();

        return $ticket;
    }

    public static function closeTicket(int $ticketId): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE tickets SET status = 'closed', updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$ticketId]);
    }
}
