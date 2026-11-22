<?php
$available = reviews_available();
$status = is_string($_GET['status'] ?? null) ? $_GET['status'] : 'pending';
if (!in_array($status, ['all', 'pending', 'approved', 'rejected'], true)) {
    $status = 'pending';
}
$term = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 150) : '';
$where = '1=1';
$params = [];
if ($status !== 'all') {
    $where .= ' AND r.status=?';
    $params[] = $status;
}
if ($term !== '') {
    $where .= ' AND (p.name LIKE ? OR c.name LIKE ? OR c.email LIKE ? OR r.title LIKE ?)';
    array_push($params, ...array_fill(0, 4, '%' . $term . '%'));
}
$total = 0;
$rows = [];
$pageNumber = 1;
$pages = 1;
if ($available) {
    $joins = ' FROM product_reviews r JOIN products p ON p.id=r.product_id JOIN customers c ON c.id=r.customer_id WHERE ';
    $total = (int) q('SELECT COUNT(*)' . $joins . $where, $params)->fetchColumn();
    $pages = max(1, (int) ceil($total / 20));
    $pageNumber = max(1, min($pages, (int) ($_GET['page'] ?? 1)));
    $rows = q(
        'SELECT r.*,p.name AS product_name,p.slug,p.active,c.name AS customer_name,c.email'
        . $joins . $where . ' ORDER BY r.created_at DESC,r.id DESC LIMIT ? OFFSET ?',
        [...$params, 20, ($pageNumber - 1) * 20]
    )->fetchAll();
}
?>
<div class="admin-page-heading">
    <div>
        <h1>Product reviews</h1>
        <p>Moderate customer feedback before it appears in the store.</p>
    </div>
    <span class="date-control"><?= $total ?> reviews in this view</span>
</div>

<?php if (!$available): ?>
<section class="admin-card padded">
    <h2>Set up review storage</h2>
    <p>
        Apply the included review migration once to enable submissions and moderation. Existing
        store data is preserved.
    </p>
    <code>php scripts/migrate-reviews.php</code>
</section>

<?php else: ?>
<section class="admin-card">
    <nav class="admin-tabs" aria-label="Review moderation views">
        <?php
        foreach (['pending' => 'Pending', 'approved' => 'Published', 'rejected' => 'Rejected', 'all' => 'All reviews'] as $value => $label):
        ?>
        <a class="<?= $status === $value ? 'active' : '' ?>" href="/admin/reviews?<?= e(http_build_query(['status' => $value, 'q' => $term])) ?>"><?= $label ?></a>

        <?php endforeach; ?>
    </nav>
    <form class="table-filters" action="/admin/reviews">
        <div class="input-with-icon">
            <?= icon('search', 17) ?>
            <label class="sr-only" for="review-search">Search reviews</label>
            <input
                id="review-search"
                name="q"
                value="<?= e($term) ?>"
                placeholder="Search product, customer, email, or review title"
            />
        </div>
        <input type="hidden" name="status" value="<?= e($status) ?>" />
        <button class="button secondary small">Search</button>
    </form>
    <div class="review-moderation-note">
        Publish genuine feedback regardless of rating. Reject spam, abuse, or content unrelated to
        the product.
    </div>

    <?php if (!$rows): ?>
    <div class="empty-state compact">
        <?= icon('star', 35) ?>
        <h3>No reviews in this view.</h3>
        <p>
            New customer reviews arrive in Pending. Published reviews contribute to product ratings.
        </p>
    </div>

    <?php else: ?>
    <div class="table-scroll">
        <table class="review-admin-table">
            <thead>
                <tr>
                    <th>Product & customer</th>
                    <th>Review</th>
                    <th>Status</th>
                    <th>Moderate</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $review): ?>
                <tr>
                    <td>
                        <strong><?= e($review['product_name']) ?></strong>
                        <small><?= e($review['customer_name']) ?></small>
                        <small><?= e($review['email']) ?></small>
                        <small><?= date('d M Y', strtotime($review['created_at'])) ?></small>
                        <?php if ($review['verified_purchase']): ?>
                        <span class="badge">Verified purchase</span>
                        <?php endif; ?>

                        <?php if ($review['active']): ?>
                        <a
                            class="subtle-link"
                            href="/product/<?= e($review['slug']) ?>#reviews"
                            target="_blank"
                        >
                            View product <?= icon('external', 12) ?>
                        </a>
                        <?php else: ?>
                        <small>Archived product</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= review_stars((int) $review['rating']) ?>
                        <h3><?= e($review['title']) ?></h3>
                        <p class="review-excerpt"><?= e(mb_strimwidth($review['body'], 0, 200, '…')) ?></p>
                        <details class="admin-review-body">
                            <summary>Read full review</summary>
                            <p><?= nl2br(e($review['body'])) ?></p>
                        </details>
                    </td>
                    <td><span class="badge review-<?= e($review['status']) ?>"><?= $review['status'] === 'approved' ? 'Published' : e(ucfirst($review['status'])) ?></span></td>
                    <td>
                        <div class="review-moderation-actions">
                            <?php
                            foreach (['approved' => 'Publish', 'rejected' => 'Reject', 'pending' => 'Return to pending'] as $value => $label):
                            ?>

                            <?php
                            if ($review['status'] === $value) {
                                continue;
                            }
                            ?>
                            <form method="post">
                                <?= csrf() ?>
                                <input type="hidden" name="action" value="moderate_review" />
                                <input type="hidden" name="review_id" value="<?= $review['id'] ?>" />
                                <input type="hidden" name="revision" value="<?= $review['revision'] ?>" />
                                <input type="hidden" name="status" value="<?= $value ?>" />
                                <input
                                    type="hidden"
                                    name="return_status"
                                    value="<?= e($status) ?>"
                                />
                                <button type="submit" class="button <?= $value === 'approved' ? 'dark' : 'secondary' ?> small">
                                    <?= $label ?>
                                </button>
                            </form>

                            <?php endforeach; ?>
                        </div>
                    </td>
                </tr>

                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php endif; ?>
    <div class="table-footer">
        <span>Showing <?= count($rows) ?> of <?= $total ?> reviews</span>
        <nav class="pagination" aria-label="Moderation pages">
            <?php if ($pageNumber > 1): ?>
            <a href="/admin/reviews?<?= e(http_build_query(['status' => $status, 'q' => $term, 'page' => $pageNumber - 1])) ?>">Previous</a>
            <?php endif; ?>
            <span><?= $pageNumber ?> / <?= $pages ?></span>
            <?php if ($pageNumber < $pages): ?>
            <a href="/admin/reviews?<?= e(http_build_query(['status' => $status, 'q' => $term, 'page' => $pageNumber + 1])) ?>">Next</a>
            <?php endif; ?>
        </nav>
    </div>
</section>

<?php endif; ?>
