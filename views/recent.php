<?php $recent = recent_products(0, 8); ?>
<section class="section recent-page">
    <div class="breadcrumb">
        <a href="/shop">Shop</a>
        <span>/</span>
        <span>Recently viewed</span>
    </div>
    <div class="section-heading">
        <div>
            <h1>Pick up where you left off.</h1>
            <p>The last eight products you viewed during this visit.</p>
        </div>
        <?php if ($recent): ?>
        <form method="post">
            <?= csrf() ?>
            <input type="hidden" name="action" value="recent_clear" />
            <button type="submit" class="text-button">Clear history</button>
        </form>
        <?php endif; ?>
    </div>

    <?php if ($recent): ?>
    <div class="product-grid">
        <?php foreach ($recent as $p): ?>

        <?php require __DIR__ . '/product-card.php'; ?>

        <?php endforeach; ?>
    </div>

    <?php else: ?>
    <div class="empty-state">
        <?= icon('return', 35) ?>
        <h2>A fresh start.</h2>
        <p>Products you open will appear here, with their current prices and availability.</p>
        <a class="button dark" href="/shop">Explore the collection <?= icon('arrow', 17) ?></a>
    </div>
    <?php endif; ?>
</section>
