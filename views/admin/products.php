<?php
$term = mb_substr(trim($_GET['q'] ?? ''), 0, 150);
$filter = $_GET['status'] ?? 'all';
$where = '1=1';
$params = [];
if ($term) {
    $where .= ' AND (p.name LIKE ? OR EXISTS(SELECT 1 FROM variants v WHERE v.product_id=p.id AND v.sku LIKE ?))';
    $params = ['%' . $term . '%', '%' . $term . '%'];
}
if ($filter === 'active') {
    $where .= ' AND p.active=1';
}
if ($filter === 'archived') {
    $where .= ' AND p.active=0';
}
$totalProducts = product_count($where, $params);
$totalPages = max(1, (int) ceil($totalProducts / 20));
$pageNumber = max(1, min($totalPages, (int) ($_GET['page'] ?? 1)));
$rows = products($where, $params, 'p.id DESC', 20, ($pageNumber - 1) * 20);
?>
<div class="admin-page-heading">
    <div>
        <h1>Products</h1>
        <p>Manage products, variants, and inventory.</p>
    </div>
    <a class="button dark small" href="/admin/products/edit"><?= icon('plus', 17) ?> Add product</a>
</div>
<section class="admin-card">
    <div class="admin-tabs">
        <?php
        foreach (['all' => 'All products', 'active' => 'Active', 'archived' => 'Archived'] as $k => $label):
        ?>
        <a href="/admin/products?status=<?= $k ?>" class="<?= $filter === $k ? 'active' : '' ?>">
            <?= $label ?>
        </a>

        <?php endforeach; ?>
    </div>
    <form class="table-filters" action="/admin/products">
        <div class="input-with-icon">
            <?= icon('search', 17) ?>
            <label class="sr-only" for="product-search">Search products</label>
            <input
                id="product-search"
                name="q"
                placeholder="Search by product name or SKU"
                value="<?= e($term) ?>"
            />
        </div>
        <input type="hidden" name="status" value="<?= e($filter) ?>" />
        <button class="button secondary small">Search</button>
        <span class="muted"><?= $totalProducts ?> products</span>
    </form>
    <div class="table-scroll">
        <table class="products-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Status</th>
                    <th>Inventory</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $p): ?>
                <tr>
                    <td>
                        <a href="/admin/products/edit?id=<?= $p['id'] ?>" class="table-product">
                            <img src="<?= e(image_url($p['image'])) ?>" alt="" width="46" height="46" />
                            <strong><?= e($p['name']) ?></strong>
                        </a>
                    </td>
                    <td><span class="badge <?= $p['active'] ? 'active' : 'archived' ?>"><?= $p['active'] ? 'Active' : 'Archived' ?></span></td>
                    <td>
                        <span class="<?= $p['stock'] < 5 ? 'low-stock' : '' ?>"><?= (int) $p['stock'] ?> in stock</span>
                        <small><?= $p['variant_count'] ?> variants</small>
                    </td>
                    <td><?= e($p['category'] ?? 'Uncategorized') ?></td>
                    <td><?= money($p['price'] ?? 0) ?></td>
                    <td>
                        <a
                            class="icon-button"
                            href="/admin/products/edit?id=<?= $p['id'] ?>"
                            aria-label="Edit <?= e($p['name']) ?>"
                        >
                            <?= icon('chevron', 17) ?>
                        </a>
                    </td>
                </tr>

                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if (!$rows): ?>
    <div class="empty-state compact">
        <?= icon('box', 35) ?>
        <h3>No products found.</h3>
        <p>Try another search, or add your first product.</p>
    </div>

    <?php endif; ?>
    <div class="table-footer">
        <span>Showing <?= count($rows) ?> of <?= $totalProducts ?> products</span>
        <nav class="pagination" aria-label="Product pages">
            <?php for ($i = 1;$i <= ceil($totalProducts / 20);$i++): ?>
            <a href="?<?= e(http_build_query(array_merge($_GET, ['page' => $i]))) ?>" class="<?= $i === $pageNumber ? 'active' : '' ?>"><?= $i ?></a>

            <?php endfor; ?>
        </nav>
    </div>
</section>
<p class="under-table-note">
    View your published catalog.
    <a href="/shop" target="_blank">See it in your storefront <?= icon('external', 13) ?></a>
</p>
