<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/logger.php';

function e(?string $value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function getClientIP(): string
{
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

function sendSecurityHeaders(): void
{
    if (headers_sent()) {
        return;
    }
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

function checkRateLimit(string $key, int $maxAttempts = 60, int $decaySeconds = 60): bool
{
    $cacheDir = BASE_PATH . '/storage/cache/ratelimit';
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0755, true);
    }
    
    $safeKey = md5($key . '_' . getClientIP());
    $filePath = $cacheDir . '/' . $safeKey . '.json';
    
    $data = ['attempts' => 0, 'expires' => time() + $decaySeconds];
    if (file_exists($filePath)) {
        $content = @file_get_contents($filePath);
        if ($content) {
            $parsed = json_decode($content, true);
            if (is_array($parsed) && isset($parsed['expires']) && $parsed['expires'] > time()) {
                $data = $parsed;
            }
        }
    }

    $data['attempts']++;
    @file_put_contents($filePath, json_encode($data), LOCK_EX);

    if ($data['attempts'] > $maxAttempts) {
        Logger::warning("Rate limit exceeded for key: {$key} from IP: " . getClientIP());
        return false;
    }

    return true;
}

function logAudit(?int $userId, bool $isAdmin, string $action, ?string $details = null): void
{
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO audit_logs (user_id, is_admin, action, ip_address, user_agent, details, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $ip = getClientIP();
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250);
        $stmt->execute([$userId, $isAdmin ? 1 : 0, $action, $ip, $ua, $details]);
    } catch (Throwable $e) {
        Logger::error("Audit log failed: " . $e->getMessage());
    }
}
