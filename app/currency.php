<?php

/*
 * Multi-currency, Medusa-style: a variant may carry an explicit price per currency
 * (variant_prices); otherwise its BDT price converts at the currency's rate.
 * Every amount is an integer in the currency's minor unit (BDT: taka, USD: cents),
 * so cart, coupon and order arithmetic stays integer-only.
 */

const BASE_CURRENCY = ['code' => 'BDT', 'symbol' => '৳', 'decimals' => 0, 'rate' => '1', 'active' => 1];

/** All currencies keyed by code, base first. Older installs without the table get BDT only. */
function currency_list(bool $activeOnly = false): array
{
    static $all;
    if ($all === null) {
        $all = ['BDT' => BASE_CURRENCY];
        try {
            foreach (q('SELECT * FROM currencies ORDER BY position,code')->fetchAll() as $row) {
                $all[$row['code']] = $row;
            }
        } catch (PDOException) {
            // Table not migrated yet: the store keeps working in BDT only.
        }
    }

    return $activeOnly ? array_filter($all, static fn ($c) => (int) $c['active'] === 1) : $all;
}

function currency(?string $code = null): array
{
    return currency_list()[$code ?? current_currency_code()] ?? BASE_CURRENCY;
}

/** The shopper's chosen currency; admin pages pin the base currency with $pin. */
function current_currency_code(?string $pin = null): string
{
    static $pinned;
    if ($pin !== null) {
        $pinned = $pin;
    }
    if ($pinned) {
        return $pinned;
    }
    $code = $_SESSION['currency'] ?? 'BDT';

    return is_string($code) && isset(currency_list(true)[$code]) ? $code : 'BDT';
}

/** BDT (whole taka) → minor units of $code at its rate. */
function convert(int|float|string $bdt, ?string $code = null): int
{
    $c = currency($code);
    if ($c['code'] === 'BDT') {
        return (int) round((float) $bdt);
    }

    return (int) round((float) $bdt / (float) $c['rate'] * 10 ** (int) $c['decimals']);
}

/** BDT per one minor unit; stored on each order so reports can total every currency in BDT. */
function rate_to_base(?string $code = null): float
{
    $c = currency($code);

    return (float) $c['rate'] / 10 ** (int) $c['decimals'];
}

/**
 * Re-price variant rows (needing id, price, compare_price) in the current currency.
 * An explicit price wins; its compare price is shown only if one was set for that currency too.
 */
function localize_variants(array $rows, ?string $code = null): array
{
    $code ??= current_currency_code();
    if ($code === 'BDT' || !$rows) {
        return $rows;
    }
    $ids = array_values(array_unique(array_map('intval', array_column($rows, 'id'))));
    $marks = implode(',', array_fill(0, count($ids), '?'));
    $explicit = q(
        "SELECT variant_id,amount,compare_amount FROM variant_prices WHERE currency=? AND variant_id IN ($marks)",
        [$code, ...$ids]
    )->fetchAll(PDO::FETCH_UNIQUE);
    foreach ($rows as &$row) {
        $own = $explicit[$row['id']] ?? null;
        $row['compare_price'] = $own ? (int) ($own['compare_amount'] ?? 0) : convert((int) ($row['compare_price'] ?? 0), $code);
        $row['price'] = $own ? (int) $own['amount'] : convert((int) $row['price'], $code);
    }
    unset($row);

    return $rows;
}

/** Format minor units, e.g. 2999 USD → "$29.99", 3450 BDT → "৳3,450". */
function money(int|float|string $amount, ?string $code = null): string
{
    $c = currency($code);
    $decimals = (int) $c['decimals'];

    return $c['symbol'] . number_format((float) $amount / 10 ** $decimals, $decimals);
}

/** Merchant input "29.99" → 2999 minor units; null for blank. */
function to_minor(mixed $value, string $code): ?int
{
    if (!is_string($value) || trim($value) === '') {
        return null;
    }
    if (!is_numeric($value) || (float) $value < 0) {
        throw new RuntimeException('Prices must be nonnegative numbers.');
    }

    return (int) round((float) $value * 10 ** (int) currency($code)['decimals']);
}
