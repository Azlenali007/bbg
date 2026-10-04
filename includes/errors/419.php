<?php
require_once __DIR__ . '/render.php';
renderErrorPage(419, 'Session Expired', 'Your security token or session has expired. Please refresh the page and submit again.');
