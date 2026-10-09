<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once dirname(__DIR__, 2) . '/config/functions.php';

if (isset($_GET['logout'])) {
    session_destroy();
    redirect('admin/login.php');
}
