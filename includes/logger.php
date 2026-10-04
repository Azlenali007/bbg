<?php
declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

class Logger
{
    private static string $logDir = BASE_PATH . '/storage/logs';

    public static function log(string $level, string $message, array $context = []): void
    {
        if (!is_dir(self::$logDir)) {
            @mkdir(self::$logDir, 0755, true);
        }

        $logFile = self::$logDir . '/app-' . date('Y-m-d') . '.log';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        $userId = $_SESSION['user_id'] ?? ($_SESSION['admin_id'] ?? 'guest');
        
        $sanitizedContext = self::sanitize($context);
        $contextString = !empty($sanitizedContext) ? ' | ' . json_encode($sanitizedContext, JSON_UNESCAPED_SLASHES) : '';
        
        $entry = sprintf(
            "[%s] [%s] [IP: %s] [UID: %s] %s%s%s",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $ip,
            $userId,
            $message,
            $contextString,
            PHP_EOL
        );

        @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        self::log('CRITICAL', $message, $context);
    }

    private static function sanitize(array $data): array
    {
        $sensitiveKeys = ['password', 'pass', 'api_key', 'apikey', 'secret', 'token', 'authorization', 'card', 'cvv'];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::sanitize($value);
            } elseif (in_array(strtolower((string)$key), $sensitiveKeys, true)) {
                $data[$key] = '***REDACTED***';
            }
        }
        return $data;
    }
}
