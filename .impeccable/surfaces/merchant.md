# Merchant workspace surface

Mode: Operate.

Routes: `/admin` and child routes.

A fixed white header and sidebar frame compact task pages. Navigation exposes orders, products, collections, customers, discounts, analytics, storefront slides, and settings. Panels group meaningful tasks; tables prioritize names, stock, prices, status, and fulfillment. Product editing separates detailed content and variants from publishing controls. Small screens use a navigation drawer, stacked panels, and horizontally scrollable tables.

All main backgrounds remain white. Black and neutral-gray controls follow the user's Shopify-style reference and requested Inter/Manrope pairing. The dashboard uses direct operational copy, including “Store overview” and “Inventory to watch,” which lists the three lowest-stock active variants. Metrics derive from actual stored orders; initial empty states do not invent sales.

Verification is limited to source and isolated CLI behavior checks. A source reviewer confirmed three specific corrections: blob image previews permitted by CSP, MySQL connection timezone set to Bangladesh time, and database exceptions excluded from customer-facing error messages. This is not a browser or native MySQL approval.

The main column now grows to the viewport height on short pages, with the footer following content in normal flow. Settings have five keyboard-accessible tabs with independent group saves and URL-fragment selection. Shared password visibility controls and restrained state feedback apply here as well as in customer forms. These changes retain the existing palette, typography, and density.

The Orders page now has an authenticated CSV export action that shares status/search filters with its table and uses bounded batches. It inherits the existing bordered secondary button style and stacks its heading actions on small screens.

Reviews now has a moderation queue with search, status tabs, excerpts/full-text disclosures, and publish/reject/return-to-pending controls. Revision checks protect against approving a newly edited body from a stale form. Moderation changes state, not the customer’s rating or words. No product reviews are fabricated for demonstration.
