<?php

if ($action === 'customer_logout') {
    unset($_SESSION['customer_id'], $_SESSION['review_form']);
    session_regenerate_id(true);
    redirect('/');
}

if (in_array($action, ['customer_register', 'customer_login'], true)) {
    $email = is_string($_POST['email'] ?? null) ? strtolower(trim($_POST['email'])) : '';
    $keys = login_keys('customer', $email);
    if (login_throttled($keys)) {
        flash('Too many attempts. Try again in 15 minutes.', 'error');
        redirect('/account/login');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('Enter a valid email address.', 'error');
        redirect('/account/login');
    }

    if ($action === 'customer_register') {
        $name = trim($_POST['name'] ?? '');
        if (!$name || mb_strlen($name) > 190 || strlen($_POST['password'] ?? '') < 12) {
            flash('Enter your name and a password of at least 12 characters.', 'error');
            redirect('/account/register');
        }
        try {
            q(
                'INSERT INTO customers(name,email,password) VALUES(?,?,?)',
                [$name, $email, password_hash($_POST['password'], PASSWORD_DEFAULT)]
            );
            $_SESSION['customer_id'] = (int) db()->lastInsertId();
            session_regenerate_id(true);
            flash('Welcome to Form & Field. Your account is ready.');
            redirect('/account');
        } catch (PDOException $ex) {
            flash('An account with this email may already exist. Try signing in.', 'error');
            redirect('/account/login');
        }
    }

    $user = q('SELECT * FROM customers WHERE email=?', [$email])->fetch() ?: null;
    if (password_matches($user, $_POST['password'] ?? '')) {
        login_cleared($keys);
        session_regenerate_id(true);
        $_SESSION['customer_id'] = $user['id'];
        redirect('/account');
    }
    login_failed($keys);
    flash('Email or password is incorrect.', 'error');
    redirect('/account/login');
}

if (empty($_SESSION['customer_id'])) {
    redirect('/account/login');
}

try {
    if ($action === 'customer_reorder') {
        $orderId = (int) ($_POST['order_id'] ?? 0);
        $order = q('SELECT id FROM orders WHERE id=? AND customer_id=?', [$orderId, $_SESSION['customer_id']])->fetch();
        if (!$order) {
            throw new RuntimeException('This order is not available in your account.');
        }
        $items = q(
            'SELECT i.quantity,i.variant_id,v.stock,p.active FROM order_items i
            LEFT JOIN variants v ON v.id=i.variant_id LEFT JOIN products p ON p.id=v.product_id
            WHERE i.order_id=? ORDER BY i.id',
            [$orderId]
        )->fetchAll();
        $added = 0;
        $unavailable = false;
        foreach ($items as $item) {
            $id = (int) $item['variant_id'];
            $existing = (int) ($_SESSION['cart'][$id] ?? 0);
            $available = min(99, (int) $item['stock']) - $existing;
            $quantity = $item['active'] ? max(0, min((int) $item['quantity'], $available)) : 0;
            if ($quantity > 0) {
                $_SESSION['cart'][$id] = $existing + $quantity;
                $added += $quantity;
            }
            $unavailable = $unavailable || $quantity < (int) $item['quantity'];
        }
        // Checkout will validate prices and inventory again; no historical discount is copied.
        flash(
            $added > 0
            ? 'Items added at current prices.' . ($unavailable ? ' Some items were unavailable or limited by stock.' : '')
            : 'These items are currently unavailable. Your existing bag is unchanged.',
            $added > 0 ? 'success' : 'error'
        );
        redirect('/cart');
    }

    if ($action === 'customer_profile') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        if (!$name || mb_strlen($name) > 190 || mb_strlen($phone) > 40) {
            throw new RuntimeException('Enter a valid name and phone number.');
        }
        q('UPDATE customers SET name=?,phone=? WHERE id=?', [$name, $phone, $_SESSION['customer_id']]);
        flash('Your details are updated.');
        redirect('/account/profile');
    }

    if ($action === 'customer_password') {
        $customer = q('SELECT * FROM customers WHERE id=?', [$_SESSION['customer_id']])->fetch();
        if (!password_verify($_POST['current_password'] ?? '', $customer['password'])) {
            throw new RuntimeException('Your current password is incorrect.');
        }
        if (strlen($_POST['new_password'] ?? '') < 12) {
            throw new RuntimeException('Your new password needs at least 12 characters.');
        }
        q(
            'UPDATE customers SET password=? WHERE id=?',
            [password_hash($_POST['new_password'], PASSWORD_DEFAULT), $customer['id']]
        );
        session_regenerate_id(true);
        flash('Your password is updated.');
        redirect('/account/profile');
    }

    if ($action === 'customer_address') {
        foreach (['first_name', 'last_name', 'phone', 'address', 'city', 'postal'] as $key) {
            if (!trim($_POST[$key] ?? '') || mb_strlen($_POST[$key]) > 500) {
                throw new RuntimeException('Complete all address fields.');
            }
        }
        db()->beginTransaction();
        if (isset($_POST['is_default'])) {
            q('UPDATE addresses SET is_default=0 WHERE customer_id=?', [$_SESSION['customer_id']]);
        }
        $addressId = (int) ($_POST['address_id'] ?? 0);
        $values = [
            mb_substr(trim($_POST['label'] ?? 'Home'), 0, 60),
            trim($_POST['first_name']),
            trim($_POST['last_name']),
            trim($_POST['phone']),
            trim($_POST['address']),
            trim($_POST['city']),
            trim($_POST['postal']),
            isset($_POST['is_default']) ? 1 : 0,
        ];
        if ($addressId) {
            if (!q('SELECT id FROM addresses WHERE id=? AND customer_id=?', [$addressId, $_SESSION['customer_id']])->fetch()) {
                throw new RuntimeException('Address not found.');
            }
            q(
                'UPDATE addresses SET label=?,first_name=?,last_name=?,phone=?,address=?,city=?,postal=?,is_default=? WHERE id=? AND customer_id=?',
                [...$values, $addressId, $_SESSION['customer_id']]
            );
        } else {
            q(
                'INSERT INTO addresses(label,first_name,last_name,phone,address,city,postal,is_default,customer_id) VALUES(?,?,?,?,?,?,?,?,?)',
                [...$values, $_SESSION['customer_id']]
            );
        }
        db()->commit();
        flash('Address saved.');
        redirect('/account/addresses');
    }

    if ($action === 'customer_delete_address') {
        q('DELETE FROM addresses WHERE id=? AND customer_id=?', [(int) $_POST['id'], $_SESSION['customer_id']]);
        flash('Address removed.');
        redirect('/account/addresses');
    }

    if ($action === 'customer_default_address') {
        db()->beginTransaction();
        $address = q('SELECT id FROM addresses WHERE id=? AND customer_id=?', [(int) $_POST['id'], $_SESSION['customer_id']])->fetch();
        if (!$address) {
            throw new RuntimeException('Address not found.');
        }
        q('UPDATE addresses SET is_default=0 WHERE customer_id=?', [$_SESSION['customer_id']]);
        q('UPDATE addresses SET is_default=1 WHERE id=?', [$address['id']]);
        db()->commit();
        flash('Default address updated.');
        redirect('/account/addresses');
    }
} catch (Throwable $ex) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }
    flash(
        ($ex instanceof RuntimeException && !($ex instanceof PDOException)) ? $ex->getMessage() : 'Could not save your changes. Please try again.',
        'error'
    );
    redirect(match ($action) {
        'customer_address' => '/account/addresses',
        'customer_reorder' => '/account/orders',
        default => '/account/profile',
    });
}

redirect('/account');
