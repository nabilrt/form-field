<?php
$filter = is_string($_GET['status'] ?? null) && in_array($_GET['status'], ['requested', 'received', 'refunded', 'rejected'], true)
    ? $_GET['status'] : 'all';
$returns = returns_available() ? q(
    "SELECT r.*, o.number, o.first_name, o.last_name, o.currency,
        (SELECT SUM(ri.quantity) FROM return_items ri WHERE ri.return_id=r.id) AS units
    FROM returns r JOIN orders o ON o.id=r.order_id"
    . ($filter === 'all' ? '' : ' WHERE r.status=?') . ' ORDER BY r.id DESC LIMIT 200',
    $filter === 'all' ? [] : [$filter]
)->fetchAll() : [];
?>
<div class="admin-page-heading">
    <div>
        <h1>Returns</h1>
        <p>Review return requests, restock what comes back, and record refunds.</p>
    </div>
    <span class="date-control"><?= icon('return', 17) ?> <?= (int) setting('return_window_days', '30') ?>-day window</span>
</div>

<?php if (!returns_available()): ?>
<section class="admin-card padded">
    <h2>Returns need a quick setup</h2>
    <p>Run <code>php scripts/migrate-returns.php</code> once on your server, then reload this page.</p>
</section>
<?php else: ?>
<section class="admin-card">
    <nav class="admin-tabs" aria-label="Filter returns">
    <?php foreach (['all' => 'All', 'requested' => 'To review', 'received' => 'To refund', 'refunded' => 'Refunded', 'rejected' => 'Rejected'] as $key => $label): ?>
    <a href="/admin/returns<?= $key === 'all' ? '' : '?status=' . $key ?>" class="<?= $filter === $key ? 'active' : '' ?>" <?= $filter === $key ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
    <?php endforeach; ?>
    </nav>
    <?php if ($returns): ?>
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Return</th>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Requested</th>
                    <th>Items</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th class="text-right">Refund</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($returns as $r): ?>
                <tr>
                    <td><a href="/admin/orders/view?id=<?= (int) $r['order_id'] ?>#returns"><strong>#<?= (int) $r['id'] ?></strong></a></td>
                    <td><?= e($r['number']) ?></td>
                    <td><?= e($r['first_name'] . ' ' . $r['last_name']) ?></td>
                    <td><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                    <td><?= (int) $r['units'] ?></td>
                    <td><?= e(RETURN_REASONS[$r['reason']] ?? $r['reason']) ?></td>
                    <td><span class="badge <?= e($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span></td>
                    <td class="text-right"><?= $r['refund_amount'] !== null ? money((int) $r['refund_amount'], $r['currency']) : '—' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state compact">
        <?= icon('return', 32) ?>
        <h3>No returns here.</h3>
        <p>Shoppers can request returns from their order page once an order is delivered.</p>
    </div>
    <?php endif; ?>
</section>
<?php endif; ?>
