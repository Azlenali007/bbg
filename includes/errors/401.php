<?php
require_once __DIR__ . '/render.php';
renderErrorPage(401, 'Unauthorized Access', 'You must be logged in with valid credentials to view this resource.');
