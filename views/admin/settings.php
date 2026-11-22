<div class="admin-page-heading">
    <div>
        <h1>Settings</h1>
        <p>Manage store details, payments, delivery, and tax.</p>
    </div>
    <span class="date-control">BDT ৳ · Bangladesh</span>
</div>

<?php
$settingsTabs = settings_groups();
$settingsTabs['security'] = ['label' => 'Security'];
?>
<div class="settings-workspace" data-settings-tabs>
    <div class="settings-tabs" role="tablist" aria-label="Store settings" hidden>
        <?php foreach ($settingsTabs as $key => $tab): ?>
        <button
            type="button"
            id="settings-tab-<?= e($key) ?>"
            class="settings-tab"
            role="tab"
            aria-controls="settings-<?= e($key) ?>"
            aria-selected="<?= $key === 'general' ? 'true' : 'false' ?>"
            tabindex="<?= $key === 'general' ? '0' : '-1' ?>"
            data-settings-tab="<?= e($key) ?>"
        >
            <?= e($tab['label']) ?>
        </button>

        <?php endforeach; ?>
    </div>
    <section
        id="settings-general"
        class="settings-panel"
        data-settings-panel="general"
        aria-labelledby="settings-tab-general"
    >
        <form method="post" class="settings-form" data-loading="Saving…">
            <?= csrf() ?>
            <input type="hidden" name="action" value="save_settings" />
            <input type="hidden" name="settings_group" value="general" />
            <div class="settings-row">
                <div>
                    <h2>Store details</h2>
                    <p>Your name, your contact details, your home base.</p>
                </div>
                <section class="admin-card padded">
                    <div class="field">
                        <label for="store_name">Store name</label>
                        <input
                            id="store_name"
                            name="store_name"
                            value="<?= e(setting('store_name')) ?>"
                            required
                            maxlength="190"
                        />
                    </div>
                    <div class="form-grid">
                        <div class="field">
                            <label for="store_email">Contact email</label>
                            <input
                                id="store_email"
                                name="store_email"
                                type="email"
                                value="<?= e(setting('store_email')) ?>"
                                required
                            />
                        </div>
                        <div class="field">
                            <label for="store_phone">Phone number</label>
                            <input
                                id="store_phone"
                                name="store_phone"
                                value="<?= e(setting('store_phone')) ?>"
                                type="tel"
                            />
                        </div>
                    </div>
                    <div class="field">
                        <label for="store_address">Business address (shown on invoices)</label>
                        <textarea id="store_address" name="store_address" rows="3" required>
<?= e(setting('store_address')) ?></textarea
                        >
                    </div>
                </section>
            </div>
            <div class="settings-save">
                <button type="submit" class="button dark">
                    Save store details <?= icon('check', 17) ?>
                </button>
            </div>
        </form>
    </section>
    <section
        id="settings-delivery"
        class="settings-panel"
        data-settings-panel="delivery"
        aria-labelledby="settings-tab-delivery"
    >
        <form method="post" class="settings-form" data-loading="Saving…">
            <?= csrf() ?>
            <input type="hidden" name="action" value="save_settings" />
            <input type="hidden" name="settings_group" value="delivery" />
            <div class="settings-row">
                <div>
                    <h2>Delivery & tax</h2>
                    <p>Simple, predictable pricing for your customers.</p>
                </div>
                <section class="admin-card padded">
                    <div class="form-grid">
                        <div class="field">
                            <label for="shipping">Standard delivery (৳)</label>
                            <input
                                id="shipping"
                                name="shipping"
                                type="number"
                                min="0"
                                value="<?= e(setting('shipping')) ?>"
                                required
                            />
                        </div>
                        <div class="field">
                            <label for="free_shipping">Free delivery from (৳)</label>
                            <input
                                id="free_shipping"
                                name="free_shipping"
                                type="number"
                                min="0"
                                value="<?= e(setting('free_shipping')) ?>"
                                required
                            />
                        </div>
                    </div>
                    <div class="field">
                        <label for="tax_rate">Tax rate (%)</label>
                        <input
                            id="tax_rate"
                            name="tax_rate"
                            type="number"
                            min="0"
                            max="100"
                            step="0.01"
                            value="<?= e(setting('tax_rate')) ?>"
                            required
                        />
                        <small>
                            Calculated on the discounted product subtotal. Set your applicable rate.
                        </small>
                    </div>
                    <div class="field">
                        <label for="return_window_days">Return window (days)</label>
                        <input
                            id="return_window_days"
                            name="return_window_days"
                            type="number"
                            min="0"
                            max="365"
                            step="1"
                            value="<?= e(setting('return_window_days', '30')) ?>"
                            required
                        />
                        <small>How long after ordering shoppers can request a return online. Use 0 to turn online returns off.</small>
                    </div>
                </section>
            </div>
            <div class="settings-save">
                <button type="submit" class="button dark">
                    Save delivery & tax <?= icon('check', 17) ?>
                </button>
            </div>
        </form>
    </section>
    <section
        id="settings-payments"
        class="settings-panel"
        data-settings-panel="payments"
        aria-labelledby="settings-tab-payments"
    >
        <form method="post" class="settings-form" data-loading="Saving…">
            <?= csrf() ?>
            <input type="hidden" name="action" value="save_settings" />
            <input type="hidden" name="settings_group" value="payments" />
            <div class="settings-row">
                <div>
                    <h2>Payments</h2>
                    <p>Cash on delivery is available. Add bank details to enable bank transfer.</p>
                </div>
                <section class="admin-card padded">
                    <div class="field">
                        <label for="bank_details">Bank transfer instructions</label>
                        <textarea
                            id="bank_details"
                            name="bank_details"
                            rows="6"
                            placeholder="Bank name&#10;Account holder&#10;Account number&#10;Branch / routing number"
                        >
<?= e(setting('bank_details')) ?></textarea
                        >
                        <small>
                            Shown at checkout, on order confirmation, and on the invoice. Leave
                            blank to disable bank transfer.
                        </small>
                    </div>
                </section>
            </div>
            <div class="settings-save">
                <button type="submit" class="button dark">
                    Save payment settings <?= icon('check', 17) ?>
                </button>
            </div>
        </form>
    </section>
    <section
        id="settings-storefront"
        class="settings-panel"
        data-settings-panel="storefront"
        aria-labelledby="settings-tab-storefront"
    >
        <form method="post" class="settings-form" data-loading="Saving…">
            <?= csrf() ?>
            <input type="hidden" name="action" value="save_settings" />
            <input type="hidden" name="settings_group" value="storefront" />
            <div class="settings-row">
                <div>
                    <h2>Storefront announcement</h2>
                    <p>A useful message at the very top of your store.</p>
                </div>
                <section class="admin-card padded">
                    <div class="field">
                        <label for="announcement">Announcement text</label>
                        <textarea id="announcement" name="announcement" rows="3" maxlength="500">
<?= e(setting('announcement')) ?></textarea
                        >
                    </div>
                </section>
            </div>
            <div class="settings-save">
                <button type="submit" class="button dark">
                    Save storefront settings <?= icon('check', 17) ?>
                </button>
            </div>
        </form>
    </section>
    <section
        id="settings-security"
        class="settings-panel"
        data-settings-panel="security"
        aria-labelledby="settings-tab-security"
    >
        <div class="settings-row">
            <div>
                <h2>Account security</h2>
                <p>Keep your merchant account protected.</p>
            </div>
            <section class="admin-card padded">
                <form method="post">
                    <?= csrf() ?>
                    <input type="hidden" name="action" value="change_password" />
                    <div class="field">
                        <label for="current_password">Current password</label>
                        <input
                            id="current_password"
                            type="password"
                            name="current_password"
                            autocomplete="current-password"
                            required
                        />
                    </div>
                    <div class="field">
                        <label for="new_password">New password</label>
                        <input
                            id="new_password"
                            type="password"
                            name="new_password"
                            autocomplete="new-password"
                            minlength="12"
                            required
                        />
                        <small>At least 12 characters.</small>
                    </div>
                    <button class="button secondary">Update password</button>
                </form>
            </section>
        </div>
    </section>
</div>
