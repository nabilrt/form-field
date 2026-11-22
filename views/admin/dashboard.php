<?php
$stats = q(
    "SELECT COUNT(*) orders,COALESCE(SUM(CASE WHEN status<>'cancelled' THEN total*rate_to_base ELSE 0 END),0) revenue,COALESCE(AVG(CASE WHEN status<>'cancelled' THEN total*rate_to_base END),0) average FROM orders WHERE created_at>=CURDATE()-INTERVAL 29 DAY"
)->fetch();
$productCount = (int) q('SELECT COUNT(*) FROM products WHERE active=1')->fetchColumn();
$customerCount = (int) q('SELECT COUNT(*) FROM customers')->fetchColumn();
$recent = q('SELECT * FROM orders ORDER BY id DESC LIMIT 5')->fetchAll();
$days = q(
    "SELECT DATE(created_at) day,SUM(total*rate_to_base) total FROM orders WHERE created_at>=CURDATE()-INTERVAL 6 DAY AND status<>'cancelled' GROUP BY DATE(created_at)"
)->fetchAll(PDO::FETCH_KEY_PAIR);
$max = max(1, ...array_values($days));
?>
<div class="admin-page-heading">
    <div>
        <h1>Store overview</h1>
        <p>Here’s what’s happening at <?= e(setting('store_name')) ?>.</p>
    </div>
    <span class="date-control"><?= icon('orders', 16) ?> <?= date('d M Y') ?></span>
</div>
<div class="welcome-banner">
    <div class="welcome-symbol"><?= icon('bag', 30) ?></div>
    <div>
        <h2>Your storefront is ready</h2>
        <p>Manage your catalog, update collections, and get ready for your next order.</p>
    </div>
    <a href="/admin/products/edit" class="button dark small"><?= icon('plus', 16) ?> Add product</a>
</div>
<div class="dashboard-section-title">
    <h2>Performance</h2>
    <span>Last 30 days</span>
</div>
<section class="stats-grid" aria-label="Store performance">
    <?php
    foreach ([
            ['Total sales', money($stats['revenue']), 'Sales from non-cancelled orders', 'chart'],
            ['Orders', (string) $stats['orders'], $pending . ' awaiting fulfillment', 'orders'],
            ['Average order value', money($stats['average']), 'Across non-cancelled orders', 'bag'],
            ['Customers', (string) $customerCount, 'Registered store accounts', 'users'],
    ] as $stat):
    ?>
    <article class="stat-card">
        <div>
            <span><?= $stat[0] ?></span>
            <?= icon($stat[3], 17) ?>
        </div>
        <strong><?= $stat[1] ?></strong>
        <small><?= $stat[2] ?></small>
    </article>

    <?php endforeach; ?>
</section>
<div class="dashboard-columns">
    <section class="admin-card sales-card">
        <div class="card-header">
            <div>
                <h2>Sales over time</h2>
                <p>Your last 7 days, in perspective.</p>
            </div>
            <a href="/admin/analytics" class="subtle-link">View report <?= icon('arrow', 16) ?></a>
        </div>
        <div class="sales-chart" aria-label="Sales in the past 7 days">
            <?php
            for ($i = 6;$i >= 0;$i--):$day = date('Y-m-d', strtotime('-' . $i . ' days'));
            $val = (int) ($days[$day] ?? 0);
            ?>
            <div class="chart-column">
                <span class="chart-value"><?= $val ? money($val) : '' ?></span>
                <div class="chart-track">
                    <div
                        class="chart-bar"
                        style="height: <?= $val ? max(3, round($val / $max * 100)) : 0 ?>%"
                        title="<?= date('d M', strtotime($day)) ?>: <?= money($val) ?>"
                    ></div>
                </div>
                <span><?= date('D', strtotime($day)) ?></span>
            </div>

            <?php endfor; ?>
        </div>

        <?php if (!array_sum($days)): ?>
        <div class="chart-empty">No sales in the last 7 days.</div>

        <?php endif; ?>
        <div class="chart-legend">
            <i></i>
            Total sales
            <span>BDT ৳</span>
        </div>
    </section>
    <section class="admin-card store-status-card">
        <div class="card-header">
            <h2>Store at a glance</h2>
            <?= icon('external', 17) ?>
        </div>
        <div class="store-status-brand">
            <span class="store-monogram large">f.</span>
            <div>
                <strong><?= e(setting('store_name')) ?></strong>
                <span class="status-dot">Storefront ready</span>
            </div>
        </div>
        <dl class="store-status-list">
            <div>
                <dt>Active products</dt>
                <dd><?= $productCount ?></dd>
            </div>
            <div>
                <dt>Collections</dt>
                <dd><?= q('SELECT COUNT(*) FROM categories')->fetchColumn() ?></dd>
            </div>
            <div>
                <dt>Currency</dt>
                <dd>Bangladeshi taka ৳</dd>
            </div>
            <div>
                <dt>Payment options</dt>
                <dd>COD<?= trim(setting('bank_details')) ? ' + bank transfer' : '' ?></dd>
            </div>
        </dl>
        <a class="button secondary full-width" href="/" target="_blank">
            Visit your store <?= icon('external', 15) ?>
        </a>
    </section>
</div>
<section class="admin-card">
    <div class="card-header">
        <div>
            <h2>Recent orders</h2>
            <p>Track your latest customer orders.</p>
        </div>
        <a class="subtle-link" href="/admin/orders">View all orders <?= icon('arrow', 16) ?></a>
    </div>

    <?php if (!$recent): ?>
    <div class="empty-state compact">
        <?= icon('orders', 34) ?>
        <h3>Ready for your first order.</h3>
        <p>New orders will appear here as soon as your customers check out.</p>
        <a class="button secondary small" href="/">Explore your storefront</a>
    </div>

    <?php else:require __DIR__ . '/orders-table.php';endif; ?>
</section>
<div class="dashboard-bottom">
    <section class="admin-card">
        <div class="card-header"><h2>Store setup</h2></div>
        <a class="task-row" href="/admin/settings">
            <span class="task-icon"><?= icon('truck', 20) ?></span>
            <div>
                <strong>Set your delivery rates</strong>
                <p>Set shipping rates and your free-delivery threshold.</p>
            </div>
            <?= icon('chevron', 17) ?>
        </a>
        <a class="task-row" href="/admin/discounts">
            <span class="task-icon"><?= icon('tag', 20) ?></span>
            <div>
                <strong>Create a promotion</strong>
                <p>Manage discount codes and minimum-spend offers.</p>
            </div>
            <?= icon('chevron', 17) ?>
        </a>
        <a class="task-row" href="/admin/sliders">
            <span class="task-icon"><?= icon('image', 20) ?></span>
            <div>
                <strong>Update the homepage</strong>
                <p>Update your homepage slides and featured collections.</p>
            </div>
            <?= icon('chevron', 17) ?>
        </a>
    </section>
    <section class="admin-card inventory-card">
        <div class="card-header">
            <h2>Inventory to watch</h2>
            <a href="/admin/products" class="subtle-link">View products <?= icon('arrow', 15) ?></a>
        </div>

        <?php
        $lowStock = q(
            'SELECT v.*,p.name,(SELECT image FROM product_images WHERE product_id=p.id ORDER BY position,id LIMIT 1) image FROM variants v JOIN products p ON p.id=v.product_id WHERE p.active=1 ORDER BY v.stock ASC,v.id LIMIT 3'
        )->fetchAll();
        foreach ($lowStock as $v):
        ?>
        <a class="inventory-row" href="/admin/products/edit?id=<?= $v['product_id'] ?>">
            <img src="<?= e(image_url($v['image'])) ?>" alt="" />
            <div>
                <strong><?= e($v['name']) ?></strong>
                <small><?= e($v['color'] . ' / ' . $v['size']) ?></small>
            </div>
            <span><?= (int) $v['stock'] ?> left</span>
        </a>

        <?php endforeach; ?>
    </section>
</div>
