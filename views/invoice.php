<?php
$token = (string) ($_GET['token'] ?? '');
$order = $token ? q('SELECT * FROM orders WHERE token=?', [$token])->fetch() : null;
if (!$order) {
    http_response_code(404);
    exit('Invoice not found.');
}
$orderItems = q('SELECT * FROM order_items WHERE order_id=?', [$order['id']])->fetchAll();
header('Cache-Control: private, no-store');
?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width,initial-scale=1" />
        <title>Invoice <?= e($order['number']) ?></title>
        <link rel="stylesheet" href="/assets/style.css" />
        <script src="/assets/app.js" defer></script>
    </head>
    <body class="invoice-body">
        <div class="invoice-actions">
            <a href="/order?token=<?= e($token) ?>" class="button secondary">Back to order</a>
            <button class="button dark" data-print><?= icon('download', 18) ?> Print / save PDF</button>
        </div>
        <main class="invoice">
            <header>
                <div>
                    <a class="brand" href="/">
                        form
                        <span>&</span>
                        field.
                    </a>
                    <p>
                        <?= e(setting('store_name')) ?>
                        <br />
                        <?= nl2br(e(setting('store_address'))) ?>
                        <br />
                        <?= e(setting('store_email')) ?>
                        <br />
                        <?= e(setting('store_phone')) ?>
                    </p>
                </div>
                <div>
                    <h1>Invoice</h1>
                    <strong><?= e($order['number']) ?></strong>
                    <p>
                        Issued <?= date('d M Y', strtotime($order['created_at'])) ?>
                        <br />
                        Currency: <?= e($order['currency']) ?> (<?= e(currency($order['currency'])['symbol']) ?>)
                    </p>
                    <span class="badge <?= e($order['payment_status']) ?>"><?= e(ucfirst($order['payment_status'])) ?></span>
                </div>
            </header>
            <div class="invoice-addresses">
                <div>
                    <h3>Bill to</h3>
                    <strong><?= e($order['first_name'] . ' ' . $order['last_name']) ?></strong>
                    <p>
                        <?= nl2br(e($order['billing_address'])) ?>
                        <br />
                        <?= e($order['email']) ?>
                    </p>
                </div>
                <div>
                    <h3>Ship to</h3>
                    <p>
                        <?= nl2br(e($order['address'])) ?>
                        <br />
                        <?= e($order['city'] . ' ' . $order['postal']) ?>
                        <br />
                        <?= e($order['phone']) ?>
                    </p>
                </div>
                <div>
                    <h3>Payment method</h3>
                    <p><?= $order['payment_method'] === 'cod' ? 'Cash on delivery' : 'Bank transfer' ?></p>
                </div>
            </div>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Qty</th>
                            <th>Unit price</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orderItems as $item): ?>
                        <tr>
                            <td>
                                <strong><?= e($item['name']) ?></strong>
                                <small><?= e($item['variant']) ?> · <?= e($item['sku']) ?></small>
                            </td>
                            <td><?= $item['quantity'] ?></td>
                            <td><?= money($item['price'], $order['currency']) ?></td>
                            <td class="text-right"><?= money($item['price'] * $item['quantity'], $order['currency']) ?></td>
                        </tr>

                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="invoice-totals">
                <?php
                $totals = $order;
                require __DIR__ . '/totals.php';
                ?>
            </div>
            <footer>
                <h3>Thank you for making room for us.</h3>
                <p>
                    Questions about your invoice? Contact <?= e(setting('store_email')) ?> with your order number.
                </p>

                <?php if ($order['payment_method'] === 'bank'): ?>
                <p>
                    <?= nl2br(e(setting('bank_details'))) ?>
                    <br />
                    Transfer reference: <?= e($order['number']) ?>
                </p>

                <?php endif; ?>
            </footer>
        </main>
    </body>
</html>
