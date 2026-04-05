<?php
require_once __DIR__ . '/../config.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }
    return $pdo;
}

function dbQuery(string $sql, array $params = []): PDOStatement {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function dbFetch(string $sql, array $params = []): ?array {
    return dbQuery($sql, $params)->fetch() ?: null;
}

function dbFetchAll(string $sql, array $params = []): array {
    return dbQuery($sql, $params)->fetchAll();
}

function dbInsert(string $table, array $data): int {
    $cols = implode(',', array_keys($data));
    $vals = implode(',', array_fill(0, count($data), '?'));
    dbQuery("INSERT INTO $table ($cols) VALUES ($vals)", array_values($data));
    return (int) db()->lastInsertId();
}

function dbUpdate(string $table, array $data, string $where, array $whereParams = []): void {
    $set = implode(',', array_map(fn($k) => "$k=?", array_keys($data)));
    dbQuery("UPDATE $table SET $set WHERE $where", [...array_values($data), ...$whereParams]);
}
