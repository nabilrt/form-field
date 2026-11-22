<?php
$requested = is_string($_GET['items'] ?? null) ? mb_substr($_GET['items'], 0, 1000) : '';
$savedIds = array_values(array_unique(array_filter(
            array_map(static fn ($id) => ctype_digit($id) ? (int) $id : 0, explode(',', $requested)),
            static fn ($id) => $id > 0 && $id <= 2147483647
)));
$savedIds = array_slice($savedIds, 0, 50);
$savedProducts = [];
if ($savedIds) {
    $placeholders = implode(',', array_fill(0, count($savedIds), '?'));
    $savedProducts = products('p.active=1 AND p.id IN (' . $placeholders . ')', $savedIds, 'p.id DESC', 50);
}
?>
<section class="section saved-page" data-saved-page data-requested-ids="<?= e(json_encode($savedIds)) ?>">
    <div class="breadcrumb">
        <a href="/">Home</a>
        <span>/</span>
        <span>Saved items</span>
    </div>
    <div class="section-heading">
        <div>
            <h1>Worth coming back to.</h1>
            <p>Your wishlist is kept in this browser, across visits.</p>
        </div>
        <button type="button" class="text-button" data-clear-saved hidden>Clear saved items</button>
    </div>
    <p class="saved-count" data-saved-count><?= count($savedProducts) ?> saved products</p>
    <div class="product-grid" data-saved-grid>
        <?php foreach ($savedProducts as $p): ?>

        <?php require __DIR__ . '/product-card.php'; ?>

        <?php endforeach; ?>
    </div>
    <div class="empty-state" data-saved-empty <?= $savedProducts ? 'hidden' : '' ?>>
        <?= icon('heart', 38) ?>
        <h2>A place for your favorites.</h2>
        <p>Select the heart on a product to save it for later. Your list stays in this browser.</p>
        <a class="button dark" href="/shop">Explore the collection <?= icon('arrow', 18) ?></a>
    </div>
    <noscript>
        <p class="muted">Enable JavaScript to save and manage your wishlist in this browser.</p>
    </noscript>
</section>
