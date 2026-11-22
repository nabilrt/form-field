<?php
$items = cart_items();
$totals      = cart_totals($items);
?>
<section class="section cart-page">
    <div class="section-heading">
        <h1>
            Your bag
            <span class="count-label">
                <?php echo cart_count() ?>
            </span>
        </h1>
        <a class="text-link" href="/shop">
            Continue shopping

            <?php echo icon('arrow', 18) ?>
        </a>
    </div>

    <?php if (! $items): ?>
    <div class="empty-state">
        <?php echo icon('bag', 46) ?>
        <h2>Your bag is empty.</h2>
        <p>Browse the collection and add your first item.</p>
        <a href="/shop" class="button dark">
            Explore the collection

            <?php echo icon('arrow', 18) ?>
        </a>
    </div>

    <?php else: ?>
    <div class="cart-layout">
        <div>
            <form method="post">
                <?php echo csrf() ?>
                <input type="hidden" name="action" value="cart_update" />
                <div class="cart-table-heading">
                    <span>Product</span>
                    <span>Quantity & total</span>
                </div>

                <?php foreach ($items as $item): ?>
                <article class="cart-item">
                    <a href="/product/<?php echo e($item['slug']) ?>">
                        <img
                            src="<?php echo e(image_url($item['image'])) ?>"
                            alt="<?php echo e($item['name']) ?>"
                            width="120"
                            height="145"
                        />
                    </a>
                    <div class="cart-item-info">
                        <a href="/product/<?php echo e($item['slug']) ?>">
                            <h3>
                                <?php echo e($item['name']) ?>
                            </h3>
                        </a>
                        <p>
                            <?php echo e($item['color'] . ' / ' . $item['size']) ?>
                        </p>
                        <span>
                            <?php echo money($item['price']) ?>
                        </span>
                        <button
                            class="text-button remove-item"
                            name="remove_variant"
                            value="<?php echo $item['id'] ?>"
                        >
                            Remove
                        </button>
                    </div>
                    <div class="cart-item-end">
                        <label class="sr-only" for="qty-<?php echo $item['id'] ?>">
                            Quantity for

                            <?php echo e($item['name']) ?>
                        </label>
                        <input
                            class="cart-quantity"
                            type="number"
                            id="qty-<?php echo $item['id'] ?>"
                            name="quantities[<?php echo $item['id'] ?>]"
                            value="<?php echo $item['quantity'] ?>"
                            min="0"
                            max="<?php echo $item['stock'] ?>"
                            data-auto-submit
                        />
                        <strong>
                            <?php echo money($item['total']) ?>
                        </strong>
                    </div>
                </article>

                <?php endforeach; ?>
                <div class="cart-update" data-no-js>
                    <span class="muted">Update quantities or remove items above.</span>
                    <button class="button secondary">Update bag</button>
                </div>
            </form>
        </div>
        <aside class="order-summary">
            <h2>Order summary</h2>

            <?php require __DIR__ . '/totals.php'; ?>
            <div class="free-shipping-note">
                <?php echo icon('truck', 19) ?>
                <span>
                    <?php
                    echo $totals['shipping'] ? 'Add ' . money(max(0, convert(setting('free_shipping')) - $totals['subtotal'])) . ' for free delivery.' : 'Your order qualifies for free delivery.'
                    ?>
                </span>
            </div>
            <?php
            $deliveryTarget = max(0, convert(setting('free_shipping')));
            $deliveryGap = max(0, $deliveryTarget - $totals['subtotal']);
            $deliveryProgress = $deliveryTarget > 0 ? min(1, $totals['subtotal'] / $deliveryTarget) : 1;
            ?>
            <div class="delivery-planner">
                <div
                    class="delivery-track"
                    role="progressbar"
                    aria-label="Progress toward free delivery"
                    aria-valuemin="0"
                    aria-valuemax="<?php echo max(1, $deliveryTarget) ?>"
                    aria-valuenow="<?php echo min(max(1, $deliveryTarget), $totals['subtotal']) ?>"
                >
                    <span style="transform: scaleX(<?php echo $deliveryProgress ?>)"></span>
                </div>
                <?php if ($deliveryGap > 0): ?>
                <a class="text-link" href="/shop?max_price=<?php echo (int) ceil($deliveryGap * rate_to_base()) ?>&amp;in_stock=1">
                    Explore within <?php echo money($deliveryGap) ?>
                    <?php echo icon('arrow', 15) ?>
                </a>
                <?php else: ?>
                <p>Free delivery is included with this bag.</p>
                <?php endif; ?>
                <small>Delivery eligibility uses your product subtotal before promo discounts.</small>
            </div>
            <?php $couponReturn = '/cart'; require __DIR__ . '/coupon-form.php'; ?>
            <a class="button dark full-width" href="/checkout">
                Continue to checkout

                <?php echo icon('arrow', 18) ?>
            </a>
            <p class="payment-note">Cash on delivery & bank transfer accepted.</p>
        </aside>
    </div>

    <?php endif; ?>
</section>
