<?php $compared = comparison_products(); ?>
<section class="section comparison-page">
    <div class="breadcrumb">
        <a href="/shop">Shop</a>
        <span>/</span>
        <span>Compare products</span>
    </div>
    <div class="section-heading">
        <div>
            <h1>A closer comparison.</h1>
            <p>Up to three products. The details, side by side.</p>
        </div>
        <a class="text-link" href="/shop">Keep exploring <?= icon('arrow', 17) ?></a>
    </div>

    <?php if (count($compared) < 2): ?>
    <div class="comparison-empty">
        <?= icon('compare', 34) ?>
        <h2><?= $compared ? 'Choose one more product.' : 'Start with a few possibilities.' ?></h2>
        <p>
            Use Compare on product cards to line up prices, finishes, materials, and customer
            ratings.
        </p>

        <?php if ($compared): ?>
        <p>
            Currently selected:
            <strong><?= e($compared[0]['name']) ?></strong>
            .
        </p>
        <?php endif; ?>
        <a class="button dark" href="/shop">Find products to compare <?= icon('arrow', 17) ?></a>
    </div>

    <?php else: ?>
    <div class="comparison-controls">
        <label class="checkbox-row">
            <input type="checkbox" data-compare-differences />
            Show only differences
        </label>
        <span>Starting prices in <?= e(current_currency_code()) ?> · Choose finishes on the product page.</span>
    </div>
    <div class="comparison-scroll" tabindex="0" role="region" aria-label="Product comparison table">
        <table class="comparison-table">
            <thead>
                <tr>
                    <th scope="col">The details</th>
                    <?php foreach ($compared as $p): ?>
                    <th scope="col">
                        <a href="/product/<?= e($p['slug']) ?>">
                            <img
                                src="<?= e(image_url($p['image'])) ?>"
                                alt="<?= e($p['name']) ?>"
                                width="340"
                                height="340"
                            />
                            <strong><?= e($p['name']) ?></strong>
                        </a>
                        <form method="post" data-compare-form>
                            <?= csrf() ?>
                            <input type="hidden" name="action" value="compare_remove" />
                            <input type="hidden" name="product_id" value="<?= $p['id'] ?>" />
                            <input type="hidden" name="return_to" value="/compare" />
                            <button type="submit" class="text-button">Remove</button>
                        </form>
                    </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach (comparison_rows($compared) as $row): ?>
                <tr data-compare-equal="<?= $row['equal'] ? 'true' : 'false' ?>">
                    <th scope="row"><?= e($row['label']) ?></th>
                    <?php foreach ($row['values'] as $value): ?>
                    <td><?= e($value) ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th scope="row">Explore</th>
                    <?php foreach ($compared as $p): ?>
                    <td>
                        <a class="button dark small" href="/product/<?= e($p['slug']) ?>">
                            View product <?= icon('arrow', 15) ?>
                        </a>
                    </td>
                    <?php endforeach; ?>
                </tr>
            </tfoot>
        </table>
    </div>
    <p class="comparison-hint">Scroll horizontally to see every column on a small screen.</p>

    <?php endif; ?>

    <?php if ($compared): ?>
    <form method="post" class="comparison-reset" data-compare-form>
        <?= csrf() ?>
        <input type="hidden" name="action" value="compare_clear" />
        <input type="hidden" name="return_to" value="/compare" />
        <button type="submit" class="text-button">Clear comparison</button>
    </form>
    <?php endif; ?>
</section>
