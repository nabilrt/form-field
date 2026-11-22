<?php
$currencies = currency_list();
$editCurrency = isset($_GET['edit']) && is_string($_GET['edit']) && $_GET['edit'] !== 'BDT' ? ($currencies[$_GET['edit']] ?? null) : null;
$priced = q('SELECT currency,COUNT(*) FROM variant_prices GROUP BY currency')->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<div class="admin-page-heading">
    <div>
        <h1>Currencies</h1>
        <p>Let shoppers browse and buy in their own currency. BDT is your base currency.</p>
    </div>
    <span class="date-control"><?= icon('currency', 17) ?> <?= count(currency_list(true)) ?> active</span>
</div>
<div class="admin-split">
    <section class="admin-card">
        <div class="card-header"><h2>Store currencies</h2></div>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Currency</th>
                        <th>Example</th>
                        <th>Exchange rate</th>
                        <th>Set prices</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($currencies as $c): $isBase = $c['code'] === 'BDT'; ?>
                    <tr>
                        <td><strong><?= e($c['code']) ?></strong> <?= e($c['symbol']) ?></td>
                        <td><?= e(money(convert(3450, $c['code']), $c['code'])) ?></td>
                        <td><?= $isBase ? 'Base currency' : '1 ' . e($c['code']) . ' = ' . e((string) (float) $c['rate']) . ' BDT' ?></td>
                        <td><?= $isBase ? '—' : (int) ($priced[$c['code']] ?? 0) . ' variants' ?></td>
                        <td><span class="badge"><?= $c['active'] ? 'Active' : 'Hidden' ?></span></td>
                        <td class="row-actions">
                            <?php if (!$isBase): ?>
                            <a class="text-button" href="/admin/currencies?edit=<?= e($c['code']) ?>#currency-form">Edit</a>
                            <form method="post">
                                <?= csrf() ?>
                                <input type="hidden" name="action" value="toggle_currency" />
                                <input type="hidden" name="code" value="<?= e($c['code']) ?>" />
                                <button class="text-button"><?= $c['active'] ? 'Hide' : 'Show' ?></button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer-note">
            Variants without their own price convert from BDT at the rate. Delivery fees, the free-delivery
            threshold and fixed discounts always convert. Hidden currencies keep their past orders.
        </div>
    </section>
    <section class="admin-card padded">
        <h2 id="currency-form"><?= $editCurrency ? 'Edit ' . e($editCurrency['code']) : 'Add a currency' ?></h2>
        <form method="post">
            <?= csrf() ?>
            <input type="hidden" name="action" value="save_currency" />
            <div class="form-grid">
                <div class="field">
                    <label for="currency-code">ISO code</label>
                    <?php if ($editCurrency): ?>
                    <input id="currency-code" value="<?= e($editCurrency['code']) ?>" disabled />
                    <input type="hidden" name="code" value="<?= e($editCurrency['code']) ?>" />
                    <?php else: ?>
                    <input id="currency-code" name="code" placeholder="USD" required pattern="[A-Za-z]{3}" maxlength="3" />
                    <?php endif; ?>
                </div>
                <div class="field">
                    <label for="currency-symbol">Symbol</label>
                    <input id="currency-symbol" name="symbol" placeholder="$" required maxlength="8" value="<?= e($editCurrency['symbol'] ?? '') ?>" />
                </div>
            </div>
            <div class="form-grid">
                <div class="field">
                    <label for="currency-rate">1 unit = how many BDT</label>
                    <input id="currency-rate" name="rate" type="number" min="0.000001" step="any" required placeholder="e.g. 120" value="<?= e($editCurrency ? (string) (float) $editCurrency['rate'] : '') ?>" />
                </div>
                <div class="field">
                    <label for="currency-decimals">Decimal places</label>
                    <?php if ($editCurrency): ?>
                    <input id="currency-decimals" value="<?= (int) $editCurrency['decimals'] ?>" disabled />
                    <input type="hidden" name="decimals" value="<?= (int) $editCurrency['decimals'] ?>" />
                    <?php else: ?>
                    <input id="currency-decimals" name="decimals" type="number" min="0" max="3" value="2" required />
                    <?php endif; ?>
                </div>
            </div>
            <div class="field">
                <label for="currency-position">Order in the switcher</label>
                <input id="currency-position" name="position" type="number" value="<?= (int) ($editCurrency['position'] ?? count($currencies)) ?>" />
                <small>Decimal places can’t change later, because saved prices and orders depend on them.</small>
            </div>
            <?php if ($editCurrency): ?>
            <button class="button dark full-width">Save changes <?= icon('check', 16) ?></button>
            <a class="subtle-link" href="/admin/currencies">Cancel editing</a>
            <?php else: ?>
            <button class="button dark full-width">Add currency <?= icon('plus', 16) ?></button>
            <?php endif; ?>
        </form>
    </section>
</div>
