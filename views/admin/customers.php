<?php
$term = mb_substr(trim($_GET['q'] ?? ''), 0, 150);
$searchParams = ['%' . $term . '%', '%' . $term . '%'];
$totalCustomers = (int) q(
    'SELECT COUNT(*) FROM customers WHERE name LIKE ? OR email LIKE ?',
    $searchParams
)->fetchColumn();
$customerPages = max(1, (int) ceil($totalCustomers / 50));
$customerPage = max(1, min($customerPages, (int) ($_GET['page'] ?? 1)));
$list = q(
    "SELECT c.*,
    (SELECT COUNT(*) FROM orders WHERE customer_id=c.id) AS orders,
    (SELECT COALESCE(SUM(total*rate_to_base),0) FROM orders WHERE customer_id=c.id AND status<>'cancelled') AS spent
    FROM customers c WHERE c.name LIKE ? OR c.email LIKE ? ORDER BY c.id DESC LIMIT ? OFFSET ?",
    [...$searchParams, 50, ($customerPage - 1) * 50]
)->fetchAll();
$guests = q(
    "SELECT email,MAX(CONCAT(first_name,' ',last_name)) name,COUNT(*) orders,SUM(CASE WHEN status<>'cancelled' THEN total ELSE 0 END) spent FROM orders WHERE customer_id IS NULL GROUP BY email ORDER BY MAX(id) DESC LIMIT 50"
)->fetchAll();
?>
<div class="admin-page-heading">
    <div>
        <h1>Customers</h1>
        <p>View registered accounts and guest purchase history.</p>
    </div>
    <span class="date-control"><?= $totalCustomers ?> registered accounts</span>
</div>
<section class="admin-card">
    <div class="card-header"><h2>Registered customers</h2></div>
    <form class="table-filters">
        <div class="input-with-icon">
            <?= icon('search', 17) ?>
            <label class="sr-only" for="customer-search">Search customers</label>
            <input
                id="customer-search"
                name="q"
                value="<?= e($term) ?>"
                placeholder="Search name or email"
            />
        </div>
        <button class="button secondary small">Search</button>
    </form>

    <?php if ($list): ?>
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Email</th>
                    <th>Joined</th>
                    <th>Orders</th>
                    <th class="text-right">Amount spent</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($list as $c): ?>
                <tr>
                    <td>
                        <div class="customer-name">
                            <span class="avatar"><?= e(mb_strtoupper(mb_substr($c['name'], 0, 1))) ?></span>
                            <strong><?= e($c['name']) ?></strong>
                        </div>
                    </td>
                    <td><a href="mailto:<?= e($c['email']) ?>"><?= e($c['email']) ?></a></td>
                    <td><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                    <td><a href="/admin/orders?q=<?= urlencode($c['email']) ?>"><?= $c['orders'] ?> orders</a></td>
                    <td class="text-right"><?= money($c['spent']) ?></td>
                </tr>

                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php else: ?>
    <div class="empty-state compact">
        <?= icon('users', 35) ?>
        <h3>No registered customers yet.</h3>
        <p>Customer accounts will appear here when people register.</p>
    </div>

    <?php endif; ?>
<div class="table-footer"><span>Showing <?= count($list) ?> of <?= $totalCustomers ?> accounts</span>
<nav class="pagination" aria-label="Customer pages">
    <?php for ($i = 1; $i <= $customerPages; $i++): ?>
    <a href="?<?= e(http_build_query(['q' => $term, 'page' => $i])) ?>"><?= $i ?></a>
    <?php endfor; ?>
</nav></div>
</section>

<?php if ($guests): ?>
<section class="admin-card">
    <div class="card-header">
        <h2>Guest customers</h2>
        <span class="muted">Most recent 50</span>
    </div>
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Email</th>
                    <th>Orders</th>
                    <th class="text-right">Amount spent</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($guests as $c): ?>
                <tr>
                    <td><strong><?= e($c['name']) ?></strong></td>
                    <td><?= e($c['email']) ?></td>
                    <td><a href="/admin/orders?q=<?= urlencode($c['email']) ?>"><?= $c['orders'] ?> orders</a></td>
                    <td class="text-right"><?= money($c['spent']) ?></td>
                </tr>

                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php endif; ?>
