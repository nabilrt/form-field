<?php

/*
 * Returns, Medusa-style: a shopper requests a return for delivered items, the merchant
 * marks it received (items go back into stock) and then records the refund, or rejects it.
 * Amounts are minor units of the order's currency. Refunds are recorded, not paid out:
 * cash-on-delivery and bank-transfer refunds are sent by hand.
 */

const RETURN_REASONS = [
    'damaged' => 'Arrived damaged',
    'wrong_item' => 'Wrong item sent',
    'not_as_described' => 'Not as described',
    'changed_mind' => 'Changed my mind',
    'other' => 'Something else',
];

function returns_available(): bool
{
    static $available;
    if ($available === null) {
        try {
            q('SELECT 1 FROM returns LIMIT 1');
            $available = true;
        } catch (PDOException) {
            $available = false;
        }
    }

    return $available;
}

/** Order items with how many units can still be returned (rejected returns free their units). */
function returnable_items(int $orderId): array
{
    return q(
        "SELECT i.*, i.quantity - COALESCE((
            SELECT SUM(ri.quantity) FROM return_items ri JOIN returns r ON r.id=ri.return_id
            WHERE ri.order_item_id=i.id AND r.status<>'rejected'
        ),0) AS returnable
        FROM order_items i WHERE i.order_id=? ORDER BY i.id",
        [$orderId]
    )->fetchAll();
}

/**
 * Why an order can't be returned right now, or null when it can.
 * ponytail: the window counts from the order date; store a delivered_at if couriers take long.
 */
function return_block_reason(array $order): ?string
{
    $window = (int) setting('return_window_days', '30');
    if ($window <= 0) {
        return 'Returns aren’t offered online. Please contact us.';
    }
    if ($order['status'] !== 'delivered') {
        return 'You can request a return once your order has been delivered.';
    }
    if (strtotime($order['created_at']) < strtotime("-$window days")) {
        return "The $window-day return window for this order has closed.";
    }

    return null;
}

/** Refund already recorded for an order, in its currency's minor units. */
function order_refunded(int $orderId): int
{
    return (int) q("SELECT COALESCE(SUM(refund_amount),0) FROM returns WHERE order_id=? AND status='refunded'", [$orderId])->fetchColumn();
}

/** Item value minus its share of the order discount, capped at what is left to refund. */
function suggested_refund(array $order, array $returnItems): int
{
    $value = 0;
    foreach ($returnItems as $item) {
        $value += (int) $item['price'] * (int) $item['quantity'];
    }
    $subtotal = max(1, (int) $order['subtotal']);
    $afterDiscount = (int) round($value * max(0, $subtotal - (int) $order['discount']) / $subtotal);

    return max(0, min($afterDiscount, (int) $order['total'] - order_refunded((int) $order['id'])));
}

/** Returns for an order, each with its items. */
function order_returns(int $orderId): array
{
    $returns = q('SELECT * FROM returns WHERE order_id=? ORDER BY id DESC', [$orderId])->fetchAll();
    foreach ($returns as &$return) {
        $return['items'] = q(
            'SELECT ri.quantity, i.name, i.variant, i.price, i.variant_id FROM return_items ri
            JOIN order_items i ON i.id=ri.order_item_id WHERE ri.return_id=? ORDER BY ri.id',
            [$return['id']]
        )->fetchAll();
    }
    unset($return);

    return $returns;
}
