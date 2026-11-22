<section class="section order-confirmation">
    <div class="success-mark"><?= icon('check', 32) ?></div>
    <h1>Your order is confirmed.</h1>
    <p>
        Thank you, <?= e($order['first_name']) ?>. Your order
        <strong><?= e($order['number']) ?></strong>
        is confirmed.
    </p>
    <p class="muted">Save this page to check your order and access your invoice.</p>
    <div class="confirmation-card">
        <div class="section-heading">
            <h2>Order details</h2>
            <span class="badge"><?= e(ucfirst($order['status'])) ?></span>
        </div>

        <?php foreach (q('SELECT * FROM order_items WHERE order_id=?', [$order['id']])->fetchAll() as $item): ?>
        <div class="checkout-item">
            <img src="<?= e(image_url($item['image'])) ?>" alt="<?= e($item['name']) ?>" class="order-product-image" />
            <div>
                <strong><?= e($item['name']) ?></strong>
                <small><?= e($item['variant']) ?> · Qty <?= $item['quantity'] ?></small>
            </div>
            <span><?= money($item['price'] * $item['quantity'], $order['currency']) ?></span>
        </div>

        <?php
        endforeach;
        $totals = $order;
        require __DIR__ . '/totals.php';
        ?>
        <div class="confirmation-addresses">
            <div>
                <h3>Delivering to</h3>
                <p>
                    <?= e($order['first_name'] . ' ' . $order['last_name']) ?>
                    <br />
                    <?= nl2br(e($order['address'])) ?>
                    <br />
                    <?= e($order['city'] . ' ' . $order['postal']) ?>
                    <br />
                    <?= e($order['phone']) ?>
                </p>
            </div>
            <div>
                <h3>Payment</h3>
                <p>
                    <?= $order['payment_method'] === 'cod' ? 'Cash on delivery' : 'Bank transfer' ?>
                    <br />
                    <span class="badge <?= e($order['payment_status']) ?>"><?= e(ucfirst($order['payment_status'])) ?></span>
                </p>
            </div>
        </div>

        <?php if ($order['payment_method'] === 'bank'): ?>
        <div class="bank-details">
            <h3>Bank transfer details</h3>
            <?= nl2br(e(setting('bank_details'))) ?>
            <p>
                Reference:
                <strong><?= e($order['number']) ?></strong>
            </p>
            <p>Your order stays unpaid until your transfer is verified.</p>
        </div>

        <?php endif; ?>
        <a
            class="button secondary full-width"
            href="/invoice?token=<?= e($order['token']) ?>"
            target="_blank"
            rel="noopener"
        >
            <?= icon('download', 18) ?> View & print invoice
        </a>
    </div>
    <a class="text-link" href="/shop">Find something else to love <?= icon('arrow', 18) ?></a>
</section>
