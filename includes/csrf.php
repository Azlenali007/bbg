<?php
declare(strict_types=1);

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/errors/render.php';

class CSRF
{
    public static function getToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function getField(): string
    {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function verify(?string $token): bool
    {
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function validateRequest(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (in_array(strtoupper($method), ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
            $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
            if (!self::verify($token)) {
                Logger::warning("CSRF verification failed", [
                    'uri' => $_SERVER['REQUEST_URI'] ?? '',
                    'ip'  => $_SERVER['REMOTE_ADDR'] ?? ''
                ]);
                renderErrorPage(419, 'Page Expired', 'The security token was missing or invalid. Please refresh the page and try again.');
                exit;
            }
        }
    }
}

function csrf_token(): string
{
    return CSRF::getToken();
}

function csrf_field(): string
{
    return CSRF::getField();
}

function verify_csrf(?string $token = null): bool
{
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
    }
    return CSRF::verify($token);
}

function require_csrf(): void
{
    CSRF::validateRequest();
}
