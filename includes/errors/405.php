<?php
require_once __DIR__ . '/render.php';
renderErrorPage(405, 'Method Not Allowed', 'The HTTP method used for this request is not supported by the endpoint.');
