<?php

$action = (string) ($_POST['action'] ?? '');
if (in_array($action, ['compare_add', 'compare_remove', 'compare_clear', 'recent_clear', 'cart_add_set'], true)) {
    require __DIR__ . '/shopping-actions.php';
}

if (in_array($action, ['customer_review', 'customer_review_delete', 'moderate_review'], true)) {
    require __DIR__ . '/review-actions.php';
}

if ($action === 'login') {
    $email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
    $keys = login_keys('admin', $email);
    if (login_throttled($keys)) {
        flash('Too many attempts. Try again in 15 minutes.', 'error');
        redirect('/admin/login');
    }

    $user = q('SELECT * FROM admins WHERE email=?', [$email])->fetch() ?: null;
    if (password_matches($user, $_POST['password'] ?? '')) {
        login_cleared($keys);
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_name'] = $user['name'];
        $_SESSION['admin_seen'] = time();
        redirect('/admin');
    }

    login_failed($keys);
    flash('Email or password is incorrect.', 'error');
    redirect('/admin/login');
}

if ($action === 'logout') {
    unset($_SESSION['admin_id'],$_SESSION['admin_name']);
    session_regenerate_id(true);
    redirect('/admin/login');
}

if ($action === 'cart_add') {
    $v = q(
        'SELECT v.*, p.slug FROM variants v JOIN products p ON p.id=v.product_id WHERE v.id=? AND p.active=1',
        [(int) ($_POST['variant_id'] ?? 0)]
    )->fetch();
    $qty = max(1, min(99, (int) ($_POST['quantity'] ?? 1)));
    if (!$v || (int) $v['stock'] < $qty + (int) ($_SESSION['cart'][$v['id']] ?? 0)) {
        flash('That quantity is unavailable. Please choose another variant or a smaller quantity.', 'error');
    } else {
        $_SESSION['cart'][$v['id']] = ($_SESSION['cart'][$v['id']] ?? 0) + $qty;
        flash('Added to your bag.', 'success', ['href' => '/cart', 'label' => 'View bag']);
    }
    // Pages with JavaScript stay put: return the new bag count and the rendered notice.
    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        $ok = ($_SESSION['flash']['type'] ?? '') !== 'error';
        ob_start();
        require __DIR__ . '/../views/flash.php';
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: private, no-store');
        http_response_code($ok ? 200 : 409);
        echo json_encode(['ok' => $ok, 'count' => cart_count(), 'flash' => ob_get_clean()], JSON_THROW_ON_ERROR);
        exit;
    }
    // Shoppers keep browsing; the confirmation links to the bag.
    redirect($v ? '/product/' . rawurlencode($v['slug']) : '/cart');
}

if ($action === 'cart_update') {
    foreach ($_POST['quantities'] ?? [] as $id => $qty) {
        $qty = max(0, min(99, (int) $qty));
        if (!$qty) {
            unset($_SESSION['cart'][(int) $id]);
            continue;
        }
        $stock = (int) q('SELECT stock FROM variants WHERE id=?', [(int) $id])->fetchColumn();
        if ($qty > $stock) {
            flash('Some quantities were adjusted to available stock.', 'error');
        }
        $qty = min($qty, $stock);
        if ($qty) {
            $_SESSION['cart'][(int) $id] = $qty;
        } else {
            unset($_SESSION['cart'][(int) $id]);
        }
    }
    if (isset($_POST['remove_variant'])) {
        unset($_SESSION['cart'][(int) $_POST['remove_variant']]);
    }
    redirect('/cart');
}

// Promo codes can be applied from the bag or from checkout; checkout keeps what was typed.
$couponReturn = ($_POST['return_to'] ?? '') === '/checkout' ? '/checkout' : '/cart';
if (in_array($action, ['coupon', 'coupon_remove'], true) && $couponReturn === '/checkout') {
    $_SESSION['checkout_old'] = array_filter(
        array_intersect_key($_POST, array_flip(['email', 'first_name', 'last_name', 'phone', 'address', 'city', 'postal', 'notes', 'payment_method', 'different_billing', 'billing_address'])),
        static fn ($value) => is_string($value) && trim($value) !== ''
    );
}

if ($action === 'coupon') {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $coupon = valid_coupon($code);
    $subtotal = array_sum(array_column(cart_items(), 'total'));
    if (!$coupon || $subtotal < convert($coupon['minimum'])) {
        flash('This code is invalid, used up, or the minimum spend has not been met.', 'error');
    } else {
        $_SESSION['coupon'] = $code;
        flash('Discount applied.');
    }
    redirect($couponReturn);
}

if ($action === 'coupon_remove') {
    unset($_SESSION['coupon']);
    redirect($couponReturn);
}

if ($action === 'set_currency') {
    $code = is_string($_POST['currency'] ?? null) ? $_POST['currency'] : '';
    if (isset(currency_list(true)[$code])) {
        $_SESSION['currency'] = $code;
    }
    $back = is_string($_POST['return_to'] ?? null) ? $_POST['return_to'] : '/';
    $backPath = parse_url($back, PHP_URL_PATH);
    redirect(is_string($backPath) && str_starts_with($back, '/') && !str_starts_with($back, '//') && !str_contains($back, '\\')
        && !preg_match('/[\x00-\x1F]/', $back) && !str_starts_with($backPath, '/admin') ? $back : '/');
}

if ($action === 'subscribe') {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    if (!$email) {
        flash('Enter a valid email address.', 'error');
    } else {
        q('INSERT IGNORE INTO subscribers(email) VALUES(?)', [$email]);
        flash('You’re on the list. Welcome to Form & Field.');
    }
    redirect('/');
}

if ($action === 'place_order') {
    $_SESSION['checkout_old'] = $_POST;
    try {
        $items = cart_items();
        if (!$items) {
            throw new RuntimeException('Your bag is empty.');
        }
        foreach (['email', 'first_name', 'last_name', 'phone', 'address', 'city', 'postal'] as $key) {
            if (!trim($_POST[$key] ?? '') || mb_strlen($_POST[$key]) > 500) {
                throw new RuntimeException('Please complete all required fields.');
            }
        }
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Enter a valid email address.');
        }
        if (!preg_match('/^[+0-9 ()-]{7,25}$/', $_POST['phone'])) {
            throw new RuntimeException('Enter a valid phone number.');
        }
        $method = $_POST['payment_method'] ?? '';
        if (!in_array($method, ['cod', 'bank'], true)) {
            throw new RuntimeException('Choose a payment method.');
        }
        if ($method === 'bank' && !trim(setting('bank_details'))) {
            throw new RuntimeException('Bank transfer is currently unavailable. Please choose cash on delivery.');
        }
        $billing = isset($_POST['different_billing']) ? trim($_POST['billing_address'] ?? '') : trim($_POST['address']) . ', ' . trim($_POST['city']) . ' ' . trim($_POST['postal']);
        if (!$billing) {
            throw new RuntimeException('Enter your billing address.');
        }

        if (($_POST['currency'] ?? current_currency_code()) !== current_currency_code()) {
            throw new RuntimeException('Your shopping currency changed. Please review your order total before placing it.');
        }
        if (!hash_equals($_SESSION['checkout_nonce'] ?? '', $_POST['checkout_nonce'] ?? '') || empty($_SESSION['checkout_nonce'])) {
            throw new RuntimeException('This checkout was already submitted. Please check your order confirmation.');
        }

        db()->beginTransaction();
        $variantIds = array_column($items, 'id');
        $placeholders = implode(',', array_fill(0, count($variantIds), '?'));
        $lockedRows = q(
            "SELECT v.*, p.active FROM variants v JOIN products p ON p.id=v.product_id
            WHERE v.id IN ($placeholders) ORDER BY v.id FOR UPDATE",
            $variantIds
        )->fetchAll();
        // Price every line in the shopper's currency at the moment of purchase.
        $currencyCode = current_currency_code();
        $locked = array_column(localize_variants($lockedRows, $currencyCode), null, 'id');
        foreach ($items as &$item) {
            $fresh = $locked[$item['id']] ?? null;
            if (!$fresh || !$fresh['active'] || (int) $fresh['stock'] < $item['quantity']) {
                throw new RuntimeException($item['name'] . ' has insufficient stock. Update your bag and try again.');
            }

            $item['price'] = $fresh['price'];
            $item['total'] = $fresh['price'] * $item['quantity'];
        }

        unset($item);
        $email = trim($_POST['email']);
        if (!empty($_SESSION['coupon']) && !valid_coupon($_SESSION['coupon'], $email, true)) {
            unset($_SESSION['coupon']);
            throw new RuntimeException('Your promo code has reached its usage limit and was removed. Review your new total.');
        }
        $t = cart_totals($items, null, $email);
        $token = bin2hex(random_bytes(24));
        $number = 'FF-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        q(
            'INSERT INTO orders(customer_id,number,token,email,first_name,last_name,phone,address,city,postal,billing_address,notes,payment_method,subtotal,discount,shipping,tax,total,coupon,currency,rate_to_base) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                $_SESSION['customer_id'] ?? null,
                $number,
                $token,
                trim($_POST['email']),
                trim($_POST['first_name']),
                trim($_POST['last_name']),
                trim($_POST['phone']),
                trim($_POST['address']),
                trim($_POST['city']),
                trim($_POST['postal']),
                $billing,
                mb_substr(trim($_POST['notes'] ?? ''), 0, 2000),
                $method,
                $t['subtotal'],
                $t['discount'],
                $t['shipping'],
                $t['tax'],
                $t['total'],
                $_SESSION['coupon'] ?? null,
                $currencyCode,
                rate_to_base($currencyCode),
            ]
        );
        $oid = (int) db()->lastInsertId();
        foreach ($items as $item) {
            q(
                'INSERT INTO order_items(order_id,variant_id,name,variant,sku,price,quantity,image) VALUES(?,?,?,?,?,?,?,?)',
                [
                    $oid,
                    $item['id'],
                    $item['name'],
                    $item['color'] . ' / ' . $item['size'],
                    $item['sku'],
                    $item['price'],
                    $item['quantity'],
                    $item['image'],
                ]
            );
            q('UPDATE variants SET stock=stock-? WHERE id=?', [$item['quantity'], $item['id']]);
        }
        db()->commit();
        unset($_SESSION['cart'],$_SESSION['coupon'],$_SESSION['checkout_old'],$_SESSION['checkout_nonce']);
        redirect('/order?token=' . $token);
    } catch (Throwable $ex) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        flash(
            ($ex instanceof RuntimeException && !($ex instanceof PDOException)) ? $ex->getMessage() : 'We couldn’t place your order. Please try again.',
            'error'
        );
        redirect('/checkout');
    }
}

if (str_starts_with($action, 'customer_')) {
    require __DIR__ . '/customer-actions.php';
}

require_admin();
try {
    if ($action === 'save_product') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $slug = slugify($_POST['slug'] ?: $name);
        if (!$name || !$slug) {
            throw new RuntimeException('A product name and URL handle are required.');
        }
        $rows = $_POST['variants'] ?? [];
        if (!$rows) {
            throw new RuntimeException('Add at least one variant.');
        }
        $skus = [];
        foreach ($rows as $row) {
            if (!trim($row['sku'] ?? '') || !trim($row['color'] ?? '') || !trim($row['size'] ?? '') || !is_numeric($row['price']) || (float) $row['price'] < 0 || !ctype_digit((string) $row['stock'])) {
                throw new RuntimeException('Each variant needs a unique SKU, color, size, nonnegative price, and whole-number stock.');
            }
            if (in_array($row['sku'], $skus, true)) {
                throw new RuntimeException('Variant SKUs must be unique.');
            }
            $skus[] = $row['sku'];
        }

        $uploads = isset($_FILES['images']) ? upload_images($_FILES['images']) : [];
        db()->beginTransaction();
        $values = [
            (int) $_POST['category_id'] ?: null,
            $name,
            $slug,
            trim($_POST['description'] ?? ''),
            trim($_POST['material'] ?? ''),
            trim($_POST['care'] ?? ''),
            isset($_POST['active']) ? 1 : 0,
            isset($_POST['featured']) ? 1 : 0,
        ];
        if ($id) {
            q(
                'UPDATE products SET category_id=?,name=?,slug=?,description=?,material=?,care=?,active=?,featured=? WHERE id=?',
                [...$values, $id]
            );
        } else {
            q(
                'INSERT INTO products(category_id,name,slug,description,material,care,active,featured) VALUES(?,?,?,?,?,?,?,?)',
                $values
            );
            $id = (int) db()->lastInsertId();
        }

        $keep = [];
        foreach ($rows as $row) {
            $vid = (int) ($row['id'] ?? 0);
            $vals = [
                $row['sku'],
                trim($row['color']),
                trim($row['size']),
                (int) round((float) $row['price']),
                (int) ($row['compare_price'] ?? 0),
                (int) $row['stock'],
            ];
            if ($vid) {
                q(
                    'UPDATE variants SET sku=?,color=?,size=?,price=?,compare_price=?,stock=? WHERE id=? AND product_id=?',
                    [...$vals, $vid, $id]
                );
                $keep[] = $vid;
            } else {
                q(
                    'INSERT INTO variants(sku,color,size,price,compare_price,stock,product_id) VALUES(?,?,?,?,?,?,?)',
                    [...$vals, $id]
                );
                $keep[] = (int) db()->lastInsertId();
            }
        }
        $marks = implode(',', array_fill(0, count($keep), '?'));
        q("DELETE FROM variants WHERE product_id=? AND id NOT IN ($marks)", [$id, ...$keep]);
        // Explicit per-currency prices; a blank price falls back to the exchange rate.
        foreach (is_array($_POST['currency_prices'] ?? null) ? $_POST['currency_prices'] : [] as $variantId => $byCurrency) {
            if (!in_array((int) $variantId, $keep, true) || !is_array($byCurrency)) {
                continue;
            }
            foreach ($byCurrency as $currencyCode => $price) {
                if ($currencyCode === 'BDT' || !isset(currency_list()[$currencyCode]) || !is_array($price)) {
                    continue;
                }
                q('DELETE FROM variant_prices WHERE variant_id=? AND currency=?', [(int) $variantId, $currencyCode]);
                $amount = to_minor($price['amount'] ?? '', $currencyCode);
                if ($amount !== null) {
                    q('INSERT INTO variant_prices(variant_id,currency,amount,compare_amount) VALUES(?,?,?,?)', [
                        (int) $variantId, $currencyCode, $amount, to_minor($price['compare'] ?? '', $currencyCode),
                    ]);
                }
            }
        }
        foreach ($_POST['remove_images'] ?? [] as $imageId) {
            q('DELETE FROM product_images WHERE id=? AND product_id=?', [(int) $imageId, $id]);
        }
        $pos = (int) q('SELECT COALESCE(MAX(position),0)+1 FROM product_images WHERE product_id=?', [$id])->fetchColumn();
        foreach ($uploads as $image) {
            q('INSERT INTO product_images(product_id,image,position) VALUES(?,?,?)', [$id, $image, $pos++]);
        }
        db()->commit();
        flash('Product saved.');
        redirect('/admin/products/edit?id=' . $id);
    }

    if ($action === 'archive_product') {
        q('UPDATE products SET active=0 WHERE id=?', [(int) $_POST['id']]);
        flash('Product archived.');
        redirect('/admin/products');
    }

    if ($action === 'save_category') {
        $name = trim($_POST['name'] ?? '');
        if (!$name) {
            throw new RuntimeException('Enter a collection name.');
        }
        q(
            'INSERT INTO categories(name,slug,description) VALUES(?,?,?)',
            [$name, slugify($name), trim($_POST['description'] ?? '')]
        );
        flash('Collection created.');
        redirect('/admin/collections');
    }

    if ($action === 'save_order') {
        $status = $_POST['status'] ?? '';
        $payment = $_POST['payment_status'] ?? '';
        if (!in_array($status, ['unfulfilled', 'processing', 'shipped', 'delivered', 'cancelled'], true) || !in_array($payment, ['unpaid', 'paid', 'refunded'], true)) {
            throw new RuntimeException('Select a valid order status.');
        }
        db()->beginTransaction();
        $order = q('SELECT * FROM orders WHERE id=? FOR UPDATE', [(int) $_POST['id']])->fetch();
        if (!$order) {
            throw new RuntimeException('Order not found.');
        }
        if ($order['status'] === 'cancelled' && $status !== 'cancelled') {
            throw new RuntimeException('Cancelled orders cannot be reopened. Create a new order instead.');
        }
        if ($status === 'cancelled' && $order['status'] !== 'cancelled') {
            foreach (q('SELECT * FROM order_items WHERE order_id=?', [$order['id']])->fetchAll() as $item) {
                if ($item['variant_id']) {
                    q('UPDATE variants SET stock=stock+? WHERE id=?', [$item['quantity'], $item['variant_id']]);
                }
            }
        }
        q('UPDATE orders SET status=?,payment_status=? WHERE id=?', [$status, $payment, $order['id']]);
        db()->commit();
        flash('Order updated.');
        redirect('/admin/orders/view?id=' . $order['id']);
    }

    if ($action === 'save_settings') {
        $groups = settings_groups();
        $group = (string) ($_POST['settings_group'] ?? 'all');
        if ($group !== 'all' && !isset($groups[$group])) {
            throw new RuntimeException('Choose a valid settings tab.');
        }
        $keys = $group === 'all' ? array_merge(...array_column($groups, 'keys')) : $groups[$group]['keys'];
        $values = [];
        foreach ($keys as $key) {
            if (!isset($_POST[$key]) || !is_string($_POST[$key])) {
                throw new RuntimeException('Please complete the fields in this settings tab.');
            }
            $value = trim($_POST[$key]);
            if (in_array($key, ['shipping', 'free_shipping', 'tax_rate'], true)
                && (!is_numeric($value) || (float) $value < 0
                    || ($key === 'tax_rate' && (float) $value > 100))) {
                throw new RuntimeException('Shipping and tax must be valid nonnegative numbers.');
            }
            if ($key === 'store_email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Enter a valid store email.');
            }
            if (in_array($key, ['store_name', 'store_address'], true) && $value === '') {
                throw new RuntimeException('Enter your store name and business address.');
            }
            if ($key === 'announcement' && mb_strlen($value) > 500) {
                throw new RuntimeException('Keep the announcement to 500 characters or fewer.');
            }
            $values[$key] = $value;
        }
        db()->beginTransaction();
        foreach ($values as $key => $value) {
            q('INSERT INTO settings(`key`,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)', [$key, $value]);
        }
        db()->commit();
        flash('Settings saved.');
        redirect('/admin/settings#' . ($group === 'all' ? 'general' : $group));
    }

    if ($action === 'save_slider') {
        $id = (int) ($_POST['id'] ?? 0);
        $image = trim($_POST['existing_image'] ?? '');
        if ($image !== '' && !preg_match('~^/(uploads|assets)/[A-Za-z0-9._-]+$~D', $image)) {
            $image = '';
        }
        if (isset($_FILES['images'])) {
            $uploads = upload_images($_FILES['images']);
            if ($uploads) {
                $image = $uploads[0];
            }
        }
        if (!$image || !trim($_POST['title'] ?? '')) {
            throw new RuntimeException('Add a title and an image.');
        }
        $link = trim($_POST['link'] ?? '/shop');
        if (!str_starts_with($link, '/') || str_starts_with($link, '//')) {
            throw new RuntimeException('Use a store URL starting with a single /.');
        }
        $values = [
            trim($_POST['title']),
            trim($_POST['description']),
            $image,
            $link,
            trim($_POST['button']),
            isset($_POST['active']) ? 1 : 0,
            (int) $_POST['position'],
        ];
        if ($id) {
            q(
                'UPDATE sliders SET title=?,description=?,image=?,link=?,button=?,active=?,position=? WHERE id=?',
                [...$values, $id]
            );
        } else {
            q('INSERT INTO sliders(title,description,image,link,button,active,position) VALUES(?,?,?,?,?,?,?)', $values);
        }
        flash('Homepage slide saved.');
        redirect('/admin/sliders');
    }

    if ($action === 'delete_slider') {
        q('DELETE FROM sliders WHERE id=?', [(int) $_POST['id']]);
        flash('Slide deleted.');
        redirect('/admin/sliders');
    }

    if ($action === 'save_coupon') {
        $amount = (int) ($_POST['amount'] ?? 0);
        $type = $_POST['type'] ?? '';
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            // Orders reference codes by text, so an edited coupon keeps its code and its usage history.
            $code = (string) q('SELECT code FROM coupons WHERE id=?', [$id])->fetchColumn();
            if ($code === '') {
                throw new RuntimeException('This promo code no longer exists.');
            }
        }
        if (!preg_match('/^[A-Z0-9_-]{2,30}$/', $code) || !in_array($type, ['percent', 'fixed'], true) || $amount <= 0 || ($type === 'percent' && $amount > 100)) {
            throw new RuntimeException('Use a valid code and discount amount.');
        }
        $limit = static fn (string $key): ?int => ($_POST[$key] ?? '') === '' ? null : max(1, (int) $_POST[$key]);
        $values = [$type, $amount, max(0, (int) ($_POST['minimum'] ?? 0)), ($_POST['expires_at'] ?? '') ?: null, $limit('max_uses'), $limit('per_customer')];
        if ($id) {
            q('UPDATE coupons SET type=?,amount=?,minimum=?,expires_at=?,max_uses=?,per_customer=? WHERE id=?', [...$values, $id]);
        } else {
            q('INSERT INTO coupons(type,amount,minimum,expires_at,max_uses,per_customer,code,active) VALUES(?,?,?,?,?,?,?,1)', [...$values, $code]);
        }
        flash($id ? 'Promo code updated.' : 'Discount created.');
        redirect('/admin/discounts');
    }

    if ($action === 'save_currency') {
        $code = strtoupper(trim(is_string($_POST['code'] ?? null) ? $_POST['code'] : ''));
        $symbol = trim(is_string($_POST['symbol'] ?? null) ? $_POST['symbol'] : '');
        $decimals = filter_var($_POST['decimals'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 3]]);
        $rate = $_POST['rate'] ?? '';
        if (!preg_match('/^[A-Z]{3}$/', $code) || $code === 'BDT') {
            throw new RuntimeException('Use a three-letter ISO currency code other than BDT, e.g. USD.');
        }
        if ($symbol === '' || mb_strlen($symbol) > 8 || $decimals === false || !is_numeric($rate) || (float) $rate <= 0) {
            throw new RuntimeException('Enter a symbol, 0–3 decimal places, and a positive exchange rate.');
        }
        $position = (int) ($_POST['position'] ?? 0);
        if (q('SELECT 1 FROM currencies WHERE code=?', [$code])->fetchColumn()) {
            // Decimals are fixed at creation: stored prices and orders are minor units of that precision.
            q('UPDATE currencies SET symbol=?,rate=?,position=? WHERE code=?', [$symbol, (string) $rate, $position, $code]);
        } else {
            q('INSERT INTO currencies(code,symbol,decimals,rate,position,active) VALUES(?,?,?,?,?,1)', [$code, $symbol, $decimals, (string) $rate, $position]);
        }
        flash('Currency saved.');
        redirect('/admin/currencies');
    }

    if ($action === 'toggle_currency') {
        q('UPDATE currencies SET active=1-active WHERE code=?', [(string) ($_POST['code'] ?? '')]);
        flash('Currency updated.');
        redirect('/admin/currencies');
    }

    if ($action === 'toggle_coupon') {
        q('UPDATE coupons SET active=1-active WHERE id=?', [(int) $_POST['id']]);
        redirect('/admin/discounts');
    }

    if ($action === 'change_password') {
        if (strlen($_POST['new_password'] ?? '') < 12) {
            throw new RuntimeException('Use at least 12 characters for the new password.');
        }
        $hash = q('SELECT password FROM admins WHERE id=?', [$_SESSION['admin_id']])->fetchColumn();
        if (!password_verify($_POST['current_password'] ?? '', $hash)) {
            throw new RuntimeException('Your current password is incorrect.');
        }
        q(
            'UPDATE admins SET password=? WHERE id=?',
            [password_hash($_POST['new_password'], PASSWORD_DEFAULT), $_SESSION['admin_id']]
        );
        session_regenerate_id(true);
        flash('Password updated.');
        redirect('/admin/settings#security');
    }

    throw new RuntimeException('Unknown action.');
} catch (Throwable $ex) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }
    error_log((string) $ex);
    flash(
        ($ex instanceof RuntimeException && !($ex instanceof PDOException)) ? $ex->getMessage() : 'Could not save. Check for a duplicate URL handle or SKU and try again.',
        'error'
    );
    if (in_array($action, ['save_settings', 'change_password'], true)) {
        $group = $action === 'change_password' ? 'security' : (string) ($_POST['settings_group'] ?? 'general');
        $tab = isset(settings_groups()[$group]) || $group === 'security' ? $group : 'general';
        redirect('/admin/settings#' . $tab);
    }
    if ($action === 'save_coupon' && (int) ($_POST['id'] ?? 0)) {
        redirect('/admin/discounts?edit=' . (int) $_POST['id'] . '#coupon-form');
    }
    $back = parse_url($_SERVER['HTTP_REFERER'] ?? '/admin', PHP_URL_PATH);
    redirect(
        str_starts_with($back ?? '', '/admin') ? $back . (isset($_POST['id']) ? '?id=' . (int) $_POST['id'] : '') : '/admin'
    );
}
