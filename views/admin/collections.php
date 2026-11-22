<?php
$list = q(
    'SELECT c.*,COUNT(p.id) product_count FROM categories c LEFT JOIN products p ON p.category_id=c.id GROUP BY c.id ORDER BY c.id'
)->fetchAll();
?>
<div class="admin-page-heading">
    <div>
        <h1>Collections</h1>
        <p>Organize your catalog by category.</p>
    </div>
    <span class="date-control"><?= count($list) ?> collections</span>
</div>
<div class="admin-split">
    <section class="admin-card">
        <div class="card-header"><h2>Your collections</h2></div>
        <table>
            <thead>
                <tr>
                    <th>Collection</th>
                    <th>Products</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($list as $c): ?>
                <tr>
                    <td>
                        <strong><?= e($c['name']) ?></strong>
                        <small><?= e($c['description']) ?></small>
                    </td>
                    <td><?= $c['product_count'] ?></td>
                    <td>
                        <a
                            class="icon-button"
                            target="_blank"
                            href="/shop?category=<?= e($c['slug']) ?>"
                            aria-label="View <?= e($c['name']) ?>"
                        >
                            <?= icon('external', 16) ?>
                        </a>
                    </td>
                </tr>

                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
    <section class="admin-card padded">
        <h2>Create a collection</h2>
        <p class="muted">Group related products so customers can browse easily.</p>
        <form method="post">
            <?= csrf() ?>
            <input type="hidden" name="action" value="save_category" />
            <div class="field">
                <label for="name">Collection name</label>
                <input
                    id="name"
                    name="name"
                    required
                    maxlength="100"
                    placeholder="e.g. Everyday essentials"
                />
            </div>
            <div class="field">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3"></textarea>
            </div>
            <button class="button dark full-width">Create collection <?= icon('plus', 16) ?></button>
        </form>
    </section>
</div>
