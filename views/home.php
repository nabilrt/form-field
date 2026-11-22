<?php $slides = q('SELECT * FROM sliders WHERE active=1 ORDER BY position,id')->fetchAll(); ?>

<?php if ($slides): ?>
<section class="hero" aria-roledescription="carousel" aria-label="Featured collections">
    <div class="slides">
        <?php foreach ($slides as $i => $slide): ?>
        <div class="slide <?= $i === 0 ? 'current' : '' ?>" data-slide="<?= $i ?>" <?= $i ? 'aria-hidden="true"' : '' ?>>
            <img
                class="hero-image"
                src="<?= e(image_url($slide['image'])) ?>"
                alt="A thoughtfully furnished space with natural materials"
                fetchpriority="<?= $i === 0 ? 'high' : 'low' ?>"
            />
            <div class="hero-copy">
                <h1><?= nl2br(e(str_replace('\\n', "\n", $slide['title']))) ?></h1>
                <p><?= e($slide['description']) ?></p>
                <a class="button dark" href="<?= e($slide['link']) ?>"><?= e($slide['button']) ?> <?= icon('arrow', 18) ?></a>
            </div>
        </div>

        <?php endforeach; ?>
    </div>
    <div class="hero-bottom">
        <span>Considered objects. Comfortable living.</span>
        <div class="slider-controls">
            <button class="icon-button" data-slide-prev aria-label="Previous slide">
                <?= icon('arrow', 16) ?>
            </button>
            <div class="slide-dots">
                <?php foreach ($slides as $i => $slide): ?>
                <button
                    data-slide-go="<?= $i ?>"
                    class="<?= $i === 0 ? 'active' : '' ?>"
                    aria-label="Go to slide <?= $i + 1 ?>"
                    aria-pressed="<?= $i === 0 ? 'true' : 'false' ?>"
                ></button>

                <?php endforeach; ?>
            </div>
            <button class="icon-button" data-slide-next aria-label="Next slide">
                <?= icon('arrow', 16) ?>
            </button>
            <button class="slider-pause" data-slide-pause aria-label="Pause slideshow">
                Pause
            </button>
        </div>
    </div>
</section>

<?php endif; ?>
<section class="home-intro">
    <span>Home. Work. Everyday.</span>
    <p>
        A considered collection for the way you live.
        <br />
        From your first coffee
        <br />
        to your last email.
    </p>
</section>
<section class="set-invitation">
    <div>
        <h2>Put your everyday together.</h2>
        <p>
            A desk refresh, a slower morning, a better-packed bag. Choose the pieces and build a set
            around your budget.
        </p>
    </div>
    <a class="button dark" href="/build-a-set">Build your set <?= icon('arrow', 18) ?></a>
</section>
<section class="section collection-section">
    <div class="section-heading">
        <h2>Browse by collection.</h2>
        <a class="text-link" href="/shop">Shop all collections <?= icon('arrow', 18) ?></a>
    </div>
    <div class="collection-grid">
        <?php
        $catImages = [
            'Living' => '/assets/product-1.jpg',
            'Electronics' => '/assets/product-9.jpg',
            'Everyday carry' => '/assets/product-12.jpg',
            'Personal care' => '/assets/product-14.jpg',
        ];
        foreach ($categories as $i => $c):
            if (!isset($catImages[$c['name']])) {
                continue;
            }
        ?>
        <a class="collection-card" href="/shop?category=<?= e($c['slug']) ?>">
            <div>
                <img
                    src="<?= e(image_url($catImages[$c['name']])) ?>"
                    alt="<?= e($c['name']) ?> collection"
                    loading="lazy"
                    width="500"
                    height="600"
                />
            </div>
            <h3><?= e($c['name']) ?> <?= icon('arrow', 20) ?></h3>
        </a>

        <?php endforeach; ?>
    </div>
</section>
<section class="section new-arrivals-section">
    <div class="section-heading">
        <div>
            <h2>New to the collection.</h2>
            <p>The latest additions, ready to find their place.</p>
        </div>
        <a class="text-link" href="/shop?sort=newest">All new arrivals <?= icon('arrow', 18) ?></a>
    </div>
    <div class="product-grid">
        <?php foreach (products('p.active=1', [], 'p.id DESC', 4) as $p): ?>

        <?php require __DIR__ . '/product-card.php'; ?>

        <?php endforeach; ?>
    </div>
</section>
<section class="section featured-section">
    <div class="section-heading">
        <div>
            <h2>Selected for your everyday.</h2>
            <p>Homeware, useful tech, and daily essentials.</p>
        </div>
        <a class="text-link" href="/shop">Explore the edit <?= icon('arrow', 18) ?></a>
    </div>
    <div class="product-grid">
        <?php
        foreach (products('p.active=1 AND p.featured=1', [], 'p.id ASC', 8) as $p) {
            require __DIR__ . '/product-card.php';
        }
        ?>
    </div>
</section>
<section class="editorial">
    <div class="editorial-image">
        <img
            src="<?= e(image_url('/assets/hero-2.jpg')) ?>"
            alt="Natural textures and thoughtful details in a warm home"
            loading="lazy"
            width="900"
            height="850"
        />
    </div>
    <div class="editorial-copy">
        <h2>
            A slower pace.
            <br />
            A better place.
        </h2>
        <p>
            We believe the best spaces aren’t put together all at once. They grow around the way you
            live — a favorite chair, a well-used mug, a soft place to land.
        </p>
        <p>That’s what we look for. Things you’ll reach for, live with, and make your own.</p>
        <a class="text-link" href="/about">Our approach <?= icon('arrow', 18) ?></a>
    </div>
</section>

<?php $electronics = products('p.active=1 AND c.slug=?', ['electronics'], 'p.id DESC', 3); ?>

<?php if ($electronics): ?>
<section class="section home-tech">
    <div class="section-heading">
        <div>
            <h2>Tech for your daily routine.</h2>
            <p>For the desk, the commute, and everything in between.</p>
        </div>
        <a class="text-link" href="/shop?category=electronics">Shop electronics <?= icon('arrow', 18) ?></a>
    </div>
    <div class="product-grid" tabindex="0" aria-label="Electronics products">
        <?php foreach ($electronics as $p): ?>

        <?php require __DIR__ . '/product-card.php'; ?>

        <?php endforeach; ?>
    </div>
</section>

<?php endif; ?>
<section class="service-strip">
    <div>
        <?= icon('truck', 27) ?>
        <span>
            <strong>Delivered to your door</strong>
            <small>Across Bangladesh</small>
        </span>
    </div>
    <div>
        <?= icon('leaf', 27) ?>
        <span>
            <strong>Thoughtfully chosen</strong>
            <small>Natural materials, lasting character</small>
        </span>
    </div>
    <div>
        <?= icon('bag', 27) ?>
        <span>
            <strong>Your way to pay</strong>
            <small>Cash on delivery or bank transfer</small>
        </span>
    </div>
</section>

<section class="section home-faq">
    <div class="faq-intro">
        <h2>A few things to know.</h2>
        <p>The details that make choosing easier.</p>
        <a class="text-link" href="/about#delivery">Delivery & help <?= icon('arrow', 18) ?></a>
    </div>
    <div class="faq-list">
        <details>
            <summary>Where do you deliver? <?= icon('plus', 17) ?></summary>
            <p>
                We deliver across Bangladesh. Standard delivery is <?= money(convert(setting('shipping'))) ?>, and orders from
                <?= money(convert(setting('free_shipping'))) ?> qualify for free delivery before promo discounts. Contact
                <?= e(setting('store_email')) ?> for delivery estimates.
            </p>
        </details>
        <details>
            <summary>How can I pay? <?= icon('plus', 17) ?></summary>
            <p>
                Cash on delivery is available. Bank transfer is also offered once the store has
                provided its bank details. Transfer instructions and your payment reference appear
                when you place your order.
            </p>
        </details>
        <details>
            <summary>Do I need an account to order? <?= icon('plus', 17) ?></summary>
            <p>
                You can check out as a guest. An account lets you save delivery addresses, see
                orders placed while signed in, and buy items again using their current price and
                availability.
            </p>
        </details>
        <details>
            <summary>Where can I get my invoice? <?= icon('plus', 17) ?></summary>
            <p>
                Your confirmation page includes a printable invoice. Signed-in customers can also
                open invoices from their order history. Use your browser’s print menu to save a PDF.
            </p>
        </details>
    </div>
</section>

<?php require __DIR__ . '/recent-strip.php'; ?>
