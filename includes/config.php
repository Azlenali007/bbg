<?php
declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/includes/logger.php';
require_once BASE_PATH . '/includes/errors/render.php';

// Parse .env if exists
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            [$key, $val] = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val, " \t\n\r\0\x0B\"'");
            if (!isset($_ENV[$key])) {
                $_ENV[$key] = $val;
                putenv("{$key}={$val}");
            }
        }
    }
}

// Config constants
if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
if (!defined('DB_PORT')) define('DB_PORT', (int)(getenv('DB_PORT') ?: 3306));
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'smm_panel');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'smm_user');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') ?: 'smm_pass123');

if (!defined('APP_ENV')) define('APP_ENV', getenv('APP_ENV') ?: 'production');
if (!defined('APP_DEBUG')) define('APP_DEBUG', filter_var(getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOLEAN));
if (!defined('APP_NAME')) define('APP_NAME', getenv('APP_NAME') ?: 'ApexSMM');
if (!defined('APP_URL')) define('APP_URL', rtrim(getenv('APP_URL') ?: '', '/'));
if (!defined('CURRENCY')) define('CURRENCY', getenv('CURRENCY') ?: 'USD');
if (!defined('CURRENCY_SYMBOL')) define('CURRENCY_SYMBOL', getenv('CURRENCY_SYMBOL') ?: '$');
if (!defined('TIMEZONE')) define('TIMEZONE', getenv('TIMEZONE') ?: 'UTC');
if (!defined('SESSION_LIFETIME')) define('SESSION_LIFETIME', (int)(getenv('SESSION_LIFETIME') ?: 86400));

date_default_timezone_set(TIMEZONE);

// Error & Exception handling - White page prevention
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    Logger::error("PHP Error [{$errno}]: {$errstr} in {$errfile}:{$errline}");
    if (APP_DEBUG) {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }
    return true;
});

set_exception_handler(function (Throwable $e) {
    Logger::critical("Uncaught Exception: " . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    if (APP_DEBUG) {
        renderErrorPage(500, 'Exception: ' . get_class($e), $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    } else {
        renderErrorPage(500, 'Internal Server Error', 'An unexpected error occurred while processing your request. Please try again later.');
    }
});

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        Logger::critical("Fatal Shutdown: {$error['message']} in {$error['file']}:{$error['line']}");
        if (!headers_sent()) {
            renderErrorPage(500, 'Fatal System Error', 'A critical fatal error occurred. Please contact the administrator.');
        }
    }
});
