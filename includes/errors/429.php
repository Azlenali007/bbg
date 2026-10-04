<?php
require_once __DIR__ . '/render.php';
renderErrorPage(429, 'Too Many Requests', 'Rate limit exceeded. You have made too many requests in a short period. Please wait a moment and try again.');
