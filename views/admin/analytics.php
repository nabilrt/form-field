<?php
$summary = q(
    "SELECT COUNT(*) orders,COALESCE(SUM(CASE WHEN status<>'cancelled' THEN total*rate_to_base ELSE 0 END),0) revenue,COALESCE(SUM(CASE WHEN payment_status='paid' AND status<>'cancelled' THEN total*rate_to_base ELSE 0 END),0) paid,COALESCE(AVG(CASE WHEN status<>'cancelled' THEN total*rate_to_base END),0) average FROM orders"
)->fetch();
$top = q(
    "SELECT i.name,SUM(i.quantity) units,SUM(i.price*i.quantity*o.rate_to_base) sales FROM order_items i JOIN orders o ON o.id=i.order_id WHERE o.status<>'cancelled' GROUP BY i.name ORDER BY sales DESC LIMIT 10"
)->fetchAll();
$monthly = q(
    "SELECT DATE_FORMAT(created_at,'%Y-%m') month,COUNT(*) orders,SUM(total*rate_to_base) sales,SUM(discount*rate_to_base) discounts,SUM(shipping*rate_to_base) shipping FROM orders WHERE status<>'cancelled' GROUP BY month ORDER BY month DESC LIMIT 12"
)->fetchAll();
?>
<div class="admin-page-heading">
    <div>
        <h1>Analytics</h1>
        <p>Real numbers. A clearer picture.</p>
    </div>
    <span class="date-control">All time · in BDT ৳</span>
</div>
<div class="stats-grid">
    <?php
    foreach ([
            ['Total sales', money($summary['revenue'])],
            ['Collected payments', money($summary['paid'])],
            ['Orders', $summary['orders']],
            ['Average order', money($summary['average'])],
    ] as $s):
    ?>
    <article class="stat-card">
        <span><?= $s[0] ?></span>
        <strong><?= $s[1] ?></strong>
        <small>Across your store’s history</small>
    </article>

    <?php endforeach; ?>
</div>
<section class="admin-card">
    <div class="card-header">
        <h2>Sales by month</h2>
        <span class="muted">Last 12 active months</span>
    </div>

    <?php if ($monthly): ?>
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Orders</th>
                    <th>Discounts</th>
                    <th>Delivery collected</th>
                    <th class="text-right">Sales</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($monthly as $m): ?>
                <tr>
                    <td><?= date('F Y', strtotime($m['month'] . '-01')) ?></td>
                    <td><?= $m['orders'] ?></td>
                    <td><?= money($m['discounts']) ?></td>
                    <td><?= money($m['shipping']) ?></td>
                    <td class="text-right"><?= money($m['sales']) ?></td>
                </tr>

                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php else: ?>
    <div class="empty-state compact">
        <?= icon('chart', 35) ?>
        <h3>A story waiting to happen.</h3>
        <p>Your sales reports will grow with your store.</p>
    </div>

    <?php endif; ?>
</section>
<section class="admin-card">
    <div class="card-header">
        <h2>Customer favorites</h2>
        <span class="muted">By item revenue, before discounts</span>
    </div>

    <?php if ($top): ?>
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Units sold</th>
                <th class="text-right">Item revenue</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($top as $p): ?>
            <tr>
                <td><strong><?= e($p['name']) ?></strong></td>
                <td><?= $p['units'] ?></td>
                <td class="text-right"><?= money($p['sales']) ?></td>
            </tr>

            <?php endforeach; ?>
        </tbody>
    </table>

    <?php else: ?>
    <div class="empty-state compact">
        <p>Your best sellers will appear here after the first sale.</p>
    </div>

    <?php endif; ?>
</section>
<p class="under-table-note">
    Sales include delivery and tax after discounts. Cancelled orders are excluded from sales.
</p>
