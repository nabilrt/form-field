<?php

require_admin();
session_write_close();
$filter = order_filters($_GET);
// Fetch at most 500 rows at a time instead of buffering the complete export in PHP memory.
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="form-field-orders-' . date('Y-m-d') . '.csv"');
header('Cache-Control: private, no-store');
$stream = fopen('php://output', 'wb');
fwrite($stream, "\xEF\xBB\xBF");
fputcsv($stream, [
    'Order', 'Date (Bangladesh)', 'Customer', 'Email', 'Phone', 'City',
    'Payment method', 'Payment status', 'Fulfillment', 'Currency', 'Subtotal',
    'Discount', 'Delivery', 'Tax', 'Total', 'Total (BDT)',
], ',', '"', '');
$cursor = PHP_INT_MAX;
$ceiling = (int) q('SELECT COALESCE(MAX(id),0) FROM orders')->fetchColumn();
do {
    $rows = q(
        'SELECT id,number,created_at,first_name,last_name,email,phone,city,payment_method,payment_status,
        status,subtotal,discount,shipping,tax,total,currency,rate_to_base FROM orders WHERE ' . $filter['where'] . '
        AND id<=? AND id<? ORDER BY id DESC LIMIT 500',
        [...$filter['params'], $ceiling, $cursor]
    )->fetchAll();
    foreach ($rows as $row) {
        // Amounts are stored in minor units of the order's currency.
        $decimals = (int) currency($row['currency'])['decimals'];
        $major = static fn ($amount) => number_format($amount / 10 ** $decimals, $decimals, '.', '');
        fputcsv($stream, array_map('csv_cell', [
            $row['number'], $row['created_at'], $row['first_name'] . ' ' . $row['last_name'],
            $row['email'], $row['phone'], $row['city'], $row['payment_method'],
            $row['payment_status'], $row['status'], $row['currency'], $major($row['subtotal']), $major($row['discount']),
            $major($row['shipping']), $major($row['tax']), $major($row['total']), (int) round($row['total'] * $row['rate_to_base']),
        ]), ',', '"', '');
        $cursor = (int) $row['id'];
    }
} while (count($rows) === 500);
fclose($stream);
