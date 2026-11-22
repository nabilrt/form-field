# Product
<!-- impeccable:product-schema 1 -->
## Platform
web
## Stack
User requested raw PHP and MySQL. No framework.
## Users
Customers browse and purchase products; merchants manage products and orders.
## Product Purpose
A complete ecommerce storefront and Shopify-inspired admin panel.
## Capabilities and Constraints
Product variants, stock, gallery uploads, homepage sliders, session cart, checkout, invoices and billing. The chosen store identity is Form & Field. Currency is BDT (taka); payment methods are cash on delivery and bank transfer. Demo content is illustrative and editable.
## Brand Commitments
Modern, crafted Shopify-style UI, explicitly avoiding a generic AI appearance.
## Confirmed additions
Customer registration, sign-in, profile/password and address management, and order history. Percentage/fixed promo codes, minimum spend and expiry controls. Electronics and other categories with product imagery. Main backgrounds must be white, UI black and white, using Inter and Manrope fonts.
## Evidence on Hand
Seed data is illustrative: 15 products, seven collections. Photographs are generated demo assets. No live merchant payment, contact, or bank details were supplied.
## Verification limits
Local server sockets, MySQL runtime, and Chromium launch are blocked or unavailable in this environment. CLI route/action checks use an isolated SQLite syntax adapter; native MySQL verification is a separate step.

## Shopping and merchant additions
Browser-local wishlist (up to 50 products), combined price/stock/sale filters, ownership-checked Buy again actions, and authenticated filtered CSV exports. Homepage includes new arrivals, electronics, and practical FAQs. Wishlist storage is per browser, not synchronized to accounts. Existing orders and invoices remain unchanged by reordering. No schema migration is needed for these additions.

## Product reviews
Authenticated customers can rate, write, edit, and withdraw one review per product. Only moderated approved reviews contribute to public averages and rating distributions. Verified purchase is derived from paid, delivered account orders. Public names use a first name and last initial. No review data is fabricated or seeded. Existing installations need `php scripts/migrate-reviews.php`; the storefront remains usable before migration.

## Shopping decision tools
Session-based comparison for three products, a mix-and-match set builder for six distinct products with variant/quantity choices and an advisory BDT budget, and the eight latest unique product views. Set submission validates every choice before updating the cart. A bag delivery planner uses the current free-delivery threshold. No schema change is required; wishlist remains separately stored in this browser. No extra bundle discount or new payment integration is implied.
