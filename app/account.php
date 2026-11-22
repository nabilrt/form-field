<?php

header('Cache-Control: private, no-store');
$accountPage = explode('/', $path)[2] ?? 'overview';
if (in_array($accountPage, ['login', 'register'], true)) {
    if (!empty($_SESSION['customer_id'])) {
        redirect('/account');
    }
    $page = 'auth';
} else {
    if (empty($_SESSION['customer_id'])) {
        redirect('/account/login');
    }
    $customer = q('SELECT * FROM customers WHERE id=?', [$_SESSION['customer_id']])->fetch();
    if (!$customer) {
        unset($_SESSION['customer_id']);
        redirect('/account/login');
    }
    $page = 'account';
    if (!in_array($accountPage, ['overview', 'orders', 'addresses', 'profile'], true)) {
        $accountPage = 'overview';
    }
}

require __DIR__ . '/../views/store-layout.php';
