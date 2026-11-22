<div class="admin-page-heading">
    <div class="heading-with-back">
        <a href="/admin/orders" class="icon-button back-button" aria-label="Back to orders">
            <?= icon('arrow') ?>
        </a>
        <div>
            <h1><?= e($order['number']) ?></h1>
            <p><?= date('d F Y · h:i a', strtotime($order['created_at'])) ?> · <?= $order['customer_id'] ? 'Registered customer' : 'Guest checkout' ?></p>
        </div>
    </div>
    <a class="button secondary small" target="_blank" href="/invoice?token=<?= e($order['token']) ?>">
        <?= icon('download', 17) ?> Print invoice
    </a>
</div>
<div class="order-admin-layout">
    <div>
        <section class="admin-card">
            <div class="card-header">
                <h2>Order items</h2>
                <span class="badge <?= e($order['status']) ?>"><?= e(ucfirst($order['status'])) ?></span>
            </div>

            <?php foreach ($orderItems as $item): ?>
            <div class="admin-order-item">
                <img src="<?= e(image_url($item['image'])) ?>" alt="<?= e($item['name']) ?>" />
                <div>
                    <strong><?= e($item['name']) ?></strong>
                    <small><?= e($item['variant']) ?></small>
                    <small><?= e($item['sku']) ?></small>
                </div>
                <span><?= money($item['price'], $order['currency']) ?> × <?= $item['quantity'] ?></span>
                <strong><?= money($item['price'] * $item['quantity'], $order['currency']) ?></strong>
            </div>

            <?php endforeach; ?>
        </section>
        <section class="admin-card padded">
            <div class="section-heading">
                <h2>Payment summary</h2>
                <span class="badge <?= e($order['payment_status']) ?>"><?= e(ucfirst($order['payment_status'])) ?></span>
            </div>

            <?php
            $totals = $order;
            require __DIR__ . '/../totals.php';
            ?>
            <div class="payment-method-line">
                Payment method:
                <strong><?= $order['payment_method'] === 'cod' ? 'Cash on delivery' : 'Bank transfer' ?></strong>
            </div>

            <?php if ($order['coupon']): ?>
            <p class="muted">Promo code: <?= e($order['coupon']) ?></p>

            <?php endif; ?>
        </section>

        <?php if ($order['notes']): ?>
        <section class="admin-card padded">
            <h2>Customer note</h2>
            <p><?= nl2br(e($order['notes'])) ?></p>
        </section>

        <?php endif; ?>
    </div>
    <aside>
        <section class="admin-card padded">
            <h2>Manage order</h2>
            <form method="post">
                <?= csrf() ?>
                <input type="hidden" name="action" value="save_order" />
                <input type="hidden" name="id" value="<?= $order['id'] ?>" />
                <div class="field">
                    <label for="status">Fulfillment status</label>
                    <select name="status" id="status">
                        <?php foreach (['unfulfilled', 'processing', 'shipped', 'delivered', 'cancelled'] as $s): ?>
                        <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>

                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="payment_status">Payment status</label>
                    <select name="payment_status" id="payment_status">
                        <?php foreach (['unpaid', 'paid', 'refunded'] as $s): ?>
                        <option value="<?= $s ?>" <?= $order['payment_status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>

                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="button dark full-width">Save changes</button>
                <p class="field-help">
                    Mark bank transfers paid after verifying receipt. Cancelling an order returns
                    its stock once. Refunded status records a refund; arrange the actual refund
                    separately.
                </p>
            </form>
        </section>
        <section class="admin-card padded">
            <h2>Customer</h2>
            <p>
                <strong><?= e($order['first_name'] . ' ' . $order['last_name']) ?></strong>
                <br />
                <a href="mailto:<?= e($order['email']) ?>"><?= e($order['email']) ?></a>
                <br />
                <?= e($order['phone']) ?>
            </p>
            <hr />
            <h3>Shipping address</h3>
            <p>
                <?= nl2br(e($order['address'])) ?>
                <br />
                <?= e($order['city'] . ' ' . $order['postal']) ?>
                <br />
                Bangladesh
            </p>
            <h3>Billing address</h3>
            <p><?= nl2br(e($order['billing_address'])) ?></p>
        </section>
    </aside>
</div>
