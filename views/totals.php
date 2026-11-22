<dl class="totals">
    <div>
        <dt>Subtotal</dt>
        <dd><?= money($totals['subtotal'], $totals['currency'] ?? null) ?></dd>
    </div>

    <?php if ($totals['discount']): ?>
    <div class="discount-line">
        <dt>Discount</dt>
        <dd>−<?= money($totals['discount'], $totals['currency'] ?? null) ?></dd>
    </div>

    <?php endif; ?>
    <div>
        <dt>Delivery</dt>
        <dd><?= $totals['shipping'] ? money($totals['shipping'], $totals['currency'] ?? null) : 'On us' ?></dd>
    </div>

    <?php if ($totals['tax']): ?>
    <div>
        <dt>Tax</dt>
        <dd><?= money($totals['tax'], $totals['currency'] ?? null) ?></dd>
    </div>

    <?php endif; ?>
    <div class="total-line">
        <dt>
            Total
            <small><?= e($totals['currency'] ?? current_currency_code()) ?></small>
        </dt>
        <dd><?= money($totals['total'], $totals['currency'] ?? null) ?></dd>
    </div>
</dl>
