<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/schema.php';

/* ---------------- Session ---------------- */

if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/* ---------------- URL helpers ---------------- */

/** Base path of the app (handles sub-folder installs). */
function app_base(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/');
    $dir = dirname($script);
    $dir = str_replace('\\', '/', $dir);
    $dir = preg_replace('#/(admin|actions)$#', '', $dir) ?? $dir;
    if ($dir === '' || $dir === '.') {
        $dir = '/';
    }
    $base = rtrim($dir, '/') . '/';
    return $base;
}

function url(string $path = ''): string
{
    return app_base() . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . (str_starts_with($path, 'http') ? $path : url($path)));
    exit;
}

/* ---------------- Output escaping ---------------- */

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/* ---------------- Settings ---------------- */

function all_settings(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $cache = [];
    foreach (rows('SELECT name, value FROM settings') as $r) {
        $cache[$r['name']] = $r['value'];
    }
    return $cache;
}

function setting(string $key, string $default = ''): string
{
    $all = all_settings();
    return isset($all[$key]) && $all[$key] !== '' ? $all[$key] : $default;
}

function save_setting(string $key, string $value): void
{
    if (DB_DRIVER === 'sqlite') {
        q('INSERT INTO settings (name, value) VALUES (:n, :v)
           ON CONFLICT(name) DO UPDATE SET value = excluded.value', [':n' => $key, ':v' => $value]);
    } else {
        q('INSERT INTO settings (name, value) VALUES (:n, :v)
           ON DUPLICATE KEY UPDATE value = VALUES(value)', [':n' => $key, ':v' => $value]);
    }
}

/* ---------------- CSRF / flash ---------------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(24));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): bool
{
    $token = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'][] = ['message' => $message, 'type' => $type];
}

function get_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/* ---------------- Slugs / text ---------------- */

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    $text = trim($text, '-');
    return $text !== '' ? $text : 'item-' . time();
}

function unique_slug(string $table, string $slug, int $ignoreId = 0): string
{
    $base = $slug;
    $i = 2;
    while (true) {
        $sql = "SELECT COUNT(*) FROM {$table} WHERE slug = :s" . ($ignoreId ? ' AND id <> :id' : '');
        $params = [':s' => $slug] + ($ignoreId ? [':id' => $ignoreId] : []);
        if ((int) scalar($sql, $params, 0) === 0) {
            return $slug;
        }
        $slug = $base . '-' . $i++;
        if ($i > 200) {
            return $base . '-' . time();
        }
    }
}

function excerpt_text(string $html, int $length = 160): string
{
    $text = trim(preg_replace('/\s+/', ' ', strip_tags($html)) ?? '');
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return rtrim(mb_substr($text, 0, $length)) . '…';
}

function format_date(?string $date, string $format = 'M j, Y'): string
{
    if (!$date) {
        return '';
    }
    $ts = strtotime($date);
    return $ts ? date($format, $ts) : '';
}

/** Basic whitelist sanitiser for admin-authored HTML. */
function sanitize_html(string $html): string
{
    $allowed = '<p><br><strong><b><em><i><u><s><h1><h2><h3><h4><ul><ol><li><a><blockquote><code><pre><hr><img><figure><figcaption><table><thead><tbody><tr><th><td><span><div>';
    $clean = strip_tags($html, $allowed);

    // Remove dangerous attributes / javascript: urls
    $clean = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean) ?? $clean;
    $clean = preg_replace('/(href|src)\s*=\s*"\s*javascript:[^"]*"/i', '$1="#"', $clean) ?? $clean;
    $clean = preg_replace('/(href|src)\s*=\s*\'\s*javascript:[^\']*\'/i', '$1="#"', $clean) ?? $clean;

    return $clean;
}

/* ---------------- Uploads ---------------- */

/**
 * Save an uploaded file. Returns the relative path (e.g. uploads/works/x.jpg)
 * or null when no file was supplied. Throws RuntimeException on invalid files.
 */
function save_upload(array $file, string $subdir, int $maxMb, array $allowedExt): ?string
{
    if (!isset($file['error']) || is_array($file['error'])) {
        return null;
    }
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed (error code ' . $file['error'] . '). Check upload_max_filesize in php.ini.');
    }
    if ($file['size'] > $maxMb * 1024 * 1024) {
        throw new RuntimeException("File is larger than {$maxMb} MB.");
    }

    $name = (string) $file['name'];
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        throw new RuntimeException('File type not allowed. Allowed: ' . implode(', ', $allowedExt));
    }

    // Verify real MIME when finfo is available (skip for SVG text)
    $realMime = null;
    if (function_exists('finfo_open') && $ext !== 'svg') {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $realMime = finfo_file($finfo, $file['tmp_name']) ?: null;
            finfo_close($finfo);
        }
        $expected = [
            'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
            'webp' => 'image/webp', 'gif' => 'image/gif',
            'mp4' => 'video/mp4', 'webm' => 'video/webm', 'mov' => 'video/quicktime',
        ];
        if ($realMime && isset($expected[$ext]) && $realMime !== $expected[$ext]) {
            throw new RuntimeException('File content does not match its extension.');
        }
    }

    $safeBase = preg_replace('/[^a-zA-Z0-9_-]+/', '-', pathinfo($name, PATHINFO_FILENAME)) ?: 'file';
    $filename = strtolower($safeBase) . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(3)) . '.' . $ext;

    $dir = PORTFOLIO_ROOT . '/uploads/' . $subdir;
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Could not create upload directory.');
    }

    $dest = $dir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Could not save the uploaded file.');
    }
    @chmod($dest, 0644);

    $rel = 'uploads/' . $subdir . '/' . $filename;

    try {
        insert('media', [
            'file_path' => $rel,
            'file_name' => $name,
            'mime' => $realMime ?: ($file['type'] ?? ''),
            'size_kb' => (int) round($file['size'] / 1024),
        ]);
    } catch (Throwable $e) {
        // media logging must never break a save
    }

    return $rel;
}

function delete_media_file(?string $relPath): void
{
    if (!$relPath) {
        return;
    }
    $rel = ltrim(str_replace(['..', "\0"], '', $relPath), '/');
    if (!str_starts_with($rel, 'uploads/')) {
        return; // only allow deleting inside uploads/
    }
    $full = PORTFOLIO_ROOT . '/' . $rel;
    if (is_file($full)) {
        @unlink($full);
    }
    q('DELETE FROM media WHERE file_path = :p', [':p' => $rel]);
}

/* ---------------- Content queries ---------------- */

function work_categories(): array
{
    return [
        'content-social' => 'Content & Social Media',
        'jujutsu'        => 'Jujutsu Content',
        'radiography'    => 'Radiography',
        'video'          => 'Video Editing',
        'geographic'     => 'Geographic Design',
    ];
}

function category_label(string $key): string
{
    $cats = work_categories();
    return $cats[$key] ?? ucfirst(str_replace('-', ' ', $key));
}

function category_icon(string $key): string
{
    return match ($key) {
        'content-social' => 'bi-megaphone-fill',
        'jujutsu'        => 'bi-lightning-charge-fill',
        'radiography'    => 'bi-clipboard2-pulse-fill',
        'video'          => 'bi-film',
        'geographic'     => 'bi-globe2',
        default          => 'bi-grid',
    };
}

function get_published_works(?string $category = null): array
{
    $sql = "SELECT * FROM works WHERE status = 'published'";
    $params = [];
    if ($category && $category !== 'all') {
        $sql .= ' AND category = :c';
        $params[':c'] = $category;
    }
    $sql .= ' ORDER BY is_featured DESC, created_at DESC';
    return rows($sql, $params);
}

function get_published_posts(int $limit = 6): array
{
    return rows(
        "SELECT * FROM posts WHERE status = 'published' ORDER BY published_at DESC LIMIT " . max(1, $limit)
    );
}

function get_post_by_slug(string $slug): ?array
{
    return row("SELECT * FROM posts WHERE slug = :s AND status = 'published'", [':s' => $slug]);
}

/* ---------------- Auth ---------------- */

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    static $user = false;
    if ($user === false) {
        $user = row('SELECT * FROM users WHERE id = :id', [':id' => (int) $_SESSION['user_id']]);
    }
    return $user;
}

function require_admin(): array
{
    $user = current_user();
    if (!$user) {
        redirect('admin/login.php');
    }
    return $user;
}

/* ---------------- Bootstrap everything ---------------- */

ensure_schema();
