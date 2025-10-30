# Toast Entertainment Quote Builder — Agent Guide

## Quick Overview
- WordPress plugin providing a quote builder via the `toast_quote_builder` shortcode.
- Frontend uses Alpine.js + Flowbite styles; service/package data lives directly in `assets/js/quote-builder.js` for now.
- AJAX submission handled server-side by `teqb_Quote_Builder::handle_quote_submission` hitting `admin-ajax.php`.

## Boot Sequence
1. `loader.php` instantiates `teqb_Loader`, which creates `teqb_Plugin`.
2. `teqb_Plugin::init()` instantiates `teqb_Quote_Builder`.
3. `teqb_Quote_Builder` registers the shortcode, AJAX handlers, and enqueues assets (with filemtime cache-busting).
4. The shortcode renders `templates/quote-builder-template.php`, which boots Alpine via `x-data="quoteBuilder()"`.

## File Map
- `classes/base.php` – Shared helpers for prefixed options.
- `classes/setup.php` – Activation/deactivation scaffolding.
- `classes/plugin.php` – Core plugin bootstrap; loads the quote builder.
- `classes/quote-builder.php` – Shortcode rendering, asset enqueue, AJAX handler.
- `teqb_quote` CPT – Stores submitted quotes (hidden from menu).
- `teqb_builder` CPT – Admin-visible quote builder configurations with custom capabilities.
- Admin menu groups settings, builder configurations, and entries under the “Quote Builder” top-level item.
- `includes/data.php` – Legacy sample dataset (not currently wired into the builder).
- `templates/quote-builder-template.php` – Alpine-powered multi-step form.
- `assets/js/quote-builder.js` – Registers `Alpine.data('quoteBuilder', …)` and exposes `window.quoteBuilder`.
- `assets/css/quote-builder.css` – Styling (not inspected by default; leverage Flowbite).
- `assets/js/admin-builder.js` – React-powered admin UI for `teqb_builder` posts.
- `assets/css/admin-builder.css` – Styling for the admin builder editor.

## Frontend Notes
- Alpine component is registered immediately and on `alpine:init`; ensure scripts remain enqueued in the header (see `enqueue_assets()`).
- Global config for AJAX/nonce is `window.quoteBuilderConfig`.
- Service data, bundle tiers, and reward catalogs are all declared at the top of `assets/js/quote-builder.js`.
- Bundle discounts are handled by `bundleDiscounts` (flat amounts) plus per-tier reward copy stored in each reward entry.
- Cache busting is handled through file modification times during `enqueue_assets()`; no manual version bump required after edits.

## AJAX Flow
- Client posts to `quoteBuilderConfig.ajaxUrl` (`admin-ajax.php`) with action `submit_quote`.
- Nonce key: `quote_builder_nonce`; created in `enqueue_assets()` and sent as `nonce`.
- Response payloads currently just send success/error messages; expand via `wp_send_json_success()` / `wp_send_json_error()` as needed.

## Extending Data
- Use `[toast_quote_builder builder="slug"]`, `[quote-builder=builder-slug]`, or `[quote builder="slug"]` to render a specific configuration by post slug.
- To add or tweak services/packages, edit the `quoteData` object in `assets/js/quote-builder.js`.
- Bundle tiers live in the `bundleDiscounts` array; reward messaging is driven by `rewardCatalog`.
- `includes/data.php` is available if you want to pivot back to a PHP-provided data source.
- Filters available:
  - `teqb_quote_data` – Modify entire dataset.
  - `teqb_location_data` – Adjust selected location data.
  - `teqb_template_content` – Override rendered markup.
  - `teqb_validate_submission` – Custom validation hook for AJAX submissions.

## Common Tasks
- **Add new field to form:** update `templates/quote-builder-template.php`, extend `formData` + validation in `assets/js/quote-builder.js`, and sanitize/save in `handle_quote_submission()`.
- **Change styling/layout:** adjust `templates/quote-builder-template.php` and `assets/css/quote-builder.css`.
- **Modify script loading:** keep Alpine as dependency; maintain header loading to avoid evaluation before component registration.
- **Localization or i18n:** template currently hardcodes strings; wrap with `__()`/`_e()` when internationalization work begins.

## Testing Tips
- Use WP debug log (`wp_debug_log`) to inspect AJAX handler issues.
- For JS changes, test with browser cache disabled; Alpine errors surface quickly in the console.
- No automated test suite yet; manual walkthrough of all steps recommended after significant changes.

## Future Enhancements (Backlog Ideas)
- Persist quote submissions (email, CRM, or custom post type).
- Admin UI to manage location/package data.
- Server-side rendering for printable quote summary emails.
- Accessibility audit of template markup (keyboard navigation between steps, ARIA attributes).
