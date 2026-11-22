<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

require __DIR__ . '/../app/bootstrap.php';
try {
    $schema = file_get_contents(__DIR__ . '/../database/schema.sql');
    foreach (['returns', 'return_items'] as $table) {
        if (!preg_match('/CREATE TABLE IF NOT EXISTS ' . $table . '\s*\(.*?;/s', $schema, $match)) {
            throw new RuntimeException("The $table table definition was not found.");
        }
        db()->exec($match[0]);
    }
    q('INSERT IGNORE INTO settings(`key`,value) VALUES(?,?)', ['return_window_days', '30']);
    echo "Returns are ready. Shoppers can request returns on delivered orders for 30 days (change it under Settings → Delivery & tax).\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'Returns migration failed: ' . $error->getMessage() . "\n");
    exit(1);
}
