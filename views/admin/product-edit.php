<div class="admin-page-heading">
    <div class="heading-with-back">
        <a href="/admin/products" class="icon-button back-button" aria-label="Back to products">
            <?= icon('arrow') ?>
        </a>
        <div>
            <h1><?= $product ? e($product['name']) : 'Add product' ?></h1>
            <p><?= $product ? 'Update product details, media, and variants.' : 'Add product details, media, and variants.' ?></p>
        </div>
    </div>

    <?php if ($product): ?>
    <a href="/product/<?= e($product['slug']) ?>" target="_blank" class="button secondary small">
        Preview <?= icon('external', 15) ?>
    </a>

    <?php endif; ?>
</div>
<form
    method="post"
    enctype="multipart/form-data"
    class="product-editor"
    data-loading="Saving product…"
>
    <?= csrf() ?>
    <input type="hidden" name="action" value="save_product" />
    <input type="hidden" name="id" value="<?= $product['id'] ?? 0 ?>" />
    <div class="editor-main">
        <section class="admin-card padded">
            <h2>Product details</h2>
            <div class="field">
                <label for="name">Title</label>
                <input
                    id="name"
                    name="name"
                    value="<?= e($product['name'] ?? '') ?>"
                    placeholder="Product name"
                    required
                    maxlength="190"
                />
            </div>
            <div class="field">
                <label for="description">Description</label>
                <textarea
                    id="description"
                    name="description"
                    rows="6"
                    placeholder="Describe the product, features, and what is included."
                    required
                >
<?= e($product['description'] ?? '') ?></textarea
                >
            </div>
            <div class="form-grid">
                <div class="field">
                    <label for="material">Materials / key features</label>
                    <input id="material" name="material" value="<?= e($product['material'] ?? '') ?>" maxlength="190" />
                </div>
                <div class="field">
                    <label for="slug">URL handle</label>
                    <input
                        id="slug"
                        name="slug"
                        value="<?= e($product['slug'] ?? '') ?>"
                        placeholder="Generated from the title"
                    />
                </div>
            </div>
            <div class="field">
                <label for="care">Care / technical details</label>
                <textarea id="care" name="care" rows="3"><?= e($product['care'] ?? '') ?></textarea>
            </div>
        </section>
        <section class="admin-card padded">
            <div class="section-heading">
                <h2>Media</h2>
                <span class="muted">First image is the cover</span>
            </div>
            <div class="media-grid">
                <?php foreach ($images as $img): ?>
                <label class="media-item">
                    <img src="<?= e(image_url($img['image'])) ?>" alt="Product gallery image" />
                    <span>
                        <input type="checkbox" name="remove_images[]" value="<?= $img['id'] ?>" />
                        Remove
                    </span>
                </label>

                <?php endforeach; ?>
            </div>
            <label class="upload-zone" for="product-images">
                <?= icon('image', 28) ?>
                <strong>Add images</strong>
                <span>Drag files here or click to browse</span>
                <small>JPG, PNG, WebP · Up to 5 MB each · Max 12</small>
                <input
                    id="product-images"
                    type="file"
                    name="images[]"
                    accept="image/jpeg,image/png,image/webp"
                    multiple
                    data-image-preview
                />
            </label>
            <div class="upload-preview" id="upload-preview"></div>
        </section>
        <section class="admin-card variants-card">
            <div class="card-header">
                <div>
                    <h2>Variants</h2>
                    <p>Manage pricing and inventory for every option.</p>
                </div>
                <button type="button" class="button secondary small" data-add-variant>
                    <?= icon('plus', 15) ?> Add variant
                </button>
            </div>
            <div class="table-scroll">
                <table id="variant-table">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Color / option</th>
                            <th>Size / type</th>
                            <th>Price (৳)</th>
                            <th>Original (৳)</th>
                            <th>Stock</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($variants as $i => $v): ?>
                        <tr>
                            <td>
                                <input
                                    type="hidden"
                                    name="variants[<?= $i ?>][id]"
                                    value="<?= $v['id'] ?>"
                                />
                                <input
                                    aria-label="SKU"
                                    name="variants[<?= $i ?>][sku]"
                                    value="<?= e($v['sku']) ?>"
                                    required
                                    maxlength="100"
                                />
                            </td>
                            <td>
                                <input
                                    aria-label="Color or option"
                                    name="variants[<?= $i ?>][color]"
                                    value="<?= e($v['color']) ?>"
                                    required
                                    maxlength="100"
                                />
                            </td>
                            <td>
                                <input
                                    aria-label="Size or type"
                                    name="variants[<?= $i ?>][size]"
                                    value="<?= e($v['size']) ?>"
                                    required
                                    maxlength="100"
                                />
                            </td>
                            <td>
                                <input
                                    aria-label="Price in taka"
                                    type="number"
                                    name="variants[<?= $i ?>][price]"
                                    value="<?= $v['price'] ?>"
                                    min="0"
                                    required
                                />
                            </td>
                            <td>
                                <input
                                    aria-label="Original price in taka"
                                    type="number"
                                    name="variants[<?= $i ?>][compare_price]"
                                    value="<?= $v['compare_price'] ?>"
                                    min="0"
                                />
                            </td>
                            <td>
                                <input
                                    aria-label="Stock"
                                    type="number"
                                    name="variants[<?= $i ?>][stock]"
                                    value="<?= $v['stock'] ?>"
                                    min="0"
                                    required
                                />
                            </td>
                            <td>
                                <button
                                    type="button"
                                    class="icon-button"
                                    data-remove-variant
                                    aria-label="Remove variant"
                                >
                                    <?= icon('close', 16) ?>
                                </button>
                            </td>
                        </tr>

                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="field-help">
                Set an original price higher than the current price to show a sale. Each SKU must be
                unique.
            </p>
        </section>
        <?php
        $extraCurrencies = array_diff_key(currency_list(), ['BDT' => true]);
        $savedVariants = array_filter($variants, static fn ($v) => !empty($v['id']));
        ?>
        <?php if ($extraCurrencies): ?>
        <section class="admin-card padded">
            <h2>Prices in other currencies</h2>
            <p class="field-help">
                Leave a price blank to convert from BDT at the exchange rate. An original price here shows a
                sale in that currency.
            </p>
            <?php if (!$savedVariants): ?>
            <p class="muted">Save the product first, then set prices for each variant.</p>
            <?php else: ?>
            <?php $explicit = q(
                'SELECT CONCAT(variant_id,\'-\',currency) k,amount,compare_amount FROM variant_prices WHERE variant_id IN ('
                . implode(',', array_fill(0, count($savedVariants), '?')) . ')',
                array_map('intval', array_column($savedVariants, 'id'))
            )->fetchAll(PDO::FETCH_UNIQUE); ?>
            <div class="table-scroll">
                <table class="variant-editor">
                    <thead>
                        <tr>
                            <th>Variant</th>
                            <?php foreach ($extraCurrencies as $cur): ?>
                            <th><?= e($cur['code']) ?> price (<?= e($cur['symbol']) ?>)</th>
                            <th><?= e($cur['code']) ?> original</th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($savedVariants as $v): ?>
                        <tr>
                            <td><?= e($v['sku']) ?><br /><small class="muted"><?= e($v['color'] . ' / ' . $v['size']) ?></small></td>
                            <?php foreach ($extraCurrencies as $cur):
                                $own = $explicit[$v['id'] . '-' . $cur['code']] ?? null;
                                $scale = 10 ** (int) $cur['decimals'];
                                $step = $cur['decimals'] ? '0.' . str_repeat('0', (int) $cur['decimals'] - 1) . '1' : '1';
                                $name = 'currency_prices[' . (int) $v['id'] . '][' . e($cur['code']) . ']';
                            ?>
                            <td>
                                <input
                                    type="number"
                                    min="0"
                                    step="<?= $step ?>"
                                    name="<?= $name ?>[amount]"
                                    value="<?= $own ? e(number_format($own['amount'] / $scale, (int) $cur['decimals'], '.', '')) : '' ?>"
                                    placeholder="<?= e(number_format(convert((int) $v['price'], $cur['code']) / $scale, (int) $cur['decimals'], '.', '')) ?>"
                                    aria-label="<?= e($cur['code'] . ' price for ' . $v['sku']) ?>"
                                />
                            </td>
                            <td>
                                <input
                                    type="number"
                                    min="0"
                                    step="<?= $step ?>"
                                    name="<?= $name ?>[compare]"
                                    value="<?= $own && $own['compare_amount'] !== null ? e(number_format($own['compare_amount'] / $scale, (int) $cur['decimals'], '.', '')) : '' ?>"
                                    aria-label="<?= e($cur['code'] . ' original price for ' . $v['sku']) ?>"
                                />
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>
    </div>
    <aside class="editor-sidebar">
        <section class="admin-card padded">
            <h2>Publishing</h2>
            <label class="checkbox-row">
                <input type="checkbox" name="active" <?= !$product || $product['active'] ? 'checked' : '' ?> />
                Active in your online store
            </label>
            <label class="checkbox-row">
                <input type="checkbox" name="featured" <?= !empty($product['featured']) ? 'checked' : '' ?> />
                Feature on the homepage
            </label>
        </section>
        <section class="admin-card padded">
            <h2>Organization</h2>
            <div class="field">
                <label for="category_id">Collection</label>
                <select id="category_id" name="category_id">
                    <option value="0">Uncategorized</option>

                    <?php foreach (q('SELECT * FROM categories ORDER BY name')->fetchAll() as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ($product['category_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>

                    <?php endforeach; ?>
                </select>
            </div>
            <a class="subtle-link" href="/admin/collections">Manage collections <?= icon('arrow', 15) ?></a>
        </section>
        <div class="editor-save">
            <button class="button dark full-width">Save product <?= icon('check', 17) ?></button>
            <a class="button secondary full-width" href="/admin/products">Cancel</a>
        </div>
    </aside>
</form>

<?php if ($product && $product['active']): ?>
<form
    method="post"
    class="archive-form"
    data-confirm="Archive this product? It will be hidden from the store."
>
    <?= csrf() ?>
    <input type="hidden" name="action" value="archive_product" />
    <input type="hidden" name="id" value="<?= $product['id'] ?>" />
    <button class="button secondary small">Archive product</button>
</form>

<?php endif; ?>
