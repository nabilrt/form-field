<?php

function shopping_ids(mixed $values, int $limit): array
{
    if (!is_array($values)) {
        return [];
    }
    $ids = [];
    foreach ($values as $value) {
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
        if ($id !== false && !in_array($id, $ids, true)) {
            $ids[] = $id;
        }
        if (count($ids) === $limit) {
            break;
        }
    }

    return $ids;
}

function shopping_products(array $ids, int $limit = 8): array
{
    $ids = shopping_ids($ids, $limit);
    if (!$ids) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $rows = products('p.active=1 AND p.id IN (' . $placeholders . ')', $ids, 'p.id DESC', $limit);
    $byId = array_column($rows, null, 'id');
    $ordered = [];
    foreach ($ids as $id) {
        if (isset($byId[$id])) {
            $ordered[] = $byId[$id];
        }
    }

    return $ordered;
}

function comparison_products(): array
{
    static $rows;
    if ($rows === null) {
        $rows = shopping_products($_SESSION['compare_products'] ?? [], 3);
        $_SESSION['compare_products'] = array_map('intval', array_column($rows, 'id'));
    }

    return $rows;
}

function comparison_payload(): array
{
    return array_map(static fn ($product) => [
        'id' => (int) $product['id'],
        'name' => $product['name'],
        'image' => image_url($product['image']),
        'url' => '/product/' . rawurlencode($product['slug']),
        'price' => (int) $product['price'],
    ], comparison_products());
}

function safe_shopping_return(mixed $url): string
{
    if (!is_string($url) || strlen($url) > 1000 || !str_starts_with($url, '/')
        || str_starts_with($url, '//') || str_contains($url, '\\') || preg_match('/[\x00-\x1F\x7F]/', $url)) {
        return '/shop';
    }
    $parts = parse_url($url);
    if (!$parts || isset($parts['scheme']) || isset($parts['host'])) {
        return '/shop';
    }
    $path = $parts['path'] ?? '';
    $allowed = in_array($path, ['/', '/shop', '/saved', '/compare', '/build-a-set', '/recent', '/cart'], true)
        || preg_match('~^/product/[a-z0-9-]+$~D', $path);

    return $allowed ? $url : '/shop';
}

function remember_product(int $productId): void
{
    $history = shopping_ids($_SESSION['recent_products'] ?? [], 8);
    $_SESSION['recent_products'] = array_slice(array_values(array_unique([$productId, ...$history])), 0, 8);
}

function recent_products(int $exclude = 0, int $limit = 4): array
{
    $ids = array_values(array_filter(
        shopping_ids($_SESSION['recent_products'] ?? [], 8),
        static fn ($id) => $id !== $exclude
    ));

    return shopping_products(array_slice($ids, 0, $limit), $limit);
}

/** Active catalog and variant choices load in two bounded queries plus review aggregates. */
function set_builder_products(): array
{
    $rows = products('p.active=1 AND EXISTS(SELECT 1 FROM variants sv WHERE sv.product_id=p.id AND sv.stock>0)', [], 'p.featured DESC,p.id ASC', 30);
    if (!$rows) {
        return [];
    }
    $ids = array_column($rows, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $options = localize_variants(q("SELECT id,product_id,color,size,price,compare_price,stock FROM variants WHERE product_id IN ($placeholders) AND stock>0 ORDER BY id", $ids)->fetchAll());
    $grouped = [];
    foreach ($options as $option) {
        $option['stock'] = max(0, (int) $option['stock'] - (int) ($_SESSION['cart'][$option['id']] ?? 0));
        if ($option['stock'] > 0) {
            $grouped[$option['product_id']][] = $option;
        }
    }
    foreach ($rows as &$product) {
        $product['options'] = $grouped[$product['id']] ?? [];
    }
    unset($product);

    return array_values(array_filter($rows, static fn ($product) => count($product['options']) > 0));
}

function comparison_rows(array $products): array
{
    $definitions = [
        'Starting price' => static fn ($p) => money($p['price'] ?? 0),
        'Collection' => static fn ($p) => $p['category'] ?: 'Uncategorized',
        'Materials / features' => static fn ($p) => $p['material'] ?: 'See product details',
        'Available options' => static fn ($p) => implode(', ', $p['colors']) ?: 'Standard',
        'Availability' => static fn ($p) => $p['stock'] > 0 ? 'In stock' : 'Sold out',
        'Customer rating' => static fn ($p) => $p['review_count']
            ? number_format($p['average_rating'], 1) . '/5 · ' . $p['review_count'] . ' reviews' : 'No reviews yet',
        'Care / technical details' => static fn ($p) => $p['care'] ?: 'See product details',
    ];
    $rows = [];
    foreach ($definitions as $label => $format) {
        $values = array_map($format, $products);
        $rows[] = ['label' => $label, 'values' => $values, 'equal' => count(array_unique($values)) <= 1];
    }

    return $rows;
}

function comparison_tray_allowed(string $page): bool
{
    return !in_array($page, ['compare', 'cart', 'checkout', 'auth', 'set-builder'], true);
}
