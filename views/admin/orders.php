<?php
$filter = order_filters($_GET);
$status = $filter['status'];
$term = $filter['term'];
$where = $filter['where'];
$params = $filter['params'];
$count = (int) q('SELECT COUNT(*) FROM orders WHERE ' . $where, $params)->fetchColumn();
$pageNumber = max(1, min(max(1, (int) ceil($count / 25)), (int) ($_GET['page'] ?? 1)));
$offset = ($pageNumber - 1) * 25;
$recent = q('SELECT * FROM orders WHERE ' . $where . ' ORDER BY id DESC LIMIT ? OFFSET ?', [...$params, 25, $offset])->fetchAll();
?>
<div class="admin-page-heading">
    <div>
        <h1>Orders</h1>
        <p>Track payments and manage fulfillment.</p>
    </div>
    <div class="order-heading-actions">
        <span class="date-control"><?= icon('orders', 17) ?> <?= $count ?> orders</span>
        <a class="button secondary small" href="/admin/orders/export?<?= e(http_build_query(['status' => $status, 'q' => $term])) ?>">
            <?= icon('download', 16) ?> Export CSV
        </a>
    </div>
</div>
<section class="admin-card">
    <nav class="admin-tabs">
        <?php
        foreach ([
                'all' => 'All orders',
                'unfulfilled' => 'Unfulfilled',
                'processing' => 'Processing',
                'shipped' => 'Shipped',
                'delivered' => 'Delivered',
                'cancelled' => 'Cancelled',
        ] as $key => $label):
        ?>
        <a href="/admin/orders?status=<?= $key ?>" class="<?= $status === $key ? 'active' : '' ?>">
            <?= $label ?>
        </a>

        <?php endforeach; ?>
    </nav>
    <form class="table-filters">
        <div class="input-with-icon">
            <?= icon('search', 17) ?>
            <label class="sr-only" for="order-search">Search orders</label>
            <input
                id="order-search"
                name="q"
                value="<?= e($term) ?>"
                placeholder="Search order number, customer or email"
            />
        </div>
        <input type="hidden" name="status" value="<?= e($status) ?>" />
        <button class="button secondary small">Search</button>
    </form>

    <?php
    if ($recent):require __DIR__ . '/orders-table.php';
    else:
    ?>
    <div class="empty-state">
        <?= icon('orders', 36) ?>
        <h3>No orders here yet.</h3>
        <p>Orders matching this view will appear here.</p>
        <a href="/admin/orders" class="button secondary small">View all orders</a>
    </div>

    <?php endif; ?>
    <div class="table-footer">
        <span>Showing <?= count($recent) ?> of <?= $count ?> orders</span>
        <nav class="pagination">
            <?php for ($i = 1;$i <= ceil($count / 25);$i++): ?>
            <a href="?<?= e(http_build_query(array_merge($_GET, ['page' => $i]))) ?>"><?= $i ?></a>

            <?php endfor; ?>
        </nav>
    </div>
</section>
