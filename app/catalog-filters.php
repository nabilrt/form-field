<?php

/** Controller-owned SQL fragments; every visitor-supplied value is bound separately. */
function catalog_filters(array $input): array
{
    $text = static fn (string $key): string => is_string($input[$key] ?? null) ? trim($input[$key]) : '';
    $term = mb_substr($text('q'), 0, 150);
    $category = mb_substr($text('category'), 0, 120);
    $sorts = [
        'featured' => 'p.featured DESC,p.id ASC',
        'newest' => 'p.id DESC',
        'price-asc' => 'price ASC,p.id ASC',
        'price-desc' => 'price DESC,p.id ASC',
    ];
    $sort = array_key_exists($text('sort'), $sorts) ? $text('sort') : 'featured';
    $min = ctype_digit($text('min_price')) ? min(100000000, (int) $text('min_price')) : null;
    $max = ctype_digit($text('max_price')) ? min(100000000, (int) $text('max_price')) : null;
    if ($min !== null && $max !== null && $min > $max) {
        [$min, $max] = [$max, $min];
    }
    $inStock = $text('in_stock') === '1';
    $sale = $text('sale') === '1';
    $where = 'p.active=1';
    $params = [];
    if ($term !== '') {
        $where .= ' AND (p.name LIKE ? OR p.description LIKE ?)';
        array_push($params, '%' . $term . '%', '%' . $term . '%');
    }
    if ($category !== '') {
        $where .= ' AND c.slug=?';
        $params[] = $category;
    }
    // Price, stock and sale conditions must describe the same purchasable variant.
    $variantFilter = catalog_variant_predicate(compact('min', 'max', 'inStock', 'sale'), 'fv');
    if ($min !== null || $max !== null || $inStock || $sale) {
        $where .= ' AND EXISTS(SELECT 1 FROM variants fv WHERE ' . $variantFilter['where'] . ')';
        array_push($params, ...$variantFilter['params']);
    }
    $query = array_filter([
        'q' => $term,
        'category' => $category,
        'sort' => $sort === 'featured' ? '' : $sort,
        'min_price' => $min,
        'max_price' => $max,
        'in_stock' => $inStock ? '1' : '',
        'sale' => $sale ? '1' : '',
    ], static fn ($value) => $value !== '' && $value !== null);

    return compact('term', 'category', 'sort', 'min', 'max', 'inStock', 'sale', 'where', 'params', 'query')
        + ['ordering' => $sorts[$sort]];
}

function order_filters(array $input): array
{
    $status = is_string($input['status'] ?? null) ? $input['status'] : 'all';
    $statuses = ['unfulfilled', 'processing', 'shipped', 'delivered', 'cancelled'];
    if (!in_array($status, $statuses, true)) {
        $status = 'all';
    }
    $term = is_string($input['q'] ?? null) ? mb_substr(trim($input['q']), 0, 150) : '';
    $where = '1=1';
    $params = [];
    if ($status !== 'all') {
        $where .= ' AND status=?';
        $params[] = $status;
    }
    if ($term !== '') {
        $where .= " AND (number LIKE ? OR email LIKE ? OR CONCAT(first_name,' ',last_name) LIKE ?)";
        array_push($params, '%' . $term . '%', '%' . $term . '%', '%' . $term . '%');
    }

    return compact('status', 'term', 'where', 'params');
}

/** Protect spreadsheet imports against formula injection, including leading whitespace. */
function csv_cell(mixed $value): string
{
    $value = (string) ($value ?? '');
    if (preg_match('/^[\s\x00-\x1F]*[=+@-]/u', $value)) {
        return "'" . $value;
    }

    return $value;
}

/** Alias names are fixed by application code, never accepted from a request. */
function catalog_variant_predicate(array $filters, string $alias): array
{
    if (!in_array($alias, ['fv', 'pv'], true)) {
        throw new InvalidArgumentException('Unsupported variant alias.');
    }
    $conditions = [$alias . '.product_id=p.id'];
    $params = [];
    if (isset($filters['min'])) {
        $conditions[] = $alias . '.price>=?';
        $params[] = (int) $filters['min'];
    }
    if (isset($filters['max'])) {
        $conditions[] = $alias . '.price<=?';
        $params[] = (int) $filters['max'];
    }
    if (!empty($filters['inStock'])) {
        $conditions[] = $alias . '.stock>0';
    }
    if (!empty($filters['sale'])) {
        $conditions[] = $alias . '.compare_price>' . $alias . '.price';
    }

    return ['where' => implode(' AND ', $conditions), 'params' => $params];
}
