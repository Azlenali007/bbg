<?php
require_once __DIR__ . '/render.php';
renderErrorPage(400, 'Bad Request', 'The request could not be understood or is missing required parameters.');
