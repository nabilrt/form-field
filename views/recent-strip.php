<?php $recentStrip = recent_products((int) ($product['id'] ?? 0), 4); ?>

<?php if ($recentStrip): ?>
<section class="section recent-section">
    <div class="section-heading">
        <div>
            <h2>Still on your mind?</h2>
            <p>A few things you recently explored.</p>
        </div>
        <a class="text-link" href="/recent">Recently viewed <?= icon('arrow', 17) ?></a>
    </div>
    <div class="product-grid">
        <?php foreach ($recentStrip as $p): ?>

        <?php require __DIR__ . '/product-card.php'; ?>

        <?php endforeach; ?>
    </div>
</section>

<?php endif; ?>
