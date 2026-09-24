<?php
declare(strict_types=1);

$config = require __DIR__ . '/../config/app.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name($config['session_name']);
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    ]);
    session_start();
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Migrations.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/CartService.php';
require_once __DIR__ . '/OrderService.php';

Migrations::run();
