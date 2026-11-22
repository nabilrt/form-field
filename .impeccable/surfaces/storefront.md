# Storefront surface

Mode: Persuade for the homepage; Operate for catalog, cart, checkout, and customer account tasks.

Routes: `/`, `/shop`, `/product/*`, `/cart`, `/checkout`, `/account/*`.

The homepage introduces Form & Field through a split headline/photo slideshow, a short introduction, collection photography, featured products, and an editorial image/text section. Product and account screens inherit the monochrome controls and local font pairing; purchase tasks keep prices, variants, stock, delivery, and payment choices close to their actions. Prices use BDT/taka.

The white interface frames locally generated sample photography for homeware, electronics, everyday carry, and personal care. Avoid presenting sample imagery or catalog copy as verified product specifications. Empty and error states must point to the relevant recovery action.

QA scope: documented from PHP templates, CSS, and JavaScript. Local server sockets and Chromium startup were blocked; no browser screenshot, contrast measurement, or responsive-render verdict is available. The isolated SQLite-adapter suite passes 177 application checks; this is application-behavior evidence, not live MySQL or visual verification.

The homepage now also contains a four-product new-arrivals grid, a three-product electronics row, and four practical FAQ disclosures. Catalog filters combine search, category, price range, stock, sale, and sorting through regular GET forms. Product hearts and `/saved` provide a browser-local wishlist; account order history exposes ownership-checked Buy again actions. All additions inherit the existing monochrome layout and local fonts. Expanded isolated route/action checks cover backend behavior; no new browser-render verdict is claimed.

Product pages now include real approved-review averages, a five-star distribution, paginated customer feedback, and authenticated review writing/editing/withdrawal. Catalog cards show compact real rating counts. The form discloses moderation and first-name/last-initial visibility. Pending and rejected entries stay private to their author and merchant. The existing store layout continues working before the new review table is installed.

Shopping tools add `/compare`, `/build-a-set`, and `/recent`. Comparison shows real catalog details for up to three session selections. The set builder combines up to six products with current variants, stock checks, and an optional advisory budget. History retains eight unique product views per session. Floating comparison and mobile set actions reserve footer clearance. Preloaded gallery fades and selection/subtotal feedback respect reduced motion. No extra dependencies or schema migration are introduced by these tools.
