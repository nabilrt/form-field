<?php
$slides = q('SELECT * FROM sliders ORDER BY position,id')->fetchAll();
$editSlide = isset($_GET['edit']) ? q('SELECT * FROM sliders WHERE id=?', [(int) $_GET['edit']])->fetch() : null;
?>
<div class="admin-page-heading">
    <div>
        <h1>Your online store</h1>
        <p>Manage your homepage banners and destinations.</p>
    </div>
    <a href="/" target="_blank" class="button secondary small">View storefront <?= icon('external', 15) ?></a>
</div>
<div class="admin-split">
    <div>
        <section class="admin-card">
            <div class="card-header">
                <div>
                    <h2>Homepage slides</h2>
                    <p>
                        Your slides appear in display order. Upload a wide image for the best
                        result.
                    </p>
                </div>
            </div>

            <?php foreach ($slides as $s): ?>
            <article class="slider-admin-row">
                <img src="<?= e(image_url($s['image'])) ?>" alt="<?= e($s['title']) ?>" />
                <div>
                    <strong><?= e(str_replace('\\n', ' ', $s['title'])) ?></strong>
                    <small>Position <?= $s['position'] ?> · <?= $s['active'] ? 'Visible' : 'Hidden' ?></small>
                    <a class="subtle-link" href="/admin/sliders?edit=<?= $s['id'] ?>">
                        Edit slide
                    </a>
                </div>
                <form method="post" data-confirm="Delete this homepage slide?">
                    <?= csrf() ?>
                    <input type="hidden" name="action" value="delete_slider" />
                    <input type="hidden" name="id" value="<?= $s['id'] ?>" />
                    <button class="icon-button" aria-label="Delete slide"><?= icon('close', 16) ?></button>
                </form>
            </article>

            <?php endforeach; ?>
        </section>
        <div class="card-footer-note">
            Need to change featured products? Open a product and enable “Feature on the homepage.”
        </div>
    </div>
    <section class="admin-card padded">
        <div class="section-heading">
            <h2><?= $editSlide ? 'Edit slide' : 'Add a slide' ?></h2>

            <?php if ($editSlide): ?>
            <a href="/admin/sliders">New slide</a>

            <?php endif; ?>
        </div>
        <form method="post" enctype="multipart/form-data">
            <?= csrf() ?>
            <input type="hidden" name="action" value="save_slider" />
            <input type="hidden" name="id" value="<?= $editSlide['id'] ?? 0 ?>" />
            <input type="hidden" name="existing_image" value="<?= e($editSlide['image'] ?? '') ?>" />
            <div class="field">
                <label for="title">Headline</label>
                <textarea id="title" name="title" rows="2" maxlength="190" required>
<?= e(str_replace('\\n', "\n", $editSlide['title'] ?? '')) ?></textarea
                >
            </div>
            <div class="field">
                <label for="description">Supporting text</label>
                <textarea id="description" name="description" rows="3" maxlength="500" required>
<?= e($editSlide['description'] ?? '') ?></textarea
                >
            </div>
            <div class="field">
                <label for="slide-images">Banner image</label>
                <input
                    id="slide-images"
                    type="file"
                    name="images[]"
                    accept="image/jpeg,image/png,image/webp"
                    <?= $editSlide ? '' : 'required' ?>
                />
                <small>Landscape, at least 1600 px wide recommended. Maximum 5 MB.</small>
            </div>
            <div class="form-grid">
                <div class="field">
                    <label for="button">Button text</label>
                    <input
                        id="button"
                        name="button"
                        value="<?= e($editSlide['button'] ?? 'Explore collection') ?>"
                        maxlength="100"
                        required
                    />
                </div>
                <div class="field">
                    <label for="position">Display order</label>
                    <input
                        id="position"
                        name="position"
                        type="number"
                        value="<?= $editSlide['position'] ?? count($slides) ?>"
                        min="0"
                        required
                    />
                </div>
            </div>
            <div class="field">
                <label for="link">Button destination</label>
                <input
                    id="link"
                    name="link"
                    value="<?= e($editSlide['link'] ?? '/shop') ?>"
                    placeholder="/shop"
                    required
                    maxlength="255"
                />
            </div>
            <label class="checkbox-row">
                <input type="checkbox" name="active" <?= !$editSlide || $editSlide['active'] ? 'checked' : '' ?> />
                Show this slide
            </label>
            <button class="button dark full-width">Save slide <?= icon('check', 16) ?></button>
        </form>
    </section>
</div>
