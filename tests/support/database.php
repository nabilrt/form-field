<?php

/** Infrastructure replacement used only in the isolated integration-test copy. */
function db(): PDO
{
    static $pdo;

    if (!$pdo) {
        $pdo = new PDO('sqlite:' . __DIR__ . '/../storage/test.sqlite', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys=ON');
        $pdo->sqliteCreateFunction('NOW', fn () => date('Y-m-d H:i:s'));
        $pdo->sqliteCreateFunction('CURDATE', fn () => date('Y-m-d'));
        $pdo->sqliteCreateFunction('CONCAT', fn (...$values) => implode('', $values));
        $pdo->sqliteCreateFunction('IF', fn ($condition, $yes, $no) => $condition ? $yes : $no);
        $pdo->sqliteCreateFunction('DATE_FORMAT', fn ($date, $format) => date('Y-m', strtotime($date)));
    }

    return $pdo;
}

function translate_test_sql(string $sql): string
{
    $sql = preg_replace(
        "/SELECT 1 FROM information_schema\\.tables WHERE table_schema = DATABASE\\(\\) AND table_name = '(\\w+)'/",
        "SELECT 1 FROM sqlite_master WHERE type='table' AND name='$1'",
        $sql
    );
    $sql = preg_replace('/ FOR UPDATE\b/i', '', $sql);
    $sql = str_replace('INSERT IGNORE', 'INSERT OR IGNORE', $sql);
    $sql = preg_replace('/CURDATE\(\)\s*-\s*INTERVAL\s+(\d+)\s+DAY/i', "date('now','-$1 days')", $sql);
    $sql = str_replace('DATE_SUB(NOW(),INTERVAL 15 MINUTE)', "datetime('now','-15 minutes')", $sql);

    if (str_contains($sql, 'ON DUPLICATE KEY UPDATE')) {
        $key = str_contains($sql, 'login_attempts') ? 'ip_hash' : '`key`';
        $sql = str_replace('ON DUPLICATE KEY UPDATE', 'ON CONFLICT(' . $key . ') DO UPDATE SET', $sql);
        $sql = str_replace('VALUES(value)', 'excluded.value', $sql);
    }

    return $sql;
}
