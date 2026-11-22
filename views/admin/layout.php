<?php
header('Cache-Control: private, no-store');
$pending = (int) q("SELECT COUNT(*) FROM orders WHERE status='unfulfilled'")->fetchColumn();
$pendingReturns = returns_available() ? (int) q("SELECT COUNT(*) FROM returns WHERE status='requested'")->fetchColumn() : 0;
$nav = [
    'dashboard' => ['home', 'Home'],
    'orders' => ['orders', 'Orders'],
    'products' => ['box', 'Products'],
    'collections' => ['tag', 'Collections'],
    'customers' => ['users', 'Customers'],
    'returns' => ['return', 'Returns'],
    'discounts' => ['tag', 'Discounts'],
    'currencies' => ['currency', 'Currencies'],
    'reviews' => ['star', 'Reviews'],
    'analytics' => ['chart', 'Analytics'],
    'sliders' => ['image', 'Online store'],
];
$title = $editing ? ($product ? 'Edit product' : 'Add product') : ($viewing ? 'Order details' : ucfirst($section));
?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width,initial-scale=1" />
        <title><?= e($title) ?> — Merchant · <?= e(setting('store_name')) ?></title>
        <link rel="icon" href="/assets/favicon.svg" />
        <link rel="stylesheet" href="/assets/style.css" />
        <link rel="stylesheet" href="/assets/admin.css" />
        <script src="/assets/app.js" defer></script>
    </head>
    <body class="admin-app">
        <header class="admin-topbar">
            <div class="admin-logo">
                <button
                    class="icon-button mobile-menu"
                    data-toggle="admin-sidebar"
                    aria-label="Open merchant navigation"
                    aria-expanded="false"
                >
                    <?= icon('menu') ?>
                </button>
                <a class="brand" href="/admin">
                    form
                    <span>&</span>
                    field.
                </a>
            </div>
            <form class="admin-search" action="/admin/products">
                <?= icon('search', 18) ?>
                <label class="sr-only" for="admin-search">Search products</label>
                <input
                    id="admin-search"
                    name="q"
                    placeholder="Search your store"
                    value="<?= e($_GET['q'] ?? '') ?>"
                />
                <kbd>/</kbd>
            </form>
            <div class="admin-topbar-end">
                <a href="/" target="_blank" class="view-store">View store <?= icon('external', 14) ?></a>
                <span class="avatar">FF</span>
                <span class="owner-name"><?= e(setting('store_name')) ?></span>
            </div>
        </header>
        <aside class="admin-sidebar" id="admin-sidebar">
            <div class="store-switcher">
                <span class="store-monogram">f.</span>
                <div>
                    <strong><?= e(setting('store_name')) ?></strong>
                    <small>Merchant account</small>
                </div>
            </div>
            <nav aria-label="Merchant navigation">
                <?php foreach ($nav as $key => $link): ?>
                <a href="/admin<?= $key === 'dashboard' ? '' : '/' . $key ?>" class="<?= $section === $key ? 'active' : '' ?>">
                    <?= icon($link[0], 19) ?>
                    <span><?= $link[1] ?></span>

                    <?php if ($key === 'returns' && $pendingReturns): ?>
                    <span class="nav-count"><?= $pendingReturns ?></span>
                    <?php endif; ?>

                    <?php if ($key === 'orders' && $pending): ?>
                    <span class="nav-count"><?= $pending ?></span>

                    <?php endif; ?>
                </a>

                <?php endforeach; ?>
            </nav>
            <div class="sidebar-bottom">
                <a href="/" class="store-preview">
                    <?= icon('external', 18) ?>
                    <span>Visit your storefront</span>
                </a>
                <a href="/admin/settings" class="<?= $section === 'settings' ? 'active' : '' ?>"><?= icon('settings', 19) ?> Settings</a>
                <form method="post">
                    <?= csrf() ?>
                    <input type="hidden" name="action" value="logout" />
                    <button class="text-button"><?= icon('logout', 18) ?> Sign out</button>
                </form>
                <small>Form & Field Commerce</small>
            </div>
        </aside>
        <main class="admin-main">
            <div class="admin-content">
                <?php require __DIR__ . '/../flash.php'; ?>

                <?php
                require __DIR__ . '/' . ($editing ? 'product-edit' : ($viewing ? 'order-view' : $section)) . '.php';
                ?>
            </div>
            <footer class="admin-footer">
                Form & Field Commerce
                <span><?= e(setting('store_name')) ?> · Merchant workspace</span>
            </footer>
        </main>
    </body>
</html>
