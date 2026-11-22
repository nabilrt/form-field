<?php

declare(strict_types=1);
ini_set('session.use_strict_mode', '1');
session_set_cookie_params(
    ['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']
);
session_start();
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=31536000');
}
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header(
    "Content-Security-Policy: default-src 'self'; img-src 'self' https://images.unsplash.com data: blob:; style-src 'self' 'unsafe-inline'; script-src 'self'; font-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'"
);
date_default_timezone_set('Asia/Dhaka');
$config = [];
if (is_file(__DIR__ . '/../.env')) {
    foreach (file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (!str_starts_with(trim($line), '#') && str_contains($line, '=')) {
            [$k,$v] = explode('=', $line, 2);
            $config[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
        }
    }
}

function envv(string $key, string $default = ''): string
{
    global $config;
    return (string) (getenv($key) ?: ($config[$key] ?? $default));
}

ini_set('display_errors', envv('APP_ENV', 'production') === 'development' ? '1' : '0');
function db(): PDO
{
    static $pdo;
    if (!$pdo) {
        $socket = envv('DB_SOCKET');
        $dsn = $socket ? 'mysql:unix_socket=' . $socket . ';dbname=' . envv('DB_DATABASE', 'form_field') . ';charset=utf8mb4' : 'mysql:host=' . envv('DB_HOST', '127.0.0.1') . ';port=' . envv('DB_PORT', '3306') . ';dbname=' . envv('DB_DATABASE', 'form_field') . ';charset=utf8mb4';
        $pdo = new PDO(
            $dsn,
            envv('DB_USERNAME', 'root'),
            envv('DB_PASSWORD'),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        $pdo->exec("SET time_zone = '+06:00'");
    }
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $s = db()->prepare($sql);
    foreach (array_values($params) as $index => $value) {
        $type = match (true) {
            is_int($value) => PDO::PARAM_INT,
            is_bool($value) => PDO::PARAM_BOOL,
            $value === null => PDO::PARAM_NULL,
            default => PDO::PARAM_STR,
        };
        $s->bindValue($index + 1, $value, $type);
    }

    $s->execute();
    return $s;
}

function e(mixed $s): string
{
    return htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
}

function setting(string $key, string $fallback = ''): string
{
    static $settings;
    if ($settings === null) {
        $settings = q('SELECT `key`,value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    return (string) ($settings[$key] ?? $fallback);
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function csrf(): string
{
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">';
}

function check_csrf(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['csrf'] ?? '')) || empty($_SESSION['csrf'])) {
        http_response_code(419);
        exit('Your session expired. Reload the page and try again.');
    }
}

/** $link is an optional same-site follow-up, e.g. ['href' => '/cart', 'label' => 'View bag']. */
function flash(string $message, string $type = 'success', ?array $link = null): void
{
    $_SESSION['flash'] = ['text' => $message, 'type' => $type] + ($link ? ['link' => $link] : []);
}

/** Throttle by IP and by account, so one attacker can't spray an account from many IPs. */
function login_keys(string $scope, string $email): array
{
    return [
        hash('sha256', $scope . '-' . ($_SERVER['REMOTE_ADDR'] ?? 'local')),
        hash('sha256', $scope . '-account-' . strtolower(trim($email))),
    ];
}

function login_throttled(array $keys): bool
{
    $marks = implode(',', array_fill(0, count($keys), '?'));
    return (bool) q(
        "SELECT 1 FROM login_attempts WHERE ip_hash IN ($marks) AND attempts>=8 AND last_attempt>DATE_SUB(NOW(),INTERVAL 15 MINUTE)",
        $keys
    )->fetchColumn();
}

function login_failed(array $keys): void
{
    foreach ($keys as $key) {
        q(
            'INSERT INTO login_attempts(ip_hash,attempts,last_attempt) VALUES(?,1,NOW()) ON DUPLICATE KEY UPDATE attempts=IF(last_attempt < DATE_SUB(NOW(),INTERVAL 15 MINUTE),1,attempts+1),last_attempt=NOW()',
            [$key]
        );
    }
}

function login_cleared(array $keys): void
{
    foreach ($keys as $key) {
        q('DELETE FROM login_attempts WHERE ip_hash=?', [$key]);
    }
}

/** Always run password_verify so response time doesn't reveal which emails have accounts. */
function password_matches(?array $user, mixed $password): bool
{
    static $dummy;
    $dummy ??= password_hash('timing-equalizer', PASSWORD_DEFAULT);
    $ok = password_verify(is_string($password) ? $password : '', $user['password'] ?? $dummy);
    return $user && $ok;
}

function admin(): bool
{
    if (empty($_SESSION['admin_id'])) {
        return false;
    }
    // Idle merchant sessions expire after two hours.
    if (isset($_SESSION['admin_seen']) && $_SESSION['admin_seen'] < time() - 7200) {
        unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_seen']);
        return false;
    }
    $_SESSION['admin_seen'] = time();
    return true;
}

function require_admin(): void
{
    if (!admin()) {
        redirect('/admin/login');
    }
}

function slugify(string $value): string
{
    return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($value)) ?? '', '-');
}

function icon(string $name, int $size = 20): string
{
    $paths = [
        'bag' => '<path d="M6 7h12l2 14H4L6 7Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/>',
        'search' => '<circle cx="10.8" cy="10.8" r="7.3"/><path d="m16 16 5 5"/>',
        'arrow' => '<path d="M4 12h16m-6-6 6 6-6 6"/>',
        'chevron' => '<path d="m9 5 7 7-7 7"/>',
        'home' => '<path d="m3 10 9-7 9 7v11h-7v-7h-4v7H3Z"/>',
        'box' => '<path d="m12 3 9 5-9 5-9-5 9-5Zm-9 5v11l9 4 9-4V8M12 13v10M7 5l10 6"/>',
        'orders' => '<rect x="5" y="4" width="14" height="18" rx="2"/><path d="M9 2h6v5H9zM9 12h6m-6 4h6"/>',
        'users' => '<circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3m1-17a3 3 0 0 1 0 6m2 4a5 5 0 0 1 3 4v3"/>',
        'chart' => '<path d="M3 3v18h18M7 16v-5m5 5V6m5 10V9"/>',
        'settings' => '<path d="m9 3-1 3-3 1 1 4-2 2 2 4 4-1 2 3 4-1 1-4 3-1-1-4-3-1-1-3Z"/><circle cx="12" cy="11" r="3"/>',
        'tag' => '<path d="M3 3h9l10 10-9 9L3 12Z"/><circle cx="8" cy="8" r="1"/>',
        'image' => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8" cy="8" r="2"/><path d="m3 17 6-6 4 4 3-3 5 5"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'close' => '<path d="m6 6 12 12M6 18 18 6"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
        'truck' => '<path d="M1 4h14v13H1zM15 9h5l3 4v4h-8"/><circle cx="5" cy="19" r="2"/><circle cx="19" cy="19" r="2"/>',
        'return' => '<path d="M4 10a8 8 0 1 1 1 8M4 4v6h6"/>',
        'leaf' => '<path d="M20 3C8 2 1 10 6 17s17-1 14-14ZM5 21 16 9"/>',
        'logout' => '<path d="M9 4H3v16h6m6-13 5 5-5 5M8 12h12"/>',
        'menu' => '<path d="M3 6h18M3 12h18M3 18h18"/>',
        'download' => '<path d="M12 3v12m-5-5 5 5 5-5M4 16v5h16v-5"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 5 9 8 9-8"/>',
        'external' => '<path d="M14 3h7v7m0-7L10 14M11 3H3v18h18v-8"/>',
        'star' => '<path d="m12 3 2.8 5.7 6.3.9-4.6 4.4 1.1 6.3-5.6-3-5.6 3 1.1-6.3L3 9.6l6.2-.9Z"/>',
        'compare' => '<rect x="3" y="4" width="7" height="16" rx="1"/><rect x="14" y="4" width="7" height="16" rx="1"/><path d="M6 9h1m-1 4h1m10-4h1m-1 4h1"/>',
        'currency' => '<circle cx="12" cy="12" r="9"/><path d="M15 9.5c-.6-1-1.7-1.5-3-1.5-1.7 0-3 .9-3 2s1.3 1.6 3 2 3 .9 3 2-1.3 2-3 2c-1.3 0-2.4-.5-3-1.5M12 6v12"/>',
        'heart' => '<path d="M20 5c-3-3-6-1-8 1-2-2-5-4-8-1s-1 7 8 14c9-7 11-11 8-14Z"/>',
    ];
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? $paths['box']) . '</svg>';
}

function image_url(?string $path): string
{
    return $path ?: '/assets/placeholder.svg';
}

/** Catalog pages use one page query and one batch variant query, regardless of card count. */
/** Catalog pages use one page query and one batch variant query, regardless of card count. */
function products(
    string $where = 'p.active=1',
    array $params = [],
    string $order = 'p.id DESC',
    ?int $limit = null,
    int $offset = 0,
    array $priceFilters = []
): array {
    $allowedOrders = ['p.id DESC', 'p.id ASC', 'p.featured DESC,p.id ASC', 'price ASC,p.id ASC', 'price DESC,p.id ASC'];
    if (!in_array($order, $allowedOrders, true)) {
        throw new InvalidArgumentException('Unsupported catalog sort order.');
    }

    // The displayed and sorted starting price follows the same variant filters as the listing.
    $pricePredicate = catalog_variant_predicate($priceFilters, 'pv');
    $priceWhere = $pricePredicate['where'];
    $params = [...$pricePredicate['params'], ...$params];
    // WHERE fragments are authored by controllers; request values always go in $params.
    $sql = "SELECT p.*, c.name AS category,
        (SELECT image FROM product_images WHERE product_id=p.id ORDER BY position,id LIMIT 1) AS image,
        (SELECT MIN(pv.price) FROM variants pv WHERE $priceWhere) AS price
        FROM products p LEFT JOIN categories c ON c.id=p.category_id
        WHERE $where ORDER BY $order";
    if ($limit !== null) {
        $sql .= ' LIMIT ? OFFSET ?';
        $params[] = max(1, min(100, $limit));
        $params[] = max(0, $offset);
    }

    $rows = q($sql, $params)->fetchAll();
    if (!$rows) {
        return [];
    }

    $ids = array_column($rows, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $variantRows = q(
        "SELECT id, product_id, color, stock, price, compare_price FROM variants
        WHERE product_id IN ($placeholders) ORDER BY id",
        $ids
    )->fetchAll();
    // ponytail: in other currencies the card shows the lowest price of all variants, ignoring active price filters (which stay in BDT).
    $localPrices = [];
    if (current_currency_code() !== 'BDT') {
        foreach (localize_variants($variantRows) as $variant) {
            $pid = $variant['product_id'];
            $localPrices[$pid] = min($localPrices[$pid] ?? PHP_INT_MAX, (int) $variant['price']);
        }
    }
    $summaries = [];
    foreach ($variantRows as $variant) {
        $id = $variant['product_id'];
        $summaries[$id] ??= ['stock' => 0, 'variant_count' => 0, 'sale' => false, 'colors' => []];
        $summaries[$id]['stock'] += (int) $variant['stock'];
        $summaries[$id]['variant_count']++;
        $summaries[$id]['sale'] = $summaries[$id]['sale'] || $variant['compare_price'] > $variant['price'];
        $summaries[$id]['colors'][$variant['color']] = $variant['color'];
    }

    $reviewRatings = review_summaries($ids);
    foreach ($rows as &$row) {
        $row['review_count'] = (int) ($reviewRatings[$row['id']]['review_count'] ?? 0);
        $row['average_rating'] = (float) ($reviewRatings[$row['id']]['average_rating'] ?? 0);
        $row += $summaries[$row['id']] ?? ['stock' => 0, 'variant_count' => 0, 'sale' => false, 'colors' => []];
        $row['colors'] = array_values($row['colors']);
        if (isset($localPrices[$row['id']])) {
            $row['price'] = $localPrices[$row['id']];
        }
    }

    unset($row);
    return $rows;
}

function product_count(string $where = 'p.active=1', array $params = []): int
{
    return (int) q(
        "SELECT COUNT(*) FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE $where",
        $params
    )->fetchColumn();
}

function cart_items(): array
{
    $cart = $_SESSION['cart'] ?? [];
    if (!$cart) {
        return [];
    }

    $ids = array_map('intval', array_keys($cart));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $rows = q(
        "SELECT v.*, p.name, p.slug, p.active,
        (SELECT image FROM product_images WHERE product_id=p.id ORDER BY position,id LIMIT 1) AS image
        FROM variants v JOIN products p ON p.id=v.product_id
        WHERE p.active=1 AND v.id IN ($placeholders) ORDER BY v.id",
        $ids
    )->fetchAll();
    $rows = localize_variants($rows);
    foreach ($rows as &$row) {
        $row['quantity'] = (int) $cart[$row['id']];
        $row['total'] = (int) $row['price'] * $row['quantity'];
    }

    unset($row);
    return $rows;
}

function cart_count(): int
{
    return array_sum($_SESSION['cart'] ?? []);
}

/**
 * Active, unexpired coupon with uses left, or null. Uses are counted from non-cancelled orders,
 * so cancelling an order frees its use. Pass $lock inside the checkout transaction.
 * ponytail: per-customer limit matches account or email; a guest using a new email gets a fresh allowance.
 */
function valid_coupon(string $code, ?string $email = null, bool $lock = false): ?array
{
    $coupon = q(
        'SELECT * FROM coupons WHERE code=? AND active=1 AND (expires_at IS NULL OR expires_at>=CURDATE())' . ($lock ? ' FOR UPDATE' : ''),
        [$code]
    )->fetch();
    if (!$coupon || ($coupon['max_uses'] === null && $coupon['per_customer'] === null)) {
        return $coupon ?: null;
    }
    $customerId = (int) ($_SESSION['customer_id'] ?? 0);
    $usage = q(
        "SELECT COUNT(*) AS total, COALESCE(SUM(customer_id=? OR LOWER(email)=?),0) AS mine
        FROM orders WHERE coupon=? AND status<>'cancelled'",
        [$customerId, strtolower(trim((string) $email)), $code]
    )->fetch();
    if ($coupon['max_uses'] !== null && (int) $usage['total'] >= (int) $coupon['max_uses']) {
        return null;
    }
    if ($coupon['per_customer'] !== null && (int) $usage['mine'] >= (int) $coupon['per_customer']) {
        return null;
    }
    return $coupon;
}

function cart_totals(array $items, ?string $code = null, ?string $email = null): array
{
    $subtotal = array_sum(array_column($items, 'total'));
    $discount = 0;
    $code = $code ?? ($_SESSION['coupon'] ?? '');
    if ($code) {
        $coupon = valid_coupon($code, $email);
        // Coupon, delivery and threshold amounts are set in BDT and convert to the cart's currency.
        if ($coupon && $subtotal >= convert($coupon['minimum'])) {
            $discount = min(
                $subtotal,
                $coupon['type'] === 'percent' ? (int) round($subtotal * (int) $coupon['amount'] / 100) : convert($coupon['amount'])
            );
        }
    }
    $shipping = $subtotal === 0 || $subtotal >= convert(setting('free_shipping', '5000')) ? 0 : convert(setting('shipping', '120'));
    $tax = (int) round(($subtotal - $discount) * (float) setting('tax_rate', '0') / 100);
    return compact('subtotal', 'discount', 'shipping', 'tax') + ['total' => $subtotal - $discount + $shipping + $tax];
}

function upload_images(array $files): array
{
    $saved = [];
    $total = count($files['name'] ?? []);
    if ($total > 12) {
        throw new RuntimeException('Upload at most 12 images at a time.');
    }
    for ($i = 0;$i < $total;$i++) {
        if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Image upload failed. Try a smaller image.');
        }
        if ($files['size'][$i] > 5 * 1024 * 1024) {
            throw new RuntimeException('Each image must be smaller than 5 MB.');
        }
        $tmp = $files['tmp_name'][$i];
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new RuntimeException('Use JPG, PNG, or WebP images.');
        }
        $info = getimagesize($tmp);
        if (!$info || $info[0] * $info[1] > 24000000) {
            throw new RuntimeException('Image resolution is too large.');
        }
        $img = imagecreatefromstring(file_get_contents($tmp));
        if (!$img) {
            throw new RuntimeException('This image could not be read.');
        }
        $name = bin2hex(random_bytes(16)) . '.webp';
        imagewebp($img, __DIR__ . '/../public/uploads/' . $name, 85);
        $saved[] = '/uploads/' . $name;
    }
    return $saved;
}

function color_hex(string $color): string
{
    return [
        'natural' => '#e9e1d2',
        'walnut' => '#6b4b35',
        'chalk' => '#eeeae3',
        'clay' => '#a67e64',
        'oat' => '#c8bea9',
        'rust' => '#a65c44',
        'sand' => '#d8c9ad',
        'charcoal' => '#393939',
        'terracotta' => '#b76d4f',
        'natural oak' => '#c4a775',
        'smoked oak' => '#695341',
        'olive' => '#666b46',
        'cream' => '#ede7db',
        'slate' => '#667078',
        'black' => '#242424',
        'original' => '#a66b2b',
        'unscented' => '#e3dfd4',
    ][strtolower($color)] ?? '#aaa';
}

/** Editable settings are scoped so each tab submits and updates only its own fields. */
function settings_groups(): array
{
    return [
        'general' => [
            'label' => 'General',
            'keys' => ['store_name', 'store_email', 'store_phone', 'store_address'],
        ],
        'delivery' => [
            'label' => 'Delivery & tax',
            'keys' => ['shipping', 'free_shipping', 'tax_rate', 'return_window_days'],
        ],
        'payments' => [
            'label' => 'Payments',
            'keys' => ['bank_details'],
        ],
        'storefront' => [
            'label' => 'Storefront',
            'keys' => ['announcement'],
        ],
    ];
}

require_once __DIR__ . '/currency.php';

require_once __DIR__ . '/returns.php';

require_once __DIR__ . '/catalog-filters.php';

require_once __DIR__ . '/reviews.php';

require_once __DIR__ . '/shopping-tools.php';
