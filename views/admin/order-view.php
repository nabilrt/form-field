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

        <?php if (returns_available() && ($orderReturns = order_returns((int) $order['id']))):
            $currency = currency($order['currency']);
            $scale = 10 ** (int) $currency['decimals'];
        ?>
        <section class="admin-card padded" id="returns">
            <div class="section-heading">
                <h2>Returns</h2>
                <?php if ($refunded = order_refunded((int) $order['id'])): ?>
                <span class="muted">Refunded so far: <?= money($refunded, $order['currency']) ?></span>
                <?php endif; ?>
            </div>
            <?php foreach ($orderReturns as $r): ?>
            <div class="admin-return">
                <div class="admin-return-head">
                    <strong>Return #<?= (int) $r['id'] ?></strong>
                    <span class="badge <?= e($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span>
                </div>
                <p class="muted">
                    <?= e(RETURN_REASONS[$r['reason']] ?? $r['reason']) ?> · requested <?= date('d M Y, h:i a', strtotime($r['created_at'])) ?>
                </p>
                <?php if ($r['note']): ?><p>“<?= nl2br(e($r['note'])) ?>”</p><?php endif; ?>
                <ul>
                    <?php foreach ($r['items'] as $item): ?>
                    <li><?= (int) $item['quantity'] ?> × <?= e($item['name']) ?> <small class="muted"><?= e($item['variant']) ?> · <?= money($item['price'], $order['currency']) ?></small></li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($r['status'] === 'requested'): ?>
                <div class="row-actions return-actions">
                    <form method="post">
                        <?= csrf() ?>
                        <input type="hidden" name="action" value="return_receive" />
                        <input type="hidden" name="return_id" value="<?= (int) $r['id'] ?>" />
                        <button class="button dark small">Mark received &amp; restock</button>
                    </form>
                    <form method="post" data-confirm="Reject this return? The customer will see it wasn’t accepted.">
                        <?= csrf() ?>
                        <input type="hidden" name="action" value="return_reject" />
                        <input type="hidden" name="return_id" value="<?= (int) $r['id'] ?>" />
                        <button class="text-button">Reject</button>
                    </form>
                </div>
                <?php elseif ($r['status'] === 'received'): ?>
                <form method="post" class="return-refund">
                    <?= csrf() ?>
                    <input type="hidden" name="action" value="return_refund" />
                    <input type="hidden" name="return_id" value="<?= (int) $r['id'] ?>" />
                    <label for="refund-<?= (int) $r['id'] ?>">Refund (<?= e($currency['symbol']) ?>)</label>
                    <input
                        id="refund-<?= (int) $r['id'] ?>"
                        name="refund_amount"
                        type="number"
                        min="0"
                        step="<?= $currency['decimals'] ? 1 / $scale : 1 ?>"
                        value="<?= e(number_format(suggested_refund($order, $r['items']) / $scale, (int) $currency['decimals'], '.', '')) ?>"
                        required
                    />
                    <button class="button dark small">Record refund</button>
                    <small class="field-help">Suggested: item prices minus their share of the order discount. Pay it the way the customer paid.</small>
                </form>
                <?php elseif ($r['status'] === 'refunded'): ?>
                <p><strong>Refunded <?= money((int) $r['refund_amount'], $order['currency']) ?></strong></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </section>
        <?php endif; ?>

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
