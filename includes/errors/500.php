<?php
require_once __DIR__ . '/render.php';
renderErrorPage(500, 'Internal Server Error', 'An unexpected error occurred while processing your request. Our technical staff has been notified.');
