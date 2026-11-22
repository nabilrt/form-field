<?php
$comparisonSelection = comparison_products();
$categories = q('SELECT * FROM categories ORDER BY id')->fetchAll();
$titles = [
    'home' => 'Objects for a well-lived home',
    'shop' => 'The collection',
    'saved' => 'Saved items',
    'compare' => 'Compare products',
    'set-builder' => 'Build a set',
    'recent' => 'Recently viewed',
    'cart' => 'Your bag',
    'checkout' => 'Checkout',
    'auth' => 'Your account',
    'account' => 'Your account',
    'product' => $product['name'] ?? 'Product',
    'order' => 'Thank you',
    'about' => 'Our approach',
    '404' => 'Page not found',
];
?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width,initial-scale=1" />
        <title><?= e($titles[$page] ?? 'Form & Field') ?> — <?= e(setting('store_name')) ?></title>
        <meta
            name="description"
            content="Thoughtfully chosen homeware, electronics, and everyday essentials. Explore Form & Field, delivered across Bangladesh."
        />
        <link rel="icon" href="/assets/favicon.svg" />
        <link rel="stylesheet" href="/assets/style.css" />
        <script src="/assets/app.js" defer></script>
    </head>
    <body class="store<?= $comparisonSelection && comparison_tray_allowed($page) ? ' compare-active' : '' ?><?= $page === 'set-builder' ? ' set-builder-view' : '' ?>">
        <a class="skip-link" href="#main-content">Skip to content</a>
        <div class="announcement">
            <?= e(setting('announcement')) ?>
            <a href="/shop">Explore the collection <?= icon('arrow', 14) ?></a>
        </div>
        <header class="store-header">
            <button
                class="icon-button mobile-menu"
                data-toggle="store-nav"
                aria-label="Open navigation"
                aria-expanded="false"
            >
                <?= icon('menu') ?>
            </button>
            <a class="brand" href="/" aria-label="Form and Field home">
                form
                <span>&</span>
                field
                <span class="brand-period">.</span>
            </a>
            <nav id="store-nav" class="store-nav" aria-label="Main navigation">
                <a class="<?= $page === 'shop' ? 'active' : '' ?>" href="/shop">Shop all</a>
                <a class="<?= $page === 'set-builder' ? 'active' : '' ?>" href="/build-a-set">Build a set</a>

                <?php
                foreach (array_filter($categories, fn ($c) => in_array($c['slug'], ['living', 'electronics'], true)) as $c):
                ?>
                <a href="/shop?category=<?= e($c['slug']) ?>"><?= e($c['name']) ?></a>

                <?php endforeach; ?>
                <a href="/about">Our approach</a>
            </nav>
            <div class="header-tools">
                <?php if (count(currency_list(true)) > 1): ?>
                <form method="post" class="currency-switcher">
                    <?= csrf() ?>
                    <input type="hidden" name="action" value="set_currency" />
                    <input type="hidden" name="return_to" value="<?= e($_SERVER['REQUEST_URI'] ?? '/') ?>" />
                    <label class="sr-only" for="currency-select">Shopping currency</label>
                    <select id="currency-select" name="currency" data-auto-submit>
                        <?php foreach (currency_list(true) as $c): ?>
                        <option value="<?= e($c['code']) ?>" <?= $c['code'] === current_currency_code() ? 'selected' : '' ?>><?= e($c['code'] . ' ' . $c['symbol']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <noscript><button class="text-button">Change</button></noscript>
                </form>
                <?php endif; ?>
                <button
                    class="icon-button"
                    data-toggle="search-panel"
                    aria-label="Search products"
                    aria-expanded="false"
                >
                    <?= icon('search') ?>
                </button>
                <a
                    class="icon-button saved-link"
                    href="/saved"
                    data-saved-link
                    aria-label="Saved items"
                >
                    <?= icon('heart') ?>
                    <span class="saved-indicator" data-saved-indicator hidden></span>
                </a>
                <a class="icon-button" href="/account" aria-label="Your account">
                    <?= icon('users') ?>
                </a>
                <a href="/cart" class="cart-link" aria-label="Shopping bag, <?= cart_count() ?> items">
                    <?= icon('bag') ?>
                    <span class="cart-count"><?= cart_count() ?></span>
                </a>
            </div>
        </header>
        <form class="search-panel" id="search-panel" action="/shop">
            <label for="search">Search the collection</label>
            <div class="search-input">
                <?= icon('search') ?>
                <input
                    id="search"
                    name="q"
                    autocomplete="off"
                    placeholder="Search furniture, electronics, and essentials…"
                />
                <button class="button dark">Search <?= icon('arrow', 16) ?></button>
            </div>
            <div class="search-suggestions" data-search-results aria-live="polite"></div>
        </form>

        <?php require __DIR__ . '/flash.php'; ?>
        <main id="main-content">
            <?php require __DIR__ . '/' . $page . '.php'; ?>
        </main>

        <?php if (!in_array($page, ['checkout', 'auth'], true)): ?>
        <section class="newsletter">
            <div>
                <h2>From the store, occasionally.</h2>
                <p>New arrivals, store updates, and selected offers.</p>
            </div>
            <form method="post">
                <?= csrf() ?>
                <input type="hidden" name="action" value="subscribe" />
                <label class="sr-only" for="newsletter-email">Your email address</label>
                <input
                    id="newsletter-email"
                    type="email"
                    name="email"
                    placeholder="Your email address"
                    required
                />
                <button aria-label="Subscribe to the newsletter"><?= icon('arrow') ?></button>
            </form>
        </section>

        <?php endif; ?>
        <footer class="store-footer">
            <div class="footer-top">
                <div>
                    <a class="brand" href="/">
                        form
                        <span>&</span>
                        field
                        <span class="brand-period">.</span>
                    </a>
                    <p>
                        Good things for the place
                        <br />
                        you call home.
                    </p>
                </div>
                <div>
                    <h3>The shop</h3>
                    <a href="/shop">All products</a>
                    <a href="/shop?sort=newest">New arrivals</a>
                    <a href="/shop?sale=1">Sale</a>
                </div>
                <div>
                    <h3>Here to help</h3>
                    <a href="/account/orders">Your orders</a>
                    <a href="/about#delivery">Delivery & returns</a>
                    <a href="mailto:<?= e(setting('store_email')) ?>">Get in touch</a>
                </div>
                <div>
                    <h3>Come say hello</h3>
                    <p><?= e(setting('store_address')) ?></p>
                    <a href="mailto:<?= e(setting('store_email')) ?>"><?= e(setting('store_email')) ?></a>
                    <span>Bangladesh · Shopping in <?= e(current_currency_code()) ?></span>
                </div>
            </div>
            <div class="footer-bottom">
                <span>© <?= date('Y') ?> <?= e(setting('store_name')) ?>. Make yourself at home.</span>
                <div>
                    <span>Cash on delivery</span>
                    <span>Bank transfer</span>
                    <a href="/admin">Merchant login <?= icon('external', 12) ?></a>
                </div>
            </div>
        </footer>

        <?php require __DIR__ . '/compare-tray.php'; ?>
    </body>
</html>
