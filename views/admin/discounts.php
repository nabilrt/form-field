<?php
$coupons = q('SELECT * FROM coupons ORDER BY id DESC')->fetchAll();
$editCoupon = isset($_GET['edit']) ? q('SELECT * FROM coupons WHERE id=?', [(int) $_GET['edit']])->fetch() : null;
$used = q("SELECT coupon,COUNT(*) FROM orders WHERE coupon IS NOT NULL AND status<>'cancelled' GROUP BY coupon")->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<div class="admin-page-heading">
    <div>
        <h1>Discounts & promotions</h1>
        <p>Manage promo codes, minimum spend, and expiry dates.</p>
    </div>
    <span class="date-control"><?= icon('tag', 17) ?> <?= count($coupons) ?> codes</span>
</div>
<div class="admin-split">
    <section class="admin-card">
        <div class="card-header"><h2>Promo codes</h2></div>

        <?php if ($coupons): ?>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Discount</th>
                        <th>Minimum</th>
                        <th>Expires</th>
                        <th>Used</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($coupons as $c):$expired = $c['expires_at'] && $c['expires_at'] < date('Y-m-d'); ?>
                    <tr>
                        <td><strong><?= e($c['code']) ?></strong></td>
                        <td><?= $c['type'] === 'percent' ? $c['amount'] . '%' : money($c['amount']) ?></td>
                        <td><?= money($c['minimum']) ?></td>
                        <td><?= $c['expires_at'] ? date('d M Y', strtotime($c['expires_at'])) : 'No expiry' ?></td>
                        <td>
                            <?= (int) ($used[$c['code']] ?? 0) ?><?= $c['max_uses'] !== null ? ' / ' . (int) $c['max_uses'] : '' ?>
                            <?php if ($c['per_customer'] !== null): ?><br /><small class="muted"><?= (int) $c['per_customer'] ?> per customer</small><?php endif; ?>
                        </td>
                        <td><span class="badge"><?= match (true) {
                            $expired => 'Expired',
                            !$c['active'] => 'Disabled',
                            $c['max_uses'] !== null && ($used[$c['code']] ?? 0) >= $c['max_uses'] => 'Used up',
                            default => 'Active',
                        } ?></span></td>
                        <td class="row-actions">
                            <a class="text-button" href="/admin/discounts?edit=<?= (int) $c['id'] ?>#coupon-form">Edit</a>
                            <form method="post">
                                <?= csrf() ?>
                                <input type="hidden" name="action" value="toggle_coupon" />
                                <input type="hidden" name="id" value="<?= $c['id'] ?>" />
                                <button class="text-button"><?= $c['active'] ? 'Disable' : 'Enable' ?></button>
                            </form>
                        </td>
                    </tr>

                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php else: ?>
        <div class="empty-state compact">
            <?= icon('tag', 32) ?>
            <h3>No promo codes yet.</h3>
            <p>Create a percentage or fixed-amount discount.</p>
        </div>

        <?php endif; ?>
        <div class="card-footer-note">
            One promo code per order. Discounts apply to products before delivery and tax.
        </div>
    </section>
    <section class="admin-card padded">
        <h2 id="coupon-form"><?= $editCoupon ? 'Edit ' . e($editCoupon['code']) : 'Create a promo code' ?></h2>
        <form method="post">
            <?= csrf() ?>
            <input type="hidden" name="action" value="save_coupon" />
            <input type="hidden" name="id" value="<?= (int) ($editCoupon['id'] ?? 0) ?>" />
            <div class="field">
                <label for="code">Promo code</label>
                <?php if ($editCoupon): ?>
                <input id="code" value="<?= e($editCoupon['code']) ?>" disabled />
                <small>Codes can’t be renamed, so usage history stays accurate. Create a new code instead.</small>
                <?php else: ?>
                <input
                    id="code"
                    name="code"
                    placeholder="e.g. ALOVELY10"
                    required
                    pattern="[A-Za-z0-9_-]{2,30}"
                    maxlength="30"
                />
                <small>Letters, numbers, hyphens, underscores. Case insensitive.</small>
                <?php endif; ?>
            </div>
            <div class="field">
                <label for="type">Discount type</label>
                <select id="type" name="type">
                    <option value="percent">Percentage off (%)</option>
                    <option value="fixed" <?= ($editCoupon['type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Fixed amount off (৳)</option>
                </select>
            </div>
            <div class="form-grid">
                <div class="field">
                    <label for="amount">Discount value</label>
                    <input id="amount" name="amount" type="number" min="1" required value="<?= e($editCoupon['amount'] ?? '') ?>" />
                </div>
                <div class="field">
                    <label for="minimum">Minimum spend (৳)</label>
                    <input id="minimum" name="minimum" type="number" min="0" value="<?= e($editCoupon['minimum'] ?? '0') ?>" required />
                </div>
            </div>
            <div class="field">
                <label for="expires_at">
                    Expiry date
                    <span class="muted">(optional)</span>
                </label>
                <input id="expires_at" name="expires_at" type="date" value="<?= e($editCoupon['expires_at'] ?? '') ?>" <?= $editCoupon ? '' : 'min="' . date('Y-m-d') . '"' ?> />
                <small>Valid through the selected date, Bangladesh time.</small>
            </div>
            <div class="form-grid">
                <div class="field">
                    <label for="max_uses">Total uses <span class="muted">(optional)</span></label>
                    <input id="max_uses" name="max_uses" type="number" min="1" placeholder="Unlimited" value="<?= e($editCoupon['max_uses'] ?? '') ?>" />
                </div>
                <div class="field">
                    <label for="per_customer">Uses per customer <span class="muted">(optional)</span></label>
                    <input id="per_customer" name="per_customer" type="number" min="1" placeholder="Unlimited" value="<?= e($editCoupon['per_customer'] ?? '') ?>" />
                </div>
            </div>
            <small class="muted">Cancelled orders don’t count toward limits.</small>
            <?php if ($editCoupon): ?>
            <button class="button dark full-width">Save changes <?= icon('check', 16) ?></button>
            <a class="subtle-link" href="/admin/discounts">Cancel editing</a>
            <?php else: ?>
            <button class="button dark full-width">Create promo code <?= icon('plus', 16) ?></button>
            <?php endif; ?>
        </form>
    </section>
</div>
<section class="promotion-note">
    <div>
        <?= icon('truck', 23) ?>
        <h2>Free delivery promotion</h2>
        <p>Offer free delivery above your preferred order amount.</p>
    </div>
    <a class="button secondary small" href="/admin/settings">Delivery settings <?= icon('arrow', 16) ?></a>
</section>
