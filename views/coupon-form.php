<?php /* Expects $couponReturn: '/cart' or '/checkout'. */ ?>
<form method="post" class="coupon-form" data-carry-checkout>
    <?php echo csrf() ?>
    <input type="hidden" name="action" value="coupon" />
    <input type="hidden" name="return_to" value="<?php echo e($couponReturn) ?>" />
    <label class="sr-only" for="promo-code">Promo code</label>
    <input
        id="promo-code"
        name="code"
        placeholder="Have a promo code?"
        required
        maxlength="50"
    />
    <button class="button secondary">Apply</button>
</form>

<?php if (! empty($_SESSION['coupon'])): ?>
<form method="post" class="applied-code" data-carry-checkout>
    <?php echo csrf() ?>
    <input type="hidden" name="action" value="coupon_remove" />
    <input type="hidden" name="return_to" value="<?php echo e($couponReturn) ?>" />
    <span>
        <?php echo icon('tag', 16) ?>

        <?php echo e($_SESSION['coupon']) ?>
    </span>
    <button class="text-button">Remove</button>
</form>

<?php endif; ?>
