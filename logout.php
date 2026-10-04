<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

Auth::logout();
flash('success', 'You have been successfully signed out.');
header('Location: /login.php');
exit;
