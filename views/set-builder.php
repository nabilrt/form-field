<?php
$setProducts = set_builder_products();
$setDraft = $_SESSION['set_builder'] ?? [];
?>
<section class="section set-builder-page">
    <div class="breadcrumb">
        <a href="/shop">Shop</a>
        <span>/</span>
        <span>Build a set</span>
    </div>
    <div class="set-heading">
        <div>
            <h1>A few things. Your combination.</h1>
            <p>Mix up to six products, choose your finishes, and keep an eye on your budget.</p>
        </div>
        <span><?= icon('bag', 22) ?> One set. Made by you.</span>
    </div>

    <?php if (!$setProducts): ?>
    <div class="empty-state">
        <h2>The next set is coming together.</h2>
        <p>
            There are no products available for a set right now. Explore the catalog for current
            availability.
        </p>
        <a class="button dark" href="/shop">View the collection</a>
    </div>

    <?php else: ?>
    <form
        method="post"
        class="set-builder-layout"
        data-set-builder
        data-currency-symbol="<?= e(currency()['symbol']) ?>"
        data-currency-decimals="<?= (int) currency()['decimals'] ?>"
    >
        <?= csrf() ?>
        <input type="hidden" name="action" value="cart_add_set" />
        <div class="set-product-list">
            <?php foreach ($setProducts as $setProduct): ?>

            <?php
            $choice = $setDraft[$setProduct['id']] ?? null;
            $optionIds = array_map('intval', array_column($setProduct['options'], 'id'));
            $selectedVariant = $choice && in_array((int) $choice['variant_id'], $optionIds, true)
                ? (int) $choice['variant_id'] : (int) ($optionIds[0] ?? 0);
            ?>
            <article class="set-product" data-set-product>
                <label class="set-selection">
                    <input
                        type="checkbox"
                        name="selected[]"
                        value="<?= $setProduct['id'] ?>"
                        data-set-select
                        <?= $choice ? 'checked' : '' ?>
                    />
                    <span class="sr-only">Add <?= e($setProduct['name']) ?> to your set</span>
                </label>
                <a class="set-product-image" href="/product/<?= e($setProduct['slug']) ?>">
                    <img
                        src="<?= e(image_url($setProduct['image'])) ?>"
                        alt="<?= e($setProduct['name']) ?>"
                        loading="lazy"
                        width="180"
                        height="210"
                    />
                </a>
                <div class="set-product-info">
                    <a href="/product/<?= e($setProduct['slug']) ?>"><h3><?= e($setProduct['name']) ?></h3></a>
                    <p><?= e($setProduct['material']) ?></p>
                    <div class="set-options">
                        <div class="field">
                            <label for="set-variant-<?= $setProduct['id'] ?>">Finish / type</label>
                            <select
                                id="set-variant-<?= $setProduct['id'] ?>"
                                name="set_variants[<?= $setProduct['id'] ?>]"
                                data-set-variant
                            >
                                <?php foreach ($setProduct['options'] as $option): ?>
                                <option
                                    value="<?= $option['id'] ?>"
                                    data-price="<?= $option['price'] ?>"
                                    data-stock="<?= $option['stock'] ?>"
                                    <?= (int) $option['id'] === $selectedVariant ? 'selected' : '' ?>
                                >
                                    <?= e($option['color'] . ' / ' . $option['size']) ?> · <?= money($option['price']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field set-quantity">
                            <label for="set-qty-<?= $setProduct['id'] ?>">Quantity</label>
                            <input
                                id="set-qty-<?= $setProduct['id'] ?>"
                                type="number"
                                name="set_quantities[<?= $setProduct['id'] ?>]"
                                min="1"
                                max="10"
                                step="1"
                                value="<?= (int) ($choice['quantity'] ?? 1) ?>"
                                data-set-quantity
                            />
                        </div>
                    </div>
                </div>
            </article>

            <?php endforeach; ?>
        </div>
        <aside class="set-summary">
            <div class="set-summary-title">
                <h2>Your set</h2>
                <span data-set-count>Choose your products</span>
            </div>
            <div class="set-summary-total">
                <span>Product subtotal</span>
                <strong data-set-total>—</strong>
            </div>
            <p class="set-summary-note">
                Prices are per product. Delivery, tax, and promo codes are calculated in your bag.
            </p>
            <div class="field">
                <label for="set-budget">
                    Your product budget
                    <span class="muted">(optional)</span>
                </label>
                <input
                    id="set-budget"
                    type="number"
                    min="0"
                    max="100000000"
                    step="1"
                    placeholder="Budget in <?= e(currency()['symbol']) ?>"
                    data-set-budget
                />
            </div>
            <div class="set-budget-track" data-budget-track hidden>
                <span data-budget-fill></span>
            </div>
            <p class="set-budget-feedback" data-set-feedback role="status" aria-live="polite">
                Select up to six products to begin.
            </p>
            <button type="submit" class="button dark full-width" data-set-submit>
                Add selections to bag <?= icon('bag', 17) ?>
            </button>
            <p class="set-stock-note">
                Availability is checked when you add your set and again at checkout.
            </p>
            <noscript>
                <p class="muted">
                    Your current total will appear in the bag after you add your selections.
                </p>
            </noscript>
        </aside>
        <div class="set-mobile-actions">
            <div>
                <span data-set-mobile-count>Choose your products</span>
                <strong data-set-total>—</strong>
            </div>
            <button type="submit" class="button dark" data-set-submit>
                Add to bag <?= icon('bag', 16) ?>
            </button>
        </div>
    </form>

    <?php endif; ?>
</section>
