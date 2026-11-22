<div class="table-scroll">
    <table>
        <thead>
            <tr>
                <th>Order</th>
                <th>Date</th>
                <th>Customer</th>
                <th>Payment</th>
                <th>Fulfillment</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recent as $o): ?>
            <tr>
                <td>
                    <a href="/admin/orders/view?id=<?= $o['id'] ?>">
                        <strong><?= e($o['number']) ?></strong>
                    </a>
                </td>
                <td><?= date('d M, h:i a', strtotime($o['created_at'])) ?></td>
                <td><?= e($o['first_name'] . ' ' . $o['last_name']) ?></td>
                <td><span class="badge <?= e($o['payment_status']) ?>"><?= e(ucfirst($o['payment_status'])) ?></span></td>
                <td><span class="badge <?= e($o['status']) ?>"><?= e(ucfirst($o['status'])) ?></span></td>
                <td class="text-right"><?= money($o['total'], $o['currency']) ?></td>
            </tr>

            <?php endforeach; ?>
        </tbody>
    </table>
</div>
