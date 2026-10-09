<?php
declare(strict_types=1);

/**
 * Authenticated inline toggle. POST + CSRF with {value}.
 * Usage: actions/toggle.php?type=work&field=status&id=3
 */

require_once __DIR__ . '/_boot.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('Method not allowed.', 405);
}
if (!verify_csrf()) {
    fail('Session expired. Please refresh and try again.', 419);
}

$type  = arg('type');
$field = arg('field');
$id    = arg_id();
$value = arg('value');

$allowed = [
    'work' => [
        'table'      => 'works',
        'fields'     => ['status' => ['published', 'draft'], 'is_featured' => ['0', '1']],
        'cast'       => ['is_featured' => 'int'],
    ],
    'post' => [
        'table'      => 'posts',
        'fields'     => ['status' => ['published', 'draft']],
        'cast'       => [],
    ],
    'message' => [
        'table'      => 'messages',
        'fields'     => ['is_read' => ['0', '1']],
        'cast'       => ['is_read' => 'int'],
    ],
];

if (!isset($allowed[$type])) {
    fail('Unknown toggle type.');
}

$rule = $allowed[$type];

if (!isset($rule['fields'][$field])) {
    fail('That field cannot be toggled.');
}
if (!in_array($value, $rule['fields'][$field], true)) {
    fail('Invalid value for ' . $field . '.');
}
if ($id <= 0) {
    fail('Missing record id.');
}

$data = [$field => $value];
if (($rule['cast'][$field] ?? '') === 'int') {
    $data[$field] = (int) $value;
}

update_by_id($rule['table'], $id, $data);

$labels = [
    'status'      => $value === 'published' ? 'Published' : 'Unpublished',
    'is_featured' => $value === '1' ? 'Marked as featured' : 'Removed from featured',
    'is_read'     => $value === '1' ? 'Marked as read' : 'Marked as unread',
];

out(['ok' => true, 'message' => $labels[$field] ?? 'Updated.']);
