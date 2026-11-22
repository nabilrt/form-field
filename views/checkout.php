<?php
$items = cart_items();
$totals = cart_totals($items);
$old = $_SESSION['checkout_old'] ?? [];
$checkoutCustomer = null;
$savedAddresses = [];
if (!empty($_SESSION['customer_id'])) {
    $checkoutCustomer = q('SELECT * FROM customers WHERE id=?', [$_SESSION['customer_id']])->fetch();
    $savedAddresses = q('SELECT * FROM addresses WHERE customer_id=? ORDER BY is_default DESC,id DESC', [$_SESSION['customer_id']])->fetchAll();
    if (!$old && $savedAddresses) {
        $old = $savedAddresses[0];
    }
    $old['email'] ??= $checkoutCustomer['email'] ?? '';
    $old['phone'] ??= $checkoutCustomer['phone'] ?? '';
}
$_SESSION['checkout_nonce'] ??= bin2hex(random_bytes(24));
?>
<section class="section checkout-page">
    <div class="breadcrumb">
        <a href="/cart">Bag</a>
        <span>/</span>
        <span>Checkout</span>
    </div>
    <h1>Checkout</h1>
    <div class="checkout-layout">
        <form method="post" class="checkout-form" id="checkout-form" data-loading="Placing your order…">
            <?= csrf() ?>
            <input type="hidden" name="action" value="place_order" />
            <input type="hidden" name="checkout_nonce" value="<?= e($_SESSION['checkout_nonce']) ?>" />
            <input type="hidden" name="currency" value="<?= e(current_currency_code()) ?>" />
            <section>
                <div class="form-section-title">
                    <h2>Contact</h2>

                    <?php if (!$checkoutCustomer): ?>
                    <a href="/account/login">Have an account? Sign in</a>

                    <?php else: ?>
                    <span><?= e($checkoutCustomer['name']) ?></span>

                    <?php endif; ?>
                </div>
                <div class="field">
                    <label for="email">Email address</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        required
                        value="<?= e($old['email'] ?? '') ?>"
                        autocomplete="email"
                    />
                    <small>Your order confirmation is available on the next page.</small>
                </div>
            </section>
            <section>
                <h2>Delivery address</h2>

                <?php if ($savedAddresses): ?>
                <div class="field">
                    <label for="saved-address">Use a saved address</label>
                    <select id="saved-address" data-addresses="<?= e(json_encode($savedAddresses)) ?>">
                        <option value="">Enter a new address</option>

                        <?php foreach ($savedAddresses as $a): ?>
                        <option value="<?= $a['id'] ?>"><?= e($a['label'] . ' — ' . $a['address']) ?></option>

                        <?php endforeach; ?>
                    </select>
                </div>

                <?php endif; ?>
                <div class="form-grid">
                    <?php
                    foreach ([
                            'first_name' => 'First name',
                            'last_name' => 'Last name',
                            'phone' => 'Phone number',
                            'address' => 'Street address',
                            'city' => 'City / district',
                            'postal' => 'Postal code',
                    ] as $key => $label):
                    ?>
                    <div class="field <?= $key === 'address' || $key === 'phone' ? 'span-2' : '' ?>">
                        <label for="<?= $key ?>"><?= $label ?></label>
                        <input
                            id="<?= $key ?>"
                            name="<?= $key ?>"
                            value="<?= e($old[$key] ?? '') ?>"
                            required
                            maxlength="<?= $key === 'address' ? '500' : '100' ?>"
                            autocomplete="<?= match($key) {
                                'first_name' => 'given-name','last_name' => 'family-name','phone' => 'tel','address' => 'street-address','city' => 'address-level2','postal' => 'postal-code'
                            } ?>"
                            <?= $key === 'phone' ? 'type="tel"' : '' ?>
                        />
                    </div>

                    <?php endforeach; ?>
                </div>
                <p class="country-note">Delivering to Bangladesh</p>
                <label class="checkbox-row">
                    <input
                        type="checkbox"
                        name="different_billing"
                        data-reveal="billing-fields"
                        <?= isset($old['different_billing']) ? 'checked' : '' ?>
                    />
                    Use a different billing address
                </label>
                <div class="field" id="billing-fields" <?= isset($old['different_billing']) ? '' : 'hidden' ?>>
                    <label for="billing_address">Full billing address</label>
                    <textarea id="billing_address" name="billing_address" rows="3">
<?= e($old['billing_address'] ?? '') ?></textarea
                    >
                </div>
            </section>
            <section>
                <h2>Payment</h2>
                <p class="muted">Choose what works for you.</p>
                <label class="payment-option">
                    <input
                        type="radio"
                        name="payment_method"
                        value="cod"
                        <?= ($old['payment_method'] ?? 'cod') === 'cod' ? 'checked' : '' ?>
                        required
                    />
                    <span>
                        <strong>Cash on delivery</strong>
                        <small>Pay in cash when your order arrives.</small>
                    </span>
                    <?= icon('truck', 21) ?>
                </label>
                <label class="payment-option <?= !trim(setting('bank_details')) ? 'unavailable' : '' ?>">
                    <input
                        type="radio"
                        name="payment_method"
                        value="bank"
                        <?= ($old['payment_method'] ?? '') === 'bank' ? 'checked' : '' ?>
                        <?= !trim(setting('bank_details')) ? 'disabled' : '' ?>
                    />
                    <span>
                        <strong>Bank transfer</strong>
                        <small><?= trim(setting('bank_details')) ? 'Transfer after placing your order. Details below.' : 'Available once the store adds its bank details.' ?></small>
                    </span>
                    <?= icon('orders', 21) ?>
                </label>

                <?php if (trim(setting('bank_details'))): ?>
                <div class="bank-details">
                    <?= nl2br(e(setting('bank_details'))) ?>
                    <p>
                        Use your order number as the transfer reference. Your order stays unpaid
                        until your transfer is verified.
                    </p>
                </div>

                <?php endif; ?>
            </section>
            <div class="field">
                <label for="notes">
                    Anything we should know?
                    <span class="muted">(optional)</span>
                </label>
                <textarea
                    id="notes"
                    name="notes"
                    rows="3"
                    maxlength="2000"
                    placeholder="Delivery instructions, a note for us…"
                >
<?= e($old['notes'] ?? '') ?></textarea
                >
            </div>
            <button class="button dark full-width" type="submit">
                Place order · <?= money($totals['total']) ?> <?= icon('arrow', 18) ?>
            </button>
            <p class="payment-note">
                Please check your address and order before placing your order.
            </p>
        </form>
        <aside class="order-summary checkout-summary">
            <h2>Your order</h2>

            <?php foreach ($items as $item): ?>
            <div class="checkout-item">
                <div class="checkout-thumb">
                    <img src="<?= e(image_url($item['image'])) ?>" alt="<?= e($item['name']) ?>" />
                    <span><?= $item['quantity'] ?></span>
                </div>
                <div>
                    <strong><?= e($item['name']) ?></strong>
                    <small><?= e($item['color'] . ' / ' . $item['size']) ?></small>
                </div>
                <span><?= money($item['total']) ?></span>
            </div>

            <?php endforeach; ?>

            <?php $couponReturn = '/checkout'; require __DIR__ . '/coupon-form.php'; ?>
            <?php require __DIR__ . '/totals.php'; ?>
            <a class="text-link" href="/cart">Edit your bag</a>
        </aside>
    </div>
</section>
