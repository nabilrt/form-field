<section class="section about-page">
    <div class="about-hero">
        <h1>
            Good things.
            <br />
            For your kind
            <br />
            of everyday.
        </h1>
        <p>
            A comfortable home. A better daily routine. The small things you reach for again and
            again. That’s where Form & Field begins.
        </p>
    </div>
    <img
        class="about-image"
        src="/assets/hero.jpg"
        alt="A warm, thoughtfully furnished living room"
        width="1600"
        height="900"
    />
    <div class="about-body">
        <h2>
            Less noise.
            <br />
            More character.
        </h2>
        <div>
            <p>
                We bring together homeware, useful technology, and daily essentials with a simple
                idea: the things around you should earn their place.
            </p>
            <p>
                From the texture of a cushion to the feel of a keyboard, we look for thoughtful
                details and things that make everyday life a little more enjoyable.
            </p>
            <h2 id="delivery">Delivery & questions</h2>
            <p>
                We deliver across Bangladesh. Standard delivery is <?= money(convert(setting('shipping'))) ?>, with free
                delivery from <?= money(convert(setting('free_shipping'))) ?>. Choose cash on delivery at checkout, or bank
                transfer when bank details are available.
            </p>
            <p>
                For delivery estimates, return enquiries, or help with an order, contact
                <a href="mailto:<?= e(setting('store_email')) ?>"><?= e(setting('store_email')) ?></a>
                . Please include your order number so we can help.
            </p>
        </div>
    </div>
</section>
