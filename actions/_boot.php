<?php
declare(strict_types=1);

require_once __DIR__ . '/../admin/includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');

function out(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function fail(string $message, int $status = 400): never
{
    out(['ok' => false, 'error' => $message], $status);
}

/** Read an integer id from the query string (POST endpoints). */
function arg_id(): int
{
    return (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
}

function arg(string $key, string $default = ''): string
{
    $v = $_GET[$key] ?? $_POST[$key] ?? $default;
    return is_string($v) ? $v : $default;
}
