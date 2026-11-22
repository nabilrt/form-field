<?php

if ($path === '/admin/login') {
    if (admin()) {
        redirect('/admin');
    }
    require __DIR__ . '/../views/admin/login.php';
    return;
}

require_admin();
// Merchant screens work in the base currency; order screens pass each order's own currency.
current_currency_code('BDT');
if ($path === '/admin/orders/export') {
    require __DIR__ . '/export-orders.php';
    return;
}
$section = explode('/', $path)[2] ?? 'dashboard';
if (!$section) {
    $section = 'dashboard';
}

$allowed = ['dashboard', 'products', 'orders', 'customers', 'collections', 'sliders', 'discounts', 'reviews', 'analytics', 'currencies', 'settings'];
if (!in_array($section, $allowed, true)) {
    http_response_code(404);
    $section = '404';
}

$editing = $path === '/admin/products/edit';
$viewing = $path === '/admin/orders/view';
if ($editing) {
    $id = (int) ($_GET['id'] ?? 0);
    $product = $id ? q('SELECT * FROM products WHERE id=?', [$id])->fetch() : null;
    if ($id && !$product) {
        redirect('/admin/products');
    }
    $variants = $id ? q('SELECT * FROM variants WHERE product_id=? ORDER BY id', [$id])->fetchAll() : [
        [
            'id' => '',
            'sku' => '',
            'color' => 'Natural',
            'size' => 'Standard',
            'price' => '',
            'compare_price' => '',
            'stock' => '0',
        ],
    ];
    $images = $id ? q('SELECT * FROM product_images WHERE product_id=? ORDER BY position,id', [$id])->fetchAll() : [];
}

if ($viewing) {
    $order = q('SELECT * FROM orders WHERE id=?', [(int) ($_GET['id'] ?? 0)])->fetch();
    if (!$order) {
        redirect('/admin/orders');
    }
    $orderItems = q('SELECT * FROM order_items WHERE order_id=?', [$order['id']])->fetchAll();
}

require __DIR__ . '/../views/admin/layout.php';
