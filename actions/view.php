<?php
declare(strict_types=1);

/**
 * Public view counter. Called by assets/js/main.js with {type, id}.
 * Throttled per session so a refresh loop cannot inflate the numbers.
 */

require_once dirname(__DIR__) . '/config/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$type = (string) ($_POST['type'] ?? $_GET['type'] ?? '');
$id   = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);

$tables = ['post' => 'posts', 'work' => 'works'];

if ($id <= 0 || !isset($tables[$type])) {
    http_response_code(400);
    echo json_encode(['ok' => false]);
    exit;
}

$table = $tables[$type];

// One counted view per item per viewer every 30 minutes.
$seenKey = 'viewed_' . $type . '_' . $id;
$seen    = $_SESSION[$seenKey] ?? 0;
if (time() - (int) $seen < 1800) {
    echo json_encode(['ok' => true, 'counted' => false]);
    exit;
}

try {
    q("UPDATE {$table} SET views = views + 1 WHERE id = :id", [':id' => $id]);
    $_SESSION[$seenKey] = time();
    echo json_encode(['ok' => true, 'counted' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false]);
}
