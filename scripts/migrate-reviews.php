<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

require __DIR__ . '/../app/bootstrap.php';
try {
    $schema = file_get_contents(__DIR__ . '/../database/schema.sql');
    if (!preg_match('/CREATE TABLE IF NOT EXISTS product_reviews\s*\(.*?;/s', $schema, $match)) {
        throw new RuntimeException('The product review table definition was not found.');
    }
    db()->exec($match[0]);
    echo "Product review storage is ready. Existing products, customers, and orders are unchanged.\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'Review migration failed: ' . $error->getMessage() . "\n");
    exit(1);
}
