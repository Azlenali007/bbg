<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/security.php';

class AdminAuth
{
    private static ?array $cachedAdmin = null;

    public static function check(): bool
    {
        return !empty($_SESSION['admin_id']);
    }

    public static function id(): ?int
    {
        return isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;
    }

    public static function user(): ?array
    {
        if (self::$cachedAdmin !== null) {
            return self::$cachedAdmin;
        }

        $id = self::id();
        if (!$id) {
            return null;
        }

        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM admins WHERE id = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$id]);
        $admin = $stmt->fetch();

        if (!$admin) {
            self::logout();
            return null;
        }

        self::$cachedAdmin = $admin;
        return self::$cachedAdmin;
    }

    public static function login(string $identifier, string $password): array
    {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM admins WHERE (username = ? OR email = ?) LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($password, $admin['password'])) {
            return ['success' => false, 'message' => 'Invalid admin credentials.'];
        }

        if ($admin['status'] !== 'active') {
            return ['success' => false, 'message' => 'Admin account is inactive.'];
        }

        regenerateSession();
        $_SESSION['admin_id'] = (int)$admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_role'] = $admin['role'];

        $updateStmt = $db->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
        $updateStmt->execute([$admin['id']]);

        logAudit((int)$admin['id'], true, 'admin_login', 'Admin login successful');

        return ['success' => true, 'admin' => $admin];
    }

    public static function logout(): void
    {
        if (isset($_SESSION['admin_id'])) {
            logAudit((int)$_SESSION['admin_id'], true, 'admin_logout');
        }
        self::$cachedAdmin = null;
        unset($_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['admin_role']);
    }

    public static function requireAdmin(): array
    {
        if (!self::check()) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) || str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/ajax/')) {
                renderErrorPage(401, 'Unauthorized Admin', 'Please login to the administrative console.');
                exit;
            }
            header('Location: /admin/login.php');
            exit;
        }

        $admin = self::user();
        if (!$admin) {
            header('Location: /admin/login.php');
            exit;
        }

        return $admin;
    }
}
