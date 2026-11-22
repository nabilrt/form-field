<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

require __DIR__ . '/../app/bootstrap.php';
try {
    $has = static fn (string $table, string $column): bool => (bool) q(
        'SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=?',
        [$table, $column]
    )->fetchColumn();
    if (!$has('coupons', 'max_uses')) {
        db()->exec('ALTER TABLE coupons ADD max_uses INT UNSIGNED NULL AFTER expires_at');
    }
    if (!$has('coupons', 'per_customer')) {
        db()->exec('ALTER TABLE coupons ADD per_customer INT UNSIGNED NULL AFTER max_uses');
    }
    if (!q("SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='orders' AND index_name='idx_orders_coupon'")->fetchColumn()) {
        db()->exec('ALTER TABLE orders ADD INDEX idx_orders_coupon (coupon)');
    }
    echo "Coupon usage limits are ready. Existing codes stay unlimited until you set a limit.\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'Coupon migration failed: ' . $error->getMessage() . "\n");
    exit(1);
}
