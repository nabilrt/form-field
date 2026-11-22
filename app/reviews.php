<?php

/** An older installation keeps working until the review migration is applied. */
function reviews_available(): bool
{
    static $available;
    if ($available === null) {
        $available = (bool) q("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'product_reviews'")->fetchColumn();
    }

    return $available;
}

/** One aggregate lookup for the whole catalog page, never a query for each card. */
function review_summaries(array $productIds): array
{
    if (!$productIds || !reviews_available()) {
        return [];
    }
    $ids = array_values(array_unique(array_map('intval', $productIds)));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $rows = q(
        "SELECT product_id, COUNT(*) AS review_count, AVG(rating) AS average_rating
        FROM product_reviews WHERE status='approved' AND product_id IN ($placeholders) GROUP BY product_id",
        $ids
    )->fetchAll();

    return array_column($rows, null, 'product_id');
}

function review_stars(int $rating): string
{
    $rating = max(0, min(5, $rating));
    $html = '<span class="review-stars" role="img" aria-label="' . $rating . ' out of 5 stars">';
    for ($i = 1; $i <= 5; $i++) {
        $html .= '<span class="' . ($i <= $rating ? 'filled' : '') . '">' . icon('star', 15) . '</span>';
    }

    return $html . '</span>';
}

function reviewer_name(string $name): string
{
    $parts = preg_split('/\s+/u', trim($name)) ?: [];
    $first = mb_substr($parts[0] ?? 'Customer', 0, 70);

    return count($parts) > 1 ? $first . ' ' . mb_substr(end($parts), 0, 1) . '.' : $first;
}

function has_verified_purchase(int $customerId, int $productId): bool
{
    return (bool) q(
        "SELECT 1 FROM orders o JOIN order_items i ON i.order_id=o.id
        JOIN variants v ON v.id=i.variant_id
        WHERE o.customer_id=? AND v.product_id=? AND o.status='delivered' AND o.payment_status='paid' LIMIT 1",
        [$customerId, $productId]
    )->fetchColumn();
}

function product_review_data(int $productId, array $summary): array
{
    $available = reviews_available();
    $data = [
        'available' => $available,
        'count' => (int) ($summary['review_count'] ?? 0),
        'average' => (float) ($summary['average_rating'] ?? 0),
        'histogram' => [],
        'items' => [],
        'own' => null,
        'page' => 1,
        'pages' => 1,
    ];
    if (!$available) {
        return $data;
    }
    $data['histogram'] = q(
        "SELECT rating,COUNT(*) FROM product_reviews WHERE product_id=? AND status='approved' GROUP BY rating",
        [$productId]
    )->fetchAll(PDO::FETCH_KEY_PAIR);
    $data['pages'] = max(1, (int) ceil($data['count'] / 5));
    $data['page'] = max(1, min($data['pages'], (int) ($_GET['reviews_page'] ?? 1)));
    $data['items'] = q(
        "SELECT r.id,r.rating,r.title,r.body,r.verified_purchase,r.created_at,c.name AS customer_name
        FROM product_reviews r JOIN customers c ON c.id=r.customer_id
        WHERE r.product_id=? AND r.status='approved' ORDER BY r.created_at DESC,r.id DESC LIMIT ? OFFSET ?",
        [$productId, 5, ($data['page'] - 1) * 5]
    )->fetchAll();
    if (!empty($_SESSION['customer_id'])) {
        $data['own'] = q(
            'SELECT id,rating,title,body,status FROM product_reviews WHERE product_id=? AND customer_id=?',
            [$productId, (int) $_SESSION['customer_id']]
        )->fetch() ?: null;
    }

    return $data;
}
