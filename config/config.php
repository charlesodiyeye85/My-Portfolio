<?php
declare(strict_types=1);

if (!defined('PORTFOLIO_ROOT')) {
    define('PORTFOLIO_ROOT', dirname(__DIR__));
}

date_default_timezone_set('Africa/Lagos');

/* ------------------------------------------------------------------
 | Database configuration
 | Default: MySQL (works on every Nigerian cPanel host).
 | Local preview without MySQL: create config/sqlite.txt  (see README)
 | You can also override with environment variables: DB_DRIVER, DB_HOST,
 | DB_NAME, DB_USER, DB_PASS.
 ------------------------------------------------------------------- */
$driver = getenv('DB_DRIVER');
if ($driver === false || $driver === '') {
    $driver = is_file(__DIR__ . '/sqlite.txt') ? 'sqlite' : 'mysql';
}

define('DB_DRIVER', $driver);
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'charles_portfolio');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? (string) getenv('DB_PASS') : '');
define('SQLITE_PATH', __DIR__ . '/portfolio.sqlite');

/* Upload limits (MB) */
define('UPLOAD_MAX_IMAGE_MB', 6);
define('UPLOAD_MAX_VIDEO_MB', 60);
define('UPLOAD_MAX_DOC_MB', 10);

/* Allowed media types */
define('ALLOWED_IMAGE_EXT', ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg']);
define('ALLOWED_VIDEO_EXT', ['mp4', 'webm', 'mov', 'avi', 'mkv']);

/* Demo mode: set to false after editing the login details */
define('DEFAULT_ADMIN_USER', 'admin');
define('DEFAULT_ADMIN_PASS', 'admin123');
