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

    <?php if (returns_available()):
        $orderReturns = order_returns((int) $order['id']);
        $returnable = array_filter(returnable_items((int) $order['id']), static fn ($i) => (int) $i['returnable'] > 0);
        $blocked = return_block_reason($order);
    ?>
    <div class="confirmation-card returns-card" id="returns">
        <div class="section-heading">
            <h2>Returns</h2>
            <?php if (!$blocked): ?><span class="muted">Within <?= (int) setting('return_window_days', '30') ?> days of ordering</span><?php endif; ?>
        </div>

        <?php foreach ($orderReturns as $r): ?>
        <div class="return-summary">
            <div>
                <strong>Return #<?= (int) $r['id'] ?></strong>
                <small><?= e(RETURN_REASONS[$r['reason']] ?? $r['reason']) ?> · requested <?= date('d M Y', strtotime($r['created_at'])) ?></small>
                <small><?= e(implode(', ', array_map(static fn ($i) => $i['quantity'] . ' × ' . $i['name'], $r['items']))) ?></small>
            </div>
            <span class="badge <?= e($r['status']) ?>"><?= e(match ($r['status']) {
                'requested' => 'Under review',
                'received' => 'Received',
                'refunded' => 'Refunded ' . money((int) $r['refund_amount'], $order['currency']),
                default => 'Not accepted',
            }) ?></span>
        </div>
        <?php endforeach; ?>

        <?php if ($blocked): ?>
        <p class="muted"><?= e($blocked) ?></p>
        <?php elseif (!$returnable): ?>
        <p class="muted">Every item in this order already has a return.</p>
        <?php else: ?>
        <form method="post" class="return-form">
            <?= csrf() ?>
            <input type="hidden" name="action" value="return_request" />
            <input type="hidden" name="token" value="<?= e($order['token']) ?>" />
            <p>Choose what you’d like to send back.</p>
            <?php foreach ($returnable as $item): ?>
            <div class="return-line">
                <label for="return-<?= (int) $item['id'] ?>">
                    <strong><?= e($item['name']) ?></strong>
                    <small><?= e($item['variant']) ?> · <?= money($item['price'], $order['currency']) ?> each</small>
                </label>
                <input
                    id="return-<?= (int) $item['id'] ?>"
                    type="number"
                    name="return_qty[<?= (int) $item['id'] ?>]"
                    min="0"
                    max="<?= (int) $item['returnable'] ?>"
                    value="0"
                    aria-describedby="return-max-<?= (int) $item['id'] ?>"
                />
                <small id="return-max-<?= (int) $item['id'] ?>">of <?= (int) $item['returnable'] ?></small>
            </div>
            <?php endforeach; ?>
            <div class="field">
                <label for="return-reason">Reason</label>
                <select id="return-reason" name="reason" required>
                    <option value="">Choose a reason</option>
                    <?php foreach (RETURN_REASONS as $value => $label): ?>
                    <option value="<?= e($value) ?>"><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="return-note">Anything we should know? <span class="muted">(optional)</span></label>
                <textarea id="return-note" name="note" rows="3" maxlength="1000"></textarea>
            </div>
            <button class="button secondary full-width">Request a return <?= icon('return', 17) ?></button>
        </form>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <a class="text-link" href="/shop">Find something else to love <?= icon('arrow', 18) ?></a>
</section>
