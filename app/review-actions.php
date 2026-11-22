<?php

if ($action === 'moderate_review') {
    require_admin();
    $returnStatus = is_string($_POST['return_status'] ?? null) ? $_POST['return_status'] : 'pending';
    if (!in_array($returnStatus, ['all', 'pending', 'approved', 'rejected'], true)) {
        $returnStatus = 'pending';
    }
    try {
        if (!reviews_available()) {
            throw new RuntimeException('Review storage needs to be installed before reviews can be moderated.');
        }
        $status = $_POST['status'] ?? '';
        if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
            throw new RuntimeException('Choose a valid moderation status.');
        }
        $id = (int) ($_POST['review_id'] ?? 0);
        $review = q('SELECT id,customer_id,product_id,revision,status,verified_purchase FROM product_reviews WHERE id=?', [$id])->fetch();
        if (!$review) {
            throw new RuntimeException('Review not found.');
        }
        $revision = (int) ($_POST['revision'] ?? 0);
        if ($revision !== (int) $review['revision']) {
            throw new RuntimeException('This review has changed. Reload the page and read the latest version before moderating it.');
        }
        $verified = (bool) $review['verified_purchase']
            || has_verified_purchase((int) $review['customer_id'], (int) $review['product_id']);
        if ($review['status'] !== $status || (bool) $review['verified_purchase'] !== $verified) {
            $updated = q('UPDATE product_reviews SET status=?,verified_purchase=? WHERE id=? AND revision=?', [
                $status, $verified ? 1 : 0, $id, $revision,
            ]);
            if (!$updated->rowCount()) {
                throw new RuntimeException('This review has changed. Reload the page and read the latest version before moderating it.');
            }
        }
        flash(match ($status) {
            'approved' => 'Review published.',
            'rejected' => 'Review rejected and hidden from the storefront.',
            default => 'Review returned to moderation and hidden from the storefront.',
        });
    } catch (Throwable $error) {
        error_log($error->getMessage());
        flash($error instanceof RuntimeException && !($error instanceof PDOException)
            ? $error->getMessage() : 'The review could not be updated. Please try again.', 'error');
    }
    redirect('/admin/reviews?status=' . $returnStatus);
}

if (empty($_SESSION['customer_id'])) {
    redirect('/account/login');
}
$product = null;
try {
    if (!reviews_available()) {
        throw new RuntimeException('Reviews are temporarily unavailable. Please try again later.');
    }
    $productId = (int) ($_POST['product_id'] ?? 0);
    $product = q('SELECT id,slug,active FROM products WHERE id=?', [$productId])->fetch();
    if (!$product) {
        throw new RuntimeException('This product is no longer available.');
    }
    $customerId = (int) $_SESSION['customer_id'];
    if ($action === 'customer_review_delete') {
        $statement = q('DELETE FROM product_reviews WHERE id=? AND customer_id=? AND product_id=?', [
            (int) ($_POST['review_id'] ?? 0), $customerId, $productId,
        ]);
        if (!$statement->rowCount()) {
            throw new RuntimeException('This review is not available in your account.');
        }
        unset($_SESSION['review_form'][$customerId][$productId]);
        flash('Your review has been withdrawn.');
    } else {
        if (!$product['active']) {
            throw new RuntimeException('Reviews cannot be added to an archived product.');
        }
        $title = is_string($_POST['title'] ?? null) ? trim($_POST['title']) : '';
        $body = is_string($_POST['body'] ?? null) ? trim($_POST['body']) : '';
        $rating = filter_var($_POST['rating'] ?? '', FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 5],
        ]);
        $_SESSION['review_form'][$customerId][$productId] = [
            'title' => mb_substr($title, 0, 140),
            'body' => mb_substr($body, 0, 4000),
            'rating' => $rating ?: '',
        ];
        if ($rating === false || mb_strlen($title) < 3 || mb_strlen($title) > 140
            || mb_strlen($body) < 10 || mb_strlen($body) > 4000) {
            throw new RuntimeException('Choose 1–5 stars, a title of 3–140 characters, and a review of 10–4,000 characters.');
        }
        db()->beginTransaction();
        // Serialize submissions by this customer; the database also enforces one review per product.
        if (!q('SELECT id FROM customers WHERE id=? FOR UPDATE', [$customerId])->fetchColumn()) {
            throw new RuntimeException('Sign in to your account before submitting a review.');
        }
        $existing = q('SELECT id,verified_purchase FROM product_reviews WHERE customer_id=? AND product_id=?', [
            $customerId, $productId,
        ])->fetch();
        $verified = !empty($existing['verified_purchase']) || has_verified_purchase($customerId, $productId);
        if ($existing) {
            q("UPDATE product_reviews SET rating=?,title=?,body=?,status='pending',verified_purchase=?,revision=revision+1,updated_at=NOW()
                WHERE id=? AND customer_id=? AND product_id=?", [
                $rating, $title, $body, $verified ? 1 : 0, $existing['id'], $customerId, $productId,
            ]);
        } else {
            q('INSERT INTO product_reviews(product_id,customer_id,rating,title,body,verified_purchase) VALUES(?,?,?,?,?,?)', [
                $productId, $customerId, $rating, $title, $body, $verified ? 1 : 0,
            ]);
        }
        db()->commit();
        unset($_SESSION['review_form'][$customerId][$productId]);
        flash('Thank you. Your review will appear once it has been approved.');
    }
    redirect($product['active']
        ? '/product/' . rawurlencode($product['slug']) . ($action === 'customer_review' ? '#review-form' : '#reviews')
        : '/shop');
} catch (Throwable $error) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }
    error_log($error->getMessage());
    flash($error instanceof RuntimeException && !($error instanceof PDOException)
        ? $error->getMessage() : 'Your review could not be saved. Please try again.', 'error');
    redirect($product && $product['active'] ? '/product/' . rawurlencode($product['slug']) . '#review-form' : '/shop');
}
