---
name: Form & Field
description: A monochrome commerce interface with editorial product photography and a compact merchant workspace.
colors:
  ink: "#121212"
  primary: "#171717"
  white: "#ffffff"
  muted: "#656565"
  line: "#e5e5e5"
  soft: "#f6f6f6"
  hover: "#393939"
typography:
  heading:
    fontFamily: "Manrope, Inter, Arial, sans-serif"
    fontSize: "48px"
    fontWeight: 600
    lineHeight: 1.15
    letterSpacing: "-0.035em"
  body:
    fontFamily: "Inter, Arial, sans-serif"
    fontSize: "14px"
    lineHeight: 1.65
  merchant-body:
    fontFamily: "Inter, Arial, sans-serif"
    fontSize: "12px"
rounded:
  storefront-button: "3px"
  input: "4px"
  merchant-button: "5px"
  merchant-card: "11px"
spacing:
  compact: "12px"
  control: "24px"
  storefront-gutter: "64px"
components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.white}"
    rounded: "{rounded.storefront-button}"
    padding: "13px 24px"
    height: "49px"
  button-primary-hover:
    backgroundColor: "{colors.hover}"
    textColor: "{colors.white}"
---

# Design System: Form & Field

## Overview

The implemented interface pairs a spacious product storefront with a denser merchant workspace. The user explicitly chose white main backgrounds, black-and-white UI, Inter, and Manrope. These commitments take precedence over generic design-detector suggestions, including its warning about Inter.

Product photography supplies texture and color; interface chrome remains neutral. This document describes source implementation, not a browser-verified appearance. Route-specific composition lives in `.impeccable/surfaces/`.

## Colors

### Primary

Near-black primary controls and typography give actions clear weight. Primary button hover uses a lighter charcoal. Selection reverses to white on near-black.

### Neutral

White is the page foundation in the storefront, account area, and admin. Light-gray surfaces distinguish summaries, active navigation, and supporting sections; thin neutral rules separate content. Status badges also use black, white, and gray rather than colored semantic fills. Preserve descriptive status text.

Photography may contain natural colors. Do not introduce a colored UI accent or replace white main backgrounds with cream.

## Typography

Manrope carries headings and the wordmark; Inter carries paragraphs, forms, controls, navigation, and tabular data. Both variable fonts are hosted under `public/assets/fonts/` with `font-display: swap`.

Storefront hierarchy runs from responsive hero headings (`clamp(46px, 4.8vw, 76px)`) through section headings (30px) to product titles and supporting copy. Merchant page titles are 27px at the default desktop size; merchant section titles are generally 13px. Dense labels and secondary text use smaller sizes that still require browser-based readability verification.

Headings use restrained negative tracking. Tabular figures are enabled for tables, merchant metrics, and numeric inputs. Preserve the requested font pairing when adding screens.

## Layout

Storefront sections use a centered maximum width of 1536px and generous desktop side gutters. Product grids use four columns by default, three at the intermediate breakpoint, and two on mobile. The homepage splits copy and photography; small screens stack the copy above the image. Product, cart, checkout, and account pages collapse their multi-column layouts on small screens.

The merchant workspace uses a fixed top bar (64px) and sidebar (228px), with white content to the right. Widths tighten at intermediate breakpoints. At 760px the sidebar becomes a toggled drawer; at 540px major grids and editors stack. Tables keep horizontal scrolling when their columns need space.

Storefront media rules: 1600px minimum; 1200px, 900px, and 680px maximum. Merchant media rules: 1700px minimum; 1250px, 1000px, 760px, and 540px maximum.

## Elevation & Depth

Borders and pale surface changes provide most separation. Product images sit flat in their grid. Merchant panels have thin borders without resting shadows. Shadows are reserved for mobile navigation overlays. The image lightbox uses a dark backdrop; input focus uses a thin near-black ring.

## Shapes

Storefront controls are nearly square, with small softened corners. Merchant panels use a larger radius; merchant buttons and inputs use compact rounded corners. Circular forms are limited to swatches, counts, and selected icon treatments. Product photography remains rectangular and uncropped by decorative masks.

## Components

- **Buttons:** solid near-black primary actions, bordered white secondary actions, underlined text links, and consistent stroke SVG icons. Hover, focus, and disabled treatments are implemented.
- **Forms:** explicit labels above white bordered controls, nearby helper text, native checkboxes and radios, and visible validation/notification text. Loading forms disable their submit control.
- **Catalog items:** image first, then product title, material/key feature, taka price, and small option swatches. A circular plus affordance links to product selection.
- **Gallery:** thumbnail selection updates the main photograph. A native dialog enlarges it and supports close, previous/next controls, and keyboard navigation.
- **Slideshow:** image/copy slides, previous/next controls, selected dots, and pause/play. Automatic movement pauses on hover/focus and respects reduced motion.
- **Merchant tables:** compact headings, thumbnail-and-name product cells, monochrome badges, filters, and pagination. Empty states describe the actual absence of records.
- **Motion:** short control-state transitions, a restrained product-image zoom, and a slide fade. A reduced-motion rule disables transitions and animation.

## Do's and Don'ts

- Do keep main backgrounds white and UI controls monochrome.
- Do use Manrope for headings and Inter for readable interface content.
- Do distinguish merchant task density from storefront browsing space.
- Do use the locally stored photographs and fonts; images are illustrative demo assets.
- Do preserve explicit text for payment, fulfillment, stock, and validation states.
- Don't add colored badges, gradients, or decorative graphics to the UI.
- Don't claim visual, contrast, responsive, or live MySQL approval from source inspection alone.

## Settings, footer, and interaction updates

The merchant main column is a full-height flex layout: `.admin-content` grows to fill short pages and `.admin-footer` stays in normal flow after long content. The footer is not a fixed overlay.

Settings use monochrome tabs for General, Delivery & tax, Payments, Storefront, and Security. The active tab has a black fill; keyboard focus is visible. Panels use independent forms, URL fragments preserve selection, and the no-JavaScript fallback exposes all forms.

Password inputs receive shared Show/Hide buttons with labels, controlled-input IDs, and pressed-state attributes. The original input name, label, validation, and autocomplete remain intact.

Motion is limited to 140–200 ms state feedback, a clipped search reveal, a restrained gallery opening, and short native cross-document fades where supported. Content is visible without animation. Reduced-motion preferences suppress movement; no scroll-triggered content hiding or staggered page entrances are introduced. Source and isolated backend checks were available; live browser layout and interaction verification remain unavailable in this environment.

## Catalog and homepage additions
The homepage now includes four new arrivals, a three-product electronics selection, and practical FAQ disclosures populated from store settings. These inherit the existing section gutters, Manrope headings, Inter interface copy, and rectangular product photography; no new visual identity or imagery is introduced. On phones, the electronics row scrolls horizontally.

Saved-product hearts sit at the upper-right of product photographs, with a filled heart for saved state and a matching header entry. The saved page reuses the catalog grid and provides an explicit empty state; its copy explains browser-local storage. Catalog price/stock/sale filters are grouped in a native disclosure and remain regular GET forms. Buy again appears beside invoices in customer order history. The merchant Orders heading provides a bordered Export CSV action using the existing button system.

The new surfaces retain reduced-motion behavior and visible controls. PHP route/action checks use an isolated SQLite adapter; browser storage, rendering, and native MySQL execution remain outside the verified scope.

## Product reviews
Product pages include a monochrome customer-review section with a real aggregate score, five rating-distribution rows, paginated feedback, and a signed-in writing/editing form. Empty products show “No reviews yet.” Catalog cards display a compact star/count link only when published reviews exist. Stars are authored SVG geometry using the existing stroke system; filled stars and distribution bars remain black.

Public feedback shows a first name and last initial, date, rating, title, plaintext body, and server-derived verified-purchase badge when applicable. The writing form uses standard labeled controls. It explains moderation and name visibility and offers withdrawal for the author’s existing review.

The admin navigation adds Reviews. Its compact table includes product/customer context, an excerpt with a full-text disclosure, publication status, and moderation actions. These follow the existing button, badge, table, and pagination styles. Sidebar overflow can scroll on smaller screens. Review-specific columns stack at the existing mobile breakpoint; no new identity, accent color, or decorative motion is introduced. Source and isolated CLI checks were available; live-browser visual approval is not claimed.

## Comparison and set planning
The storefront navigation adds Build a set. Catalog headings link to set planning, comparison, and recently viewed products. Compare controls sit below card details and beside the product save action. A white floating tray with a restrained overlay shadow and reserved footer clearance holds up to three selected thumbnails. Comparison uses a horizontally scrollable table with a sticky label column and an optional differences-only view.

The set builder uses a list of rectangular product images and native selection/variant/quantity controls beside a sticky pale-gray summary. The main background stays white. The summary stacks above the list at 900px; a mobile bottom bar keeps total and submission visible with reserved page clearance. Budget and delivery progress use monochrome tracks. Homepage and product history strips reuse the product grid. The homepage set invitation uses thin separators and the existing heading/button system.

Gallery changes preload the next image before a 180ms crossfade. Comparison-tray entry uses 200ms opacity/translation; subtotal changes use 160ms opacity feedback. Reduced-motion preferences bypass these effects, and essential content stays visible. The isolated suite passes 177 checks; no live browser, contrast, or responsive-render approval is claimed.
