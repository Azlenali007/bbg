<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/security.php';

class Auth
{
    private static ?array $cachedUser = null;

    public static function check(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    public static function id(): ?int
    {
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }

    public static function user(): ?array
    {
        if (self::$cachedUser !== null) {
            return self::$cachedUser;
        }

        $id = self::id();
        if (!$id) {
            return null;
        }

        $db = getDB();
        $stmt = $db->prepare("
            SELECT u.*, w.balance AS wallet_balance, w.total_spent, w.total_deposited 
            FROM users u
            LEFT JOIN wallets w ON u.id = w.user_id
            WHERE u.id = ? AND u.status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        if (!$user) {
            // User banned or removed
            self::logout();
            return null;
        }

        self::$cachedUser = $user;
        return self::$cachedUser;
    }

    public static function login(string $identifier, string $password): array
    {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT * FROM users 
            WHERE (username = ? OR email = ?) 
            LIMIT 1
        ");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid username/email or password.'];
        }

        if ($user['status'] === 'banned') {
            return ['success' => false, 'message' => 'Your account has been suspended or banned. Please contact support.'];
        }

        regenerateSession();
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        logAudit((int)$user['id'], false, 'user_login', 'Successful login');

        return ['success' => true, 'user' => $user];
    }

    public static function register(string $username, string $email, string $password): array
    {
        $db = getDB();

        // Check duplicate
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Username or email already in use.'];
        }

        Database::beginTransaction();
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $apiKey = bin2hex(random_bytes(24));

            $insertStmt = $db->prepare("
                INSERT INTO users (username, email, password, balance, spent, role, api_key, status, created_at)
                VALUES (?, ?, ?, 0.0000, 0.0000, 'user', ?, 'active', NOW())
            ");
            $insertStmt->execute([$username, $email, $hash, $apiKey]);
            $userId = (int)$db->lastInsertId();

            // Create user wallet record
            $walletStmt = $db->prepare("
                INSERT INTO wallets (user_id, balance, total_deposited, total_spent, updated_at)
                VALUES (?, 0.0000, 0.0000, 0.0000, NOW())
            ");
            $walletStmt->execute([$userId]);

            // Welcome notification
            $notifStmt = $db->prepare("
                INSERT INTO notifications (user_id, title, message, type, is_read, created_at)
                VALUES (?, CONCAT('Welcome to ', ?), 'Your account was successfully registered. Check out our services to get started.', 'success', 0, NOW())
            ");
            $notifStmt->execute([$userId, APP_NAME]);

            Database::commit();

            logAudit($userId, false, 'user_register', 'New user registered');

            // Auto-login after registration
            regenerateSession();
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = 'user';

            return ['success' => true, 'user_id' => $userId];
        } catch (Throwable $e) {
            Database::rollBack();
            Logger::error("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred during registration. Please try again.'];
        }
    }

    public static function logout(): void
    {
        if (isset($_SESSION['user_id'])) {
            logAudit((int)$_SESSION['user_id'], false, 'user_logout');
        }
        self::$cachedUser = null;
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        @session_destroy();
    }

    public static function requireAuth(): array
    {
        if (!self::check()) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) || str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/ajax/')) {
                renderErrorPage(401, 'Unauthorized', 'Please login to continue.');
                exit;
            }
            header('Location: /login.php');
            exit;
        }

        $user = self::user();
        if (!$user) {
            header('Location: /login.php');
            exit;
        }

        return $user;
    }
}
