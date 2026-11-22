<?php

require __DIR__ . '/../app/bootstrap.php';
try {
    db();
} catch (Throwable $error) {
    http_response_code(503);
    require __DIR__ . '/../views/setup.php';
    exit;
}

$path = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/', '/') ?: '/';
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        require __DIR__ . '/../app/actions.php';
    }

    if (str_starts_with($path, '/admin')) {
        require __DIR__ . '/../app/admin.php';
        exit;
    }

    if (str_starts_with($path, '/account')) {
        require __DIR__ . '/../app/account.php';
        exit;
    }

    if ($path === '/invoice') {
        require __DIR__ . '/../views/invoice.php';
        exit;
    }

    $page = match (true) {
        $path === '/' => 'home',
        $path === '/shop' => 'shop',
        $path === '/saved' => 'saved',
        $path === '/compare' => 'compare',
        $path === '/build-a-set' => 'set-builder',
        $path === '/recent' => 'recent',
        str_starts_with($path, '/product/') => 'product',
        $path === '/cart' => 'cart',
        $path === '/checkout' => 'checkout',
        $path === '/order' => 'order',
        $path === '/about' => 'about',
        default => '404',
    };
    if ($page === '404') {
        http_response_code(404);
    }

    if ($page === 'checkout' && !cart_items()) {
        redirect('/cart');
    }

    if ($page === 'product') {
        $product = products('p.slug=? AND p.active=1', [urldecode(substr($path, 9))])[0] ?? null;
        if (!$product) {
            $page = '404';
            http_response_code(404);
        } else {
            $variants = q('SELECT * FROM variants WHERE product_id=? ORDER BY id', [$product['id']])->fetchAll();
            $images = q('SELECT * FROM product_images WHERE product_id=? ORDER BY position,id', [$product['id']])->fetchAll();
            $reviewData = product_review_data((int) $product['id'], $product);
            remember_product((int) $product['id']);
        }
    }

    if ($page === 'order') {
        $order = q('SELECT * FROM orders WHERE token=?', [$_GET['token'] ?? ''])->fetch();
        if (!$order) {
            http_response_code(404);
            $page = '404';
        }
    }

    require __DIR__ . '/../views/store-layout.php';
} catch (Throwable $error) {
    error_log((string) $error);
    http_response_code(500);
    echo '<h1>We couldn’t load this page.</h1><p>Please try again in a moment.</p><a href="/">Return to the store</a>';
}
