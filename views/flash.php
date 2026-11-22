<?php
if (isset($_SESSION['flash'])):$flash = $_SESSION['flash'];
unset($_SESSION['flash']);
?>
<div class="flash <?= $flash['type'] === 'error' ? 'error' : '' ?>" role="<?= $flash['type'] === 'error' ? 'alert' : 'status' ?>">
    <?= icon($flash['type'] === 'error' ? 'close' : 'check', 18) ?>
    <span>
        <?= e($flash['text']) ?>
        <?php if (!empty($flash['link']['href']) && str_starts_with($flash['link']['href'], '/') && !str_starts_with($flash['link']['href'], '//')): ?>
        <a class="flash-link" href="<?= e($flash['link']['href']) ?>"><?= e($flash['link']['label'] ?? 'View') ?></a>
        <?php endif; ?>
    </span>
    <button class="icon-button" data-dismiss aria-label="Dismiss notification">
        <?= icon('close', 16) ?>
    </button>
</div>

<?php endif; ?>
