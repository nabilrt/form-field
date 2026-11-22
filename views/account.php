<?php
$customerOrders = [];
$totalOrders = 0;
$orderPage = 1;
$orderPages = 1;
if (in_array($accountPage, ['overview', 'orders'], true)) {
    $totalOrders = (int) q('SELECT COUNT(*) FROM orders WHERE customer_id=?', [$customer['id']])->fetchColumn();
    $orderLimit = $accountPage === 'overview' ? 5 : 20;
    $orderPages = max(1, (int) ceil($totalOrders / $orderLimit));
    $orderPage = max(1, min($orderPages, (int) ($_GET['page'] ?? 1)));
    $customerOrders = q(
        'SELECT * FROM orders WHERE customer_id=? ORDER BY id DESC LIMIT ? OFFSET ?',
        [$customer['id'], $orderLimit, ($orderPage - 1) * $orderLimit]
    )->fetchAll();
}
?>
<section class="section account-page">
    <div class="section-heading">
        <div>
            <h1>Your account</h1>
            <p>Good to see you, <?= e(explode(' ', $customer['name'])[0]) ?>.</p>
        </div>
        <form method="post">
            <?= csrf() ?>
            <input type="hidden" name="action" value="customer_logout" />
            <button class="text-link text-button">Sign out <?= icon('logout', 17) ?></button>
        </form>
    </div>
    <div class="account-layout">
        <nav class="account-nav" aria-label="Account navigation">
            <?php
            foreach ([
                    'overview' => ['home', 'Overview'],
                    'orders' => ['orders', 'Your orders'],
                    'addresses' => ['truck', 'Address book'],
                    'profile' => ['users', 'Your details'],
            ] as $key => $link):
            ?>
            <a class="<?= $accountPage === $key ? 'active' : '' ?>" href="/account<?= $key === 'overview' ? '' : '/' . $key ?>">
                <?= icon($link[0], 19) ?> <?= $link[1] ?>
            </a>

            <?php endforeach; ?>
        </nav>
        <div class="account-content">
            <?php if (in_array($accountPage, ['overview', 'orders'], true)): ?>

            <?php if ($accountPage === 'overview'): ?>
            <div class="account-welcome">
                <h2>Manage your account</h2>
                <p>
                    Keep track of your orders, save a delivery address, and make your next visit a
                    little easier.
                </p>
                <a class="text-link" href="/shop">Find your next favorite <?= icon('arrow', 17) ?></a>
            </div>

            <?php endif; ?>
            <div class="section-heading">
                <h2><?= $accountPage === 'orders' ? 'Your orders' : 'Recent orders' ?></h2>
                <span><?= $totalOrders ?> orders</span>
            </div>

            <?php if (!$customerOrders): ?>
            <div class="empty-state compact">
                <?= icon('bag', 35) ?>
                <h3>No orders yet.</h3>
                <p>Orders placed while signed in will appear here.</p>
                <a class="button dark" href="/shop">Explore the collection</a>
            </div>

            <?php else: ?>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customerOrders as $o): ?>
                        <tr>
                            <td>
                                <a href="/order?token=<?= e($o['token']) ?>">
                                    <strong><?= e($o['number']) ?></strong>
                                </a>
                            </td>
                            <td><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                            <td><span class="badge <?= e($o['status']) ?>"><?= e(ucfirst($o['status'])) ?></span></td>
                            <td><?= money($o['total'], $o['currency']) ?></td>
                            <td>
                                <a class="text-link" href="/invoice?token=<?= e($o['token']) ?>">
                                    Invoice <?= icon('download', 15) ?>
                                </a>
                                <form method="post" class="reorder-form">
                                    <?= csrf() ?>
                                    <input type="hidden" name="action" value="customer_reorder" />
                                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>" />
                                    <button type="submit" class="text-button">Buy again</button>
                                </form>
                            </td>
                        </tr>

                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php endif; ?>

            <?php if ($accountPage === 'orders' && $orderPages > 1): ?>
            <nav class="pagination" aria-label="Order history pages">
                <?php for ($i = 1; $i <= $orderPages; $i++): ?>
                <a class="button secondary small" href="/account/orders?page=<?= $i ?>">
                    <?= $i ?>
                </a>

                <?php endfor; ?>
            </nav>

            <?php endif; ?>

            <?php
            elseif ($accountPage === 'addresses'):$addresses = q('SELECT * FROM addresses WHERE customer_id=? ORDER BY is_default DESC,id DESC', [$customer['id']])->fetchAll();
            $editingAddress = isset($_GET['edit']) ? q('SELECT * FROM addresses WHERE id=? AND customer_id=?', [(int) $_GET['edit'], $customer['id']])->fetch() : null;
            ?>
            <h2>Your address book</h2>
            <div class="address-grid">
                <?php foreach ($addresses as $a): ?>
                <article class="address-card">
                    <div class="section-heading">
                        <h3><?= e($a['label']) ?></h3>

                        <?php if ($a['is_default']): ?>
                        <span class="badge">Default</span>

                        <?php endif; ?>
                    </div>
                    <p>
                        <?= e($a['first_name'] . ' ' . $a['last_name']) ?>
                        <br />
                        <?= nl2br(e($a['address'])) ?>
                        <br />
                        <?= e($a['city'] . ' ' . $a['postal']) ?>
                        <br />
                        <?= e($a['phone']) ?>
                    </p>
                    <div class="address-actions">
                        <a
                            href="/account/addresses?edit=<?= $a['id'] ?>#address-form"
                            class="text-button"
                        >
                            Edit
                        </a>

                        <?php if (!$a['is_default']): ?>
                        <form method="post">
                            <?= csrf() ?>
                            <input type="hidden" name="action" value="customer_default_address" />
                            <input type="hidden" name="id" value="<?= $a['id'] ?>" />
                            <button class="text-button">Make default</button>
                        </form>

                        <?php endif; ?>
                        <form method="post">
                            <?= csrf() ?>
                            <input type="hidden" name="action" value="customer_delete_address" />
                            <input type="hidden" name="id" value="<?= $a['id'] ?>" />
                            <button class="text-button danger">Remove</button>
                        </form>
                    </div>
                </article>

                <?php endforeach; ?>
            </div>
            <h2 id="address-form"><?= $editingAddress ? 'Edit address' : 'Add a new address' ?></h2>
            <form method="post" class="account-form">
                <?= csrf() ?>
                <input type="hidden" name="action" value="customer_address" />
                <input type="hidden" name="address_id" value="<?= $editingAddress['id'] ?? 0 ?>" />
                <div class="form-grid">
                    <?php
                    foreach ([
                            'label' => 'Address label (e.g. Home)',
                            'first_name' => 'First name',
                            'last_name' => 'Last name',
                            'phone' => 'Phone number',
                            'address' => 'Street address',
                            'city' => 'City / district',
                            'postal' => 'Postal code',
                    ] as $key => $label):
                    ?>
                    <div class="field <?= $key === 'address' || $key === 'label' ? 'span-2' : '' ?>">
                        <label for="<?= $key ?>"><?= $label ?></label>
                        <input
                            id="<?= $key ?>"
                            name="<?= $key ?>"
                            value="<?= e($editingAddress[$key] ?? '') ?>"
                            required
                            maxlength="<?= $key === 'address' ? '500' : '100' ?>"
                        />
                    </div>

                    <?php endforeach; ?>
                </div>
                <label class="checkbox-row">
                    <input type="checkbox" name="is_default" <?= !empty($editingAddress['is_default']) ? 'checked' : '' ?> />
                    Use as my default delivery address
                </label>
                <button class="button dark">Save address <?= icon('check', 17) ?></button>
            </form>

            <?php elseif ($accountPage === 'profile'): ?>
            <h2>Your details</h2>
            <form method="post" class="account-form">
                <?= csrf() ?>
                <input type="hidden" name="action" value="customer_profile" />
                <div class="field">
                    <label for="name">Your name</label>
                    <input
                        id="name"
                        name="name"
                        value="<?= e($customer['name']) ?>"
                        required
                        maxlength="190"
                    />
                </div>
                <div class="field">
                    <label for="account-email">Email address</label>
                    <input id="account-email" value="<?= e($customer['email']) ?>" disabled />
                    <small>Your email is your account identifier.</small>
                </div>
                <div class="field">
                    <label for="phone">Phone number</label>
                    <input
                        id="phone"
                        name="phone"
                        value="<?= e($customer['phone']) ?>"
                        type="tel"
                        maxlength="40"
                    />
                </div>
                <button class="button dark">Save details</button>
            </form>
            <h2>Update your password</h2>
            <form method="post" class="account-form">
                <?= csrf() ?>
                <input type="hidden" name="action" value="customer_password" />
                <div class="field">
                    <label for="current_password">Current password</label>
                    <input
                        id="current_password"
                        name="current_password"
                        type="password"
                        autocomplete="current-password"
                        required
                    />
                </div>
                <div class="field">
                    <label for="new_password">New password</label>
                    <input
                        id="new_password"
                        name="new_password"
                        type="password"
                        autocomplete="new-password"
                        minlength="12"
                        required
                    />
                    <small>At least 12 characters.</small>
                </div>
                <button class="button secondary">Update password</button>
            </form>

            <?php endif; ?>
        </div>
    </div>
</section>
