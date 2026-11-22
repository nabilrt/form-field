<?php
$comparisonItems = comparison_payload();
$showComparisonTray = comparison_tray_allowed($page);
?>
<aside
    class="compare-tray"
    data-compare-tray
    data-items="<?= e(json_encode($comparisonItems)) ?>"
    data-allow-tray="<?= $showComparisonTray ? 'true' : 'false' ?>"
    aria-label="Product comparison"
    <?= !$comparisonItems || !$showComparisonTray ? 'hidden' : '' ?>
>
    <div class="compare-tray-inner">
        <div class="compare-thumbnails" data-compare-thumbnails>
            <?php foreach ($comparisonItems as $item): ?>
            <a href="<?= e($item['url']) ?>">
                <img src="<?= e($item['image']) ?>" alt="<?= e($item['name']) ?>" width="42" height="48" />
            </a>
            <?php endforeach; ?>
        </div>
        <div class="compare-tray-copy">
            <strong>Worth a closer look.</strong>
            <span data-compare-count><?= count($comparisonItems) ?> of 3 products selected</span>
        </div>
        <form method="post" data-compare-form class="compare-clear-form">
            <?= csrf() ?>
            <input type="hidden" name="action" value="compare_clear" />
            <input type="hidden" name="return_to" value="<?= e(safe_shopping_return($_SERVER['REQUEST_URI'] ?? '/shop')) ?>" />
            <button type="submit" class="text-button">Clear</button>
        </form>
        <a class="button dark small" href="/compare" data-compare-link>
            Compare products <?= icon('arrow', 16) ?>
        </a>
    </div>
    <p class="compare-feedback" data-compare-feedback role="status" aria-live="polite"></p>
</aside>
<div class="shopping-feedback" data-shopping-feedback role="status" aria-live="polite" hidden></div>
