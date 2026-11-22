<?php if ($reviewData['available']): ?>
<section class="section product-reviews" id="reviews" aria-labelledby="reviews-heading">
    <div class="section-heading">
        <div>
            <h2 id="reviews-heading">Customer reviews</h2>
            <p><?= $reviewData['count'] ?> published <?= $reviewData['count'] === 1 ? 'review' : 'reviews' ?></p>
        </div>
        <a class="text-link" href="#review-form">Write a review <?= icon('arrow', 17) ?></a>
    </div>
    <div class="reviews-layout">
        <aside class="review-overview" aria-label="Rating summary">
            <?php if ($reviewData['count']): ?>
            <div class="review-average">
                <strong><?= number_format($reviewData['average'], 1) ?></strong>
                <span>out of 5</span>
            </div>
            <p>Based on <?= $reviewData['count'] ?> published <?= $reviewData['count'] === 1 ? 'review' : 'reviews' ?>.</p>
            <dl class="rating-distribution">
                <?php for ($star = 5; $star >= 1; $star--): ?>

                <?php $count = (int) ($reviewData['histogram'][$star] ?? 0); ?>
                <div>
                    <dt><?= $star ?> <?= $star === 1 ? 'star' : 'stars' ?></dt>
                    <dd>
                        <span class="rating-track" aria-hidden="true">
                            <span style="width: <?= (int) round($count / $reviewData['count'] * 100) ?>%"></span>
                        </span>
                        <span><?= $count ?></span>
                    </dd>
                </div>

                <?php endfor; ?>
            </dl>

            <?php else: ?>
            <h3>No reviews yet.</h3>
            <p>Share your experience to help the next customer choose.</p>

            <?php endif; ?>
            <p class="review-policy">
                Reviews are moderated for spam and abuse. A verified purchase means a paid,
                delivered order in that customer’s account.
            </p>
        </aside>
        <div class="reviews-content">
            <?php foreach ($reviewData['items'] as $review): ?>
            <article class="published-review">
                <div class="review-meta">
                    <div>
                        <strong><?= e(reviewer_name($review['customer_name'])) ?></strong>
                        <?php if ($review['verified_purchase']): ?>
                        <span class="verified-review"><?= icon('check', 13) ?> Verified purchase</span>
                        <?php endif; ?>
                    </div>
                    <time datetime="<?= e(date('Y-m-d', strtotime($review['created_at']))) ?>"><?= date('d M Y', strtotime($review['created_at'])) ?></time>
                </div>
                <?= review_stars((int) $review['rating']) ?>
                <h3><?= e($review['title']) ?></h3>
                <p><?= nl2br(e($review['body'])) ?></p>
            </article>

            <?php endforeach; ?>

            <?php if ($reviewData['pages'] > 1): ?>
            <nav class="pagination review-pagination" aria-label="Review pages">
                <?php if ($reviewData['page'] > 1): ?>
                <a
                    class="button secondary small"
                    href="/product/<?= e($product['slug']) ?>?reviews_page=<?= $reviewData['page'] - 1 ?>#reviews"
                >
                    Previous
                </a>
                <?php endif; ?>
                <span>Page <?= $reviewData['page'] ?> of <?= $reviewData['pages'] ?></span>

                <?php if ($reviewData['page'] < $reviewData['pages']): ?>
                <a
                    class="button secondary small"
                    href="/product/<?= e($product['slug']) ?>?reviews_page=<?= $reviewData['page'] + 1 ?>#reviews"
                >
                    Next
                </a>
                <?php endif; ?>
            </nav>

            <?php endif; ?>
            <section class="write-review" id="review-form" aria-labelledby="review-form-heading">
                <?php if (empty($_SESSION['customer_id'])): ?>
                <h3 id="review-form-heading">Share your experience.</h3>
                <p>
                    Sign in to write a review. Your first name and last initial will be shown
                    publicly.
                </p>
                <a class="button secondary" href="/account/login">
                    Sign in to review <?= icon('arrow', 17) ?>
                </a>

                <?php else: ?>

                <?php
                $ownReview = $reviewData['own'];
                $reviewForm = $_SESSION['review_form'][$_SESSION['customer_id']][$product['id']] ?? $ownReview ?? [];
                unset($_SESSION['review_form'][$_SESSION['customer_id']][$product['id']]);
                ?>
                <h3 id="review-form-heading"><?= $ownReview ? 'Your review' : 'Write a review' ?></h3>

                <?php if ($ownReview): ?>
                <p class="own-review-status" role="status">
                    <?php if ($ownReview['status'] === 'approved'): ?>
                    Your review is published. Edits will need approval before appearing.
                    <?php elseif ($ownReview['status'] === 'pending'): ?>
                    Your review is awaiting moderation. You can update it below.
                    <?php else: ?>
                    Your review was not published. You can edit and resubmit it.
                    <?php endif; ?>
                </p>

                <?php endif; ?>
                <p class="review-form-note">
                    Your first name and last initial will be shown. Please review the product itself
                    and leave out phone numbers, addresses, and other private details.
                </p>
                <form method="post" data-loading="Sending review…">
                    <?= csrf() ?>
                    <input type="hidden" name="action" value="customer_review" />
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>" />
                    <div class="field">
                        <label for="review-rating">Your rating</label>
                        <select id="review-rating" name="rating" required>
                            <option value="">Choose a rating</option>

                            <?php
                            foreach ([5 => '5 — Excellent', 4 => '4 — Good', 3 => '3 — Okay', 2 => '2 — Disappointing', 1 => '1 — Poor'] as $value => $label):
                            ?>
                            <option value="<?= $value ?>" <?= (string) ($reviewForm['rating'] ?? '') === (string) $value ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>

                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="review-title">Review title</label>
                        <input
                            id="review-title"
                            name="title"
                            value="<?= e($reviewForm['title'] ?? '') ?>"
                            minlength="3"
                            maxlength="140"
                            required
                            placeholder="A short summary of your experience"
                        />
                    </div>
                    <div class="field">
                        <label for="review-body">Your review</label>
                        <textarea
                            id="review-body"
                            name="body"
                            rows="5"
                            minlength="10"
                            maxlength="4000"
                            required
                            placeholder="What worked well? What could be better?"
                        >
<?= e($reviewForm['body'] ?? '') ?></textarea
                        >
                        <small>10–4,000 characters. Reviews appear after moderation.</small>
                    </div>
                    <button type="submit" class="button dark">
                        <?= $ownReview ? 'Update review' : 'Submit review' ?> <?= icon('arrow', 17) ?>
                    </button>
                </form>

                <?php if ($ownReview): ?>
                <form
                    method="post"
                    class="withdraw-review"
                    data-confirm="Withdraw your review? You can submit a new one later."
                >
                    <?= csrf() ?>
                    <input type="hidden" name="action" value="customer_review_delete" />
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>" />
                    <input type="hidden" name="review_id" value="<?= $ownReview['id'] ?>" />
                    <button type="submit" class="text-button">Withdraw review</button>
                </form>

                <?php endif; ?>

                <?php endif; ?>
            </section>
        </div>
    </div>
</section>

<?php endif; ?>
