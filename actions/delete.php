<?php
declare(strict_types=1);

/** Authenticated delete endpoint. POST + CSRF, identified by ?type=&id= */

require_once __DIR__ . '/_boot.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('Method not allowed.', 405);
}
if (!verify_csrf()) {
    fail('Session expired. Please refresh and try again.', 419);
}

$type = arg('type');
$id   = arg_id();

if ($id <= 0) {
    fail('Missing record id.');
}

switch ($type) {
    case 'work':
        $item = row('SELECT id, image FROM works WHERE id = :id', [':id' => $id]);
        if (!$item) {
            fail('Work not found.', 404);
        }
        q('DELETE FROM works WHERE id = :id', [':id' => $id]);
        delete_media_file($item['image'] ?: null);
        out(['ok' => true, 'message' => 'Work deleted.']);

    case 'post':
        $item = row('SELECT id, slug, cover_image FROM posts WHERE id = :id', [':id' => $id]);
        if (!$item) {
            fail('Post not found.', 404);
        }
        q('DELETE FROM posts WHERE id = :id', [':id' => $id]);
        delete_media_file($item['cover_image'] ?: null);
        out(['ok' => true, 'message' => 'Post deleted.']);

    case 'media':
        $item = row('SELECT id, file_path FROM media WHERE id = :id', [':id' => $id]);
        if (!$item) {
            fail('Media not found.', 404);
        }
        $path = (string) $item['file_path'];
        $used = (int) scalar('SELECT COUNT(*) FROM works WHERE image = :p', [':p' => $path], 0)
              + (int) scalar('SELECT COUNT(*) FROM posts WHERE cover_image = :p', [':p' => $path], 0)
              + (int) scalar("SELECT COUNT(*) FROM settings WHERE name IN ('profile_photo','hero_image') AND value = :p", [':p' => $path], 0);
        if ($used > 0) {
            fail('That file is still used by ' . $used . ' item' . ($used === 1 ? '' : 's') . ' of your content. Remove or replace it there first.', 409);
        }
        delete_media_file($path);
        out(['ok' => true, 'message' => 'File removed.']);

    case 'message':
        $item = row('SELECT id FROM messages WHERE id = :id', [':id' => $id]);
        if (!$item) {
            fail('Message not found.', 404);
        }
        q('DELETE FROM messages WHERE id = :id', [':id' => $id]);
        out(['ok' => true, 'message' => 'Message deleted.']);

    default:
        fail('Unknown delete type.');
}
