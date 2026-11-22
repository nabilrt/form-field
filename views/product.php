<?php
$variants = localize_variants($variants);
// Preselect the first purchasable option so the button is ready.
$selected = array_values(array_filter($variants, static fn ($v) => (int) $v['stock'] > 0))[0] ?? $variants[0] ?? null;
$showSize = count(array_unique(array_column($variants, 'size'))) > 1;
$variantJson = json_encode(array_map(static fn ($v) => $v + [
    'price_label' => money($v['price']),
    'compare_label' => money($v['compare_price'] ?? 0),
], $variants), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<div class="section product-page">
    <div class="breadcrumb">
        <a href="/">Home</a>
        <span>/</span>
        <a href="/shop">Shop</a>
        <span>/</span>
        <span><?= e($product['name']) ?></span>
    </div>
    <div class="product-detail">
        <section class="product-gallery" aria-label="Product images">
            <button
                class="main-product-image"
                data-lightbox-open
                aria-label="Enlarge product image"
            >
                <img
                    id="main-image"
                    src="<?= e(image_url($images[0]['image'] ?? null)) ?>"
                    alt="<?= e($product['name']) ?>"
                    width="900"
                    height="1000"
                />
                <span><?= icon('plus', 20) ?> A closer look</span>
            </button>
            <div class="gallery-thumbs">
                <?php foreach ($images as $i => $img): ?>
                <button
                    data-gallery-src="<?= e(image_url($img['image'])) ?>"
                    class="<?= $i === 0 ? 'active' : '' ?>"
                    aria-label="View product image <?= $i + 1 ?>"
                >
                    <img src="<?= e(image_url($img['image'])) ?>" alt="<?= e($product['name']) ?>, view <?= $i + 1 ?>" />
                </button>

                <?php endforeach; ?>
            </div>
        </section>
        <section class="product-info">
            <a class="subtle-link" href="/shop?category=<?= e(slugify($product['category'] ?? '')) ?>"><?= e($product['category']) ?></a>
            <h1><?= e($product['name']) ?></h1>
            <div class="product-price">
                <span id="variant-price"><?= money($selected['price'] ?? 0) ?></span>
                <del id="variant-compare" <?= (int) ($selected['compare_price'] ?? 0) <= (int) ($selected['price'] ?? 0) ? 'hidden' : '' ?>><?= money($selected['compare_price'] ?? 0) ?></del>
                <span><?= e(current_currency_code()) ?></span>
            </div>

            <?php if ($reviewData['available']): ?>
            <a class="product-rating" href="#reviews">
                <?= icon('star', 15) ?>
                <?php if ($reviewData['count']): ?>
                <strong><?= number_format($reviewData['average'], 1) ?>/5</strong>
                <span><?= $reviewData['count'] ?> <?= $reviewData['count'] === 1 ? 'review' : 'reviews' ?></span>

                <?php else: ?>
                <span>No reviews yet · Write a review</span>

                <?php endif; ?>
            </a>

            <?php endif; ?>
            <p class="product-description"><?= nl2br(e($product['description'])) ?></p>
            <form method="post" id="add-to-cart" data-variants="<?= e($variantJson) ?>">
                <?= csrf() ?>
                <input type="hidden" name="action" value="cart_add" />
                <fieldset class="field variant-swatches">
                    <legend>Choose your finish<?= $showSize ? ' & size' : '' ?></legend>
                    <div class="swatch-list">
                        <?php foreach ($variants as $v): ?>
                        <label class="variant-chip<?= (int) $v['stock'] > 0 ? '' : ' sold-out' ?>">
                            <input
                                type="radio"
                                name="variant_id"
                                value="<?= (int) $v['id'] ?>"
                                required
                                <?= $selected && $v['id'] === $selected['id'] ? 'checked' : '' ?>
                            />
                            <span class="swatch-dot" style="background: <?= e(color_hex($v['color'])) ?>"></span>
                            <span><?= e($v['color'] . ($showSize ? ' · ' . $v['size'] : '')) ?><?= (int) $v['stock'] > 0 ? '' : '<small> Sold out</small>' ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
                <p class="stock-indicator" id="variant-stock"><?= (int) ($selected['stock'] ?? 0) > 0 ? 'In stock. Ready for your home.' : 'Currently sold out.' ?></p>
                <div class="add-cart-row">
                    <div class="quantity-control">
                        <button type="button" data-quantity="-1" aria-label="Decrease quantity">
                            −
                        </button>
                        <label class="sr-only" for="quantity">Quantity</label>
                        <input
                            id="quantity"
                            type="number"
                            name="quantity"
                            value="1"
                            min="1"
                            max="<?= max(1, (int) ($selected['stock'] ?? 0)) ?>"
                        />
                        <button type="button" data-quantity="1" aria-label="Increase quantity">
                            +
                        </button>
                    </div>
                    <button class="button dark" id="add-button" <?= empty($selected['stock']) ? 'disabled' : '' ?>>
                        Add to bag <?= icon('bag', 18) ?>
                    </button>
                </div>
            </form>
            <div class="sticky-buy" data-sticky-buy hidden>
                <div>
                    <strong><?= e($product['name']) ?></strong>
                    <span data-sticky-price></span>
                </div>
                <button class="button dark" form="add-to-cart" data-sticky-button>Add to bag <?= icon('bag', 18) ?></button>
            </div>
            <div class="product-secondary-actions">
                <button
                    type="button"
                    class="text-link saved-product-action"
                    data-save-product="<?= $product['id'] ?>"
                    data-product-name="<?= e($product['name']) ?>"
                    aria-pressed="false"
                    hidden
                >
                    <?= icon('heart', 18) ?>
                    <span data-save-label>Save for later</span>
                </button>

                <?php
                $isCompared = in_array((int) $product['id'], shopping_ids($_SESSION['compare_products'] ?? [], 3), true);
                ?>
                <form
                    method="post"
                    class="compare-product-form"
                    data-compare-form
                    data-compare-product="<?= $product['id'] ?>"
                >
                    <?= csrf() ?>
                    <input type="hidden" name="action" value="<?= $isCompared ? 'compare_remove' : 'compare_add' ?>" />
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>" />
                    <input type="hidden" name="return_to" value="<?= e(safe_shopping_return($_SERVER['REQUEST_URI'] ?? '/shop')) ?>" />
                    <button type="submit" class="compare-toggle" aria-pressed="<?= $isCompared ? 'true' : 'false' ?>">
                        <?= icon('compare', 15) ?>
                        <span data-compare-label><?= $isCompared ? 'Comparing' : 'Compare' ?></span>
                    </button>
                </form>
            </div>
            <div class="product-delivery">
                <?= icon('truck', 20) ?>
                <span>Delivery across Bangladesh · Free over <?= money(convert(setting('free_shipping'))) ?></span>
            </div>
            <details open>
                <summary>Materials & details <?= icon('plus', 16) ?></summary>
                <p><?= e($product['material']) ?>. Each piece has its own natural character.</p>
                <p class="muted">
                    SKU:
                    <span id="variant-sku"><?= e($selected['sku'] ?? '') ?></span>
                </p>
            </details>
            <details>
                <summary>A little care goes a long way <?= icon('plus', 16) ?></summary>
                <p><?= e($product['care']) ?></p>
            </details>
            <details>
                <summary>Delivery & payment <?= icon('plus', 16) ?></summary>
                <p>
                    Pay with cash on delivery or bank transfer. Standard delivery is
                    <?= money(convert(setting('shipping'))) ?>, with free delivery on orders from <?= money(convert(setting('free_shipping'))) ?>. For
                    delivery and return enquiries, email <?= e(setting('store_email')) ?>.
                </p>
            </details>
        </section>
    </div>
</div>

<?php require __DIR__ . '/product-reviews.php'; ?>
<section class="section">
    <div class="section-heading">
        <h2>Good company.</h2>
        <a class="text-link" href="/shop">Explore more <?= icon('arrow', 18) ?></a>
    </div>
    <div class="product-grid">
        <?php
        foreach (products('p.active=1 AND p.id<>?', [$product['id']], 'p.id DESC', 4) as $p) {
            require __DIR__ . '/product-card.php';
        }
        ?>
    </div>
</section>
<dialog id="image-lightbox">
    <button class="icon-button" data-lightbox-close aria-label="Close image">
        <?= icon('close', 26) ?>
    </button>
    <button class="lightbox-prev icon-button" data-gallery-step="-1" aria-label="Previous image">
        <?= icon('arrow', 25) ?>
    </button>
    <img
        id="lightbox-image"
        src="<?= e(image_url($images[0]['image'] ?? null)) ?>"
        alt="<?= e($product['name']) ?> enlarged"
        loading="lazy"
    />
    <button class="lightbox-next icon-button" data-gallery-step="1" aria-label="Next image">
        <?= icon('arrow', 25) ?>
    </button>
</dialog>

<?php require __DIR__ . '/recent-strip.php'; ?>
