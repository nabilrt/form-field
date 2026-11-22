<article class="product-card" data-product-id="<?= $p['id'] ?>">
    <button
        type="button"
        class="wishlist-button icon-button"
        data-save-product="<?= $p['id'] ?>"
        data-product-name="<?= e($p['name']) ?>"
        aria-label="Save <?= e($p['name']) ?>"
        aria-pressed="false"
        hidden
    >
        <?= icon('heart', 18) ?>
    </button>
    <a href="/product/<?= e($p['slug']) ?>" class="product-photo">
        <img
            src="<?= e(image_url($p['image'])) ?>"
            alt="<?= e($p['name']) ?>"
            loading="lazy"
            width="600"
            height="700"
        />

        <?php if ((int) $p['stock'] === 0): ?>
        <span class="product-label">Sold out</span>

        <?php elseif (!empty($p['sale'])): ?>
        <span class="product-label">Sale</span>

        <?php endif; ?>
        <span class="product-quick" aria-hidden="true"><?= icon('plus') ?></span>
    </a>
    <div class="product-meta">
        <div>
            <h3><a href="/product/<?= e($p['slug']) ?>"><?= e($p['name']) ?></a></h3>
            <p><?= e($p['material']) ?></p>
        </div>
        <span><?= money($p['price'] ?? 0) ?></span>
    </div>
    <div class="swatches" aria-label="Available colors">
        <?php foreach ($p['colors'] as $index => $color): ?>
        <span class="swatch" style="background: <?= e(color_hex($color)) ?>" title="<?= e($color) ?>"></span>

        <?php endforeach; ?>
    </div>

    <?php if (!empty($p['review_count'])): ?>
    <a
        class="card-rating"
        href="/product/<?= e($p['slug']) ?>#reviews"
        aria-label="Rated <?= number_format($p['average_rating'], 1) ?> out of 5 from <?= $p['review_count'] ?> <?= $p['review_count'] === 1 ? 'review' : 'reviews' ?>"
    >
        <?= icon('star', 12) ?>
        <span><?= number_format($p['average_rating'], 1) ?></span>
        <span>(<?= $p['review_count'] ?>)</span>
    </a>

    <?php endif; ?>

    <?php
    $isCompared = in_array((int) $p['id'], shopping_ids($_SESSION['compare_products'] ?? [], 3), true);
    ?>
    <form
        method="post"
        class="compare-product-form"
        data-compare-form
        data-compare-product="<?= $p['id'] ?>"
    >
        <?= csrf() ?>
        <input type="hidden" name="action" value="<?= $isCompared ? 'compare_remove' : 'compare_add' ?>" />
        <input type="hidden" name="product_id" value="<?= $p['id'] ?>" />
        <input type="hidden" name="return_to" value="<?= e(safe_shopping_return($_SERVER['REQUEST_URI'] ?? '/shop')) ?>" />
        <button type="submit" class="compare-toggle" aria-pressed="<?= $isCompared ? 'true' : 'false' ?>">
            <?= icon('compare', 14) ?>
            <span data-compare-label><?= $isCompared ? 'Comparing' : 'Compare' ?></span>
        </button>
    </form>
</article>
