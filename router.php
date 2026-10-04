<?php
// Simple router for PHP built-in web server
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = __DIR__ . $uri;

if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    return false; // serve the requested resource as-is
}

if ($uri === '/') {
    require __DIR__ . '/index.php';
    exit;
}

if (file_exists($file . '.php')) {
    require $file . '.php';
    exit;
}

if (is_dir($file) && file_exists($file . '/index.php')) {
    require $file . '/index.php';
    exit;
}

if (file_exists($file)) {
    return false;
}

// 404 handler
http_response_code(404);
require __DIR__ . '/includes/errors/404.php';
exit;
