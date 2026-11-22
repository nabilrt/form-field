<?php
$filters = catalog_filters($_GET);
$term = $filters['term'];
$category = $filters['category'];
$sort = $filters['sort'];
$query = $filters['query'];
$totalProducts = product_count($filters['where'], $filters['params']);
$totalPages = max(1, (int) ceil($totalProducts / 12));
$pageNumber = max(1, min($totalPages, (int) ($_GET['page'] ?? 1)));
$listing = products($filters['where'], $filters['params'], $filters['ordering'], 12, ($pageNumber - 1) * 12, $filters);
$catName = 'The collection.';
foreach ($categories as $c) {
    if ($c['slug'] === $category) {
        $catName = $c['name'] . '.';
    }
}
$hasFilters = $filters['min'] !== null || $filters['max'] !== null || $filters['inStock'] || $filters['sale'];
?>
<div class="shop-heading">
    <div class="breadcrumb">
        <a href="/">Home</a>
        <span>/</span>
        <span>Shop</span>
    </div>
    <h1><?= e($term ? 'Results for “' . $term . '”' : $catName) ?></h1>
    <p>Furniture, tech, and essentials for every day.</p>
    <nav class="shopping-tools-nav" aria-label="Shopping tools">
        <a href="/build-a-set"><?= icon('bag', 15) ?> Build a set</a>
        <a href="/compare"><?= icon('compare', 15) ?> Compare products</a>
        <a href="/recent"><?= icon('return', 15) ?> Recently viewed</a>
    </nav>
</div>
<div class="shop-toolbar">
    <nav class="category-tabs" aria-label="Product categories">
        <a href="/shop?<?= e(http_build_query(array_diff_key($query, ['category' => true]))) ?>" class="<?= $category === '' ? 'active' : '' ?>">All objects</a>

        <?php foreach ($categories as $c): ?>
        <a href="/shop?<?= e(http_build_query(array_merge($query, ['category' => $c['slug']]))) ?>" class="<?= $category === $c['slug'] ? 'active' : '' ?>"><?= e($c['name']) ?></a>

        <?php endforeach; ?>
    </nav>
    <form action="/shop" class="sort-form">
        <?php foreach (array_diff_key($query, ['sort' => true]) as $key => $value): ?>
        <input type="hidden" name="<?= e($key) ?>" value="<?= e($value) ?>" />

        <?php endforeach; ?>
        <label for="sort">Sort by</label>
        <select name="sort" id="sort" data-auto-submit>
            <?php
            foreach (['featured' => 'Featured', 'newest' => 'Newest arrivals', 'price-asc' => 'Price: low to high', 'price-desc' => 'Price: high to low'] as $value => $label):
            ?>
            <option value="<?= $value ?>" <?= $value === $sort ? 'selected' : '' ?>><?= $label ?></option>

            <?php endforeach; ?>
        </select>
        <noscript><button>Sort</button></noscript>
    </form>
</div>
<div class="catalog-filter-wrap">
    <details class="catalog-filters" <?= $hasFilters ? 'open' : '' ?>>
        <summary>Refine the collection <?= icon('plus', 16) ?></summary>
        <form action="/shop" class="catalog-filter-form">
            <?php foreach (array_intersect_key($query, array_flip(['q', 'category', 'sort'])) as $key => $value): ?>
            <input type="hidden" name="<?= e($key) ?>" value="<?= e($value) ?>" />

            <?php endforeach; ?>
            <div class="field">
                <label for="min-price">Minimum price (৳)</label>
                <input
                    id="min-price"
                    name="min_price"
                    type="number"
                    min="0"
                    max="100000000"
                    step="1"
                    value="<?= e($filters['min']) ?>"
                    placeholder="No minimum"
                />
            </div>
            <div class="field">
                <label for="max-price">Maximum price (৳)</label>
                <input
                    id="max-price"
                    name="max_price"
                    type="number"
                    min="0"
                    max="100000000"
                    step="1"
                    value="<?= e($filters['max']) ?>"
                    placeholder="No maximum"
                />
            </div>
            <label class="checkbox-row">
                <input type="checkbox" name="in_stock" value="1" data-auto-submit <?= $filters['inStock'] ? 'checked' : '' ?> />
                In stock only
            </label>
            <label class="checkbox-row">
                <input type="checkbox" name="sale" value="1" data-auto-submit <?= $filters['sale'] ? 'checked' : '' ?> />
                On sale
            </label>
            <button type="submit" class="button dark small">Apply filters</button>

            <?php if ($hasFilters): ?>
            <a class="subtle-link" href="/shop?<?= e(http_build_query(array_intersect_key($query, array_flip(['q', 'category', 'sort'])))) ?>">Clear filters</a>

            <?php endif; ?>
        </form>
    </details>
</div>
<section class="section shop-products">
    <div class="results-count" role="status">
        <?= $totalProducts ?> products found

        <?php if ($term): ?>
        ·
        <a href="/shop">Clear search</a>

        <?php endif; ?>
    </div>
    <div class="product-grid">
        <?php foreach ($listing as $p): ?>

        <?php require __DIR__ . '/product-card.php'; ?>

        <?php endforeach; ?>
    </div>

    <?php if (!$listing): ?>
    <div class="empty-state">
        <?= icon('search', 36) ?>
        <h2>No products match those filters.</h2>
        <p>Try a wider price range or explore the full collection.</p>
        <a class="button dark" href="/shop">Explore all products</a>
    </div>

    <?php endif; ?>

    <?php if ($totalPages > 1): ?>
    <nav class="pagination" aria-label="Pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a class="button <?= $i === $pageNumber ? 'dark' : 'secondary' ?>" <?= $i === $pageNumber ? 'aria-current="page"' : '' ?> href="/shop?<?= e(http_build_query(array_merge($query, ['page' => $i]))) ?>">
            <?= $i ?>
        </a>

        <?php endfor; ?>
    </nav>

    <?php endif; ?>
</section>
