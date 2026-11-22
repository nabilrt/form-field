<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

require __DIR__ . '/../app/bootstrap.php';
try {
    $schema = file_get_contents(__DIR__ . '/../database/schema.sql');
    foreach (['currencies', 'variant_prices'] as $table) {
        if (!preg_match('/CREATE TABLE IF NOT EXISTS ' . $table . '\s*\(.*?;/s', $schema, $match)) {
            throw new RuntimeException("The $table table definition was not found.");
        }
        db()->exec($match[0]);
    }
    $has = static fn (string $column): bool => (bool) q(
        "SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='orders' AND column_name=?",
        [$column]
    )->fetchColumn();
    if (!$has('currency')) {
        db()->exec("ALTER TABLE orders ADD currency CHAR(3) NOT NULL DEFAULT 'BDT' AFTER coupon");
    }
    if (!$has('rate_to_base')) {
        db()->exec('ALTER TABLE orders ADD rate_to_base DECIMAL(18,8) NOT NULL DEFAULT 1 AFTER currency');
    }
    echo "Currencies are ready. Existing orders are recorded as BDT. Add currencies under Admin → Currencies.\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'Currency migration failed: ' . $error->getMessage() . "\n");
    exit(1);
}
