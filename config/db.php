<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    if (DB_DRIVER === 'sqlite') {
        $pdo = new PDO('sqlite:' . SQLITE_PATH, null, null, $options);
        $pdo->exec('PRAGMA journal_mode = WAL');
        $pdo->exec('PRAGMA foreign_keys = ON');
    } else {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            echo '<h2>Database connection failed</h2>';
            echo '<p>Check the credentials in <code>config/config.php</code>.</p>';
            echo '<p>Details: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES) . '</p>';
            echo '<p>See README.md for cPanel setup instructions (Whogohost, HostNowNow, Qservers, Truehost, etc.).</p>';
            exit;
        }
    }

    return $pdo;
}

/** Run a query and return the statement. */
function q(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/** Fetch all rows. */
function rows(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}

/** Fetch one row or null. */
function row(string $sql, array $params = []): ?array
{
    $r = q($sql, $params)->fetch();
    return $r === false ? null : $r;
}

/** Fetch single scalar value. */
function scalar(string $sql, array $params = [], mixed $default = null): mixed
{
    $v = q($sql, $params)->fetchColumn();
    return $v === false ? $default : $v;
}

/** Insert and return the new id. */
function insert(string $table, array $data): int
{
    $cols = array_keys($data);
    $sql = sprintf(
        'INSERT INTO %s (%s) VALUES (%s)',
        $table,
        implode(', ', $cols),
        implode(', ', array_map(fn($c) => ':' . $c, $cols))
    );
    q($sql, $data);
    return (int) db()->lastInsertId();
}

/** Update rows by id. */
function update_by_id(string $table, int $id, array $data): void
{
    if (!$data) {
        return;
    }
    $sets = implode(', ', array_map(fn($c) => $c . ' = :' . $c, array_keys($data)));
    q("UPDATE {$table} SET {$sets} WHERE id = :__id", $data + ['__id' => $id]);
}
