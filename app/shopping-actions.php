<?php

if (in_array($action, ['compare_add', 'compare_remove', 'compare_clear'], true)) {
    $message = '';
    $status = 200;
    try {
        $ids = shopping_ids($_SESSION['compare_products'] ?? [], 3);
        if ($action === 'compare_clear') {
            $ids = [];
            $message = 'Comparison cleared.';
        } else {
            if ($ids) {
                $marks = implode(',', array_fill(0, count($ids), '?'));
                $active = array_map('intval', q("SELECT id FROM products WHERE active=1 AND id IN ($marks)", $ids)->fetchAll(PDO::FETCH_COLUMN));
                $ids = array_values(array_intersect($ids, $active));
            }
            $id = filter_var($_POST['product_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
            if ($id === false) {
                throw new RuntimeException('Choose a valid product.');
            }
            if ($action === 'compare_remove') {
                $ids = array_values(array_diff($ids, [$id]));
                $message = 'Removed from comparison.';
            } else {
                if (!q('SELECT id FROM products WHERE id=? AND active=1', [$id])->fetchColumn()) {
                    throw new RuntimeException('This product is no longer available.');
                }
                if (!in_array($id, $ids, true)) {
                    if (count($ids) >= 3) {
                        throw new RuntimeException('Compare up to three products. Remove one before adding another.');
                    }
                    $ids[] = $id;
                }
                $message = 'Added to comparison.';
            }
        }
        $_SESSION['compare_products'] = array_values($ids);
    } catch (Throwable $error) {
        error_log($error->getMessage());
        $status = 409;
        $message = $error instanceof RuntimeException && !($error instanceof PDOException)
            ? $error->getMessage() : 'Comparison could not be updated. Please try again.';
    }
    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: private, no-store');
        http_response_code($status);
        echo json_encode(['ok' => $status === 200, 'message' => $message, 'items' => comparison_payload()], JSON_THROW_ON_ERROR);
        exit;
    }
    flash($message, $status === 200 ? 'success' : 'error');
    redirect(safe_shopping_return($_POST['return_to'] ?? '/shop'));
}

if ($action === 'recent_clear') {
    unset($_SESSION['recent_products']);
    flash('Recently viewed products cleared.');
    redirect('/recent');
}

try {
    $selected = $_POST['selected'] ?? [];
    if (!is_array($selected) || count($selected) > 6 || count($selected) === 0) {
        throw new RuntimeException('Choose between one and six products for your set.');
    }
    $productIds = shopping_ids($selected, 6);
    if (count($productIds) !== count($selected)) {
        throw new RuntimeException('Choose up to six different products.');
    }
    if (!is_array($_POST['set_variants'] ?? null) || !is_array($_POST['set_quantities'] ?? null)) {
        throw new RuntimeException('Choose a finish and quantity for your selected products.');
    }
    $variants = [];
    $draft = [];
    foreach ($productIds as $productId) {
        $variant = filter_var($_POST['set_variants'][$productId] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $quantity = filter_var($_POST['set_quantities'][$productId] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 10]]);
        if ($variant === false || $quantity === false) {
            throw new RuntimeException('Choose a finish and a quantity from 1 to 10 for every selected product.');
        }
        $variants[] = $variant;
        $draft[$productId] = ['variant_id' => $variant, 'quantity' => $quantity];
    }
    $_SESSION['set_builder'] = $draft;
    $placeholders = implode(',', array_fill(0, count($variants), '?'));
    $rows = q("SELECT v.id,v.product_id,v.stock,p.active FROM variants v JOIN products p ON p.id=v.product_id WHERE v.id IN ($placeholders)", $variants)->fetchAll();
    $byId = array_column($rows, null, 'id');
    $nextCart = $_SESSION['cart'] ?? [];
    foreach ($draft as $productId => $choice) {
        $variant = $byId[$choice['variant_id']] ?? null;
        if (!$variant || !$variant['active'] || (int) $variant['product_id'] !== $productId) {
            throw new RuntimeException('One of these products is no longer available. Please review your set.');
        }
        $quantity = (int) ($nextCart[$variant['id']] ?? 0) + $choice['quantity'];
        if ($quantity > min(99, (int) $variant['stock'])) {
            throw new RuntimeException('One of these quantities exceeds available stock, including items already in your bag. Please review your set.');
        }
        $nextCart[$variant['id']] = $quantity;
    }
    // Apply the entire set only after every choice passes validation. Stock is reserved at checkout.
    $_SESSION['cart'] = $nextCart;
    unset($_SESSION['set_builder']);
    flash('Your set is in the bag. Current prices and any eligible promo code apply at checkout.');
    redirect('/cart');
} catch (Throwable $error) {
    error_log($error->getMessage());
    flash($error instanceof RuntimeException && !($error instanceof PDOException)
        ? $error->getMessage() : 'Your set could not be added. Please try again.', 'error');
    redirect('/build-a-set');
}
