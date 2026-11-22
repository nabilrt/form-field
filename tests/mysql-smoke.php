<?php

/** Read-only native MySQL smoke check. Run after scripts/install.php. */
if (PHP_SAPI !== 'cli') {
    exit;
}

require __DIR__ . '/../app/bootstrap.php';
try {
    $version = q('SELECT VERSION()')->fetchColumn();
    foreach ([
        'admins',
        'settings',
        'categories',
        'products',
        'product_reviews',
        'variants',
        'product_images',
        'sliders',
        'coupons',
        'orders',
        'order_items',
        'customers',
        'addresses',
        'subscribers',
        'login_attempts',
    ] as $table) {
        $count = q('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn();
        echo "OK $table ($count rows)\n";
    }

    $items = products();
    echo 'OK catalog query (' . count($items) . " products)\n";
    db()->beginTransaction();
    q('SELECT * FROM variants ORDER BY id LIMIT 1 FOR UPDATE')->fetch();
    db()->rollBack();
    echo "OK InnoDB transaction and row lock\nNative MySQL smoke checks passed ($version).\n";
} catch (Throwable $e) {
    if (isset($GLOBALS['pdo']) && db()->inTransaction()) {
        db()->rollBack();
    }
    fwrite(STDERR, 'FAIL ' . $e->getMessage() . "\n");
    exit(1);
}
