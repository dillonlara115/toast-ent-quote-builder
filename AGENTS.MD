# Toast Entertainment Quote Builder — Agent Guide

## Quick Overview
- WordPress plugin providing a quote builder via the `toast_quote_builder` shortcode.
- Frontend uses Alpine.js + Flowbite styles; data comes from `includes/data.php`.
- AJAX submission handled server-side by `teqb_Quote_Builder::handle_quote_submission` hitting `admin-ajax.php`.

## Boot Sequence
1. `loader.php` instantiates `teqb_Loader`, which creates `teqb_Plugin`.
2. `teqb_Plugin::init()` instantiates `teqb_Quote_Builder`.
3. `teqb_Quote_Builder` registers the shortcode, AJAX handlers, and enqueues assets.
4. The shortcode renders `templates/quote-builder-template.php`, passing location data into Alpine via `x-init="initData(...)"`.

## File Map
- `classes/base.php` – Shared helpers for prefixed options.
- `classes/setup.php` – Activation/deactivation scaffolding.
- `classes/plugin.php` – Core plugin bootstrap; loads the quote builder.
- `classes/quote-builder.php` – Shortcode rendering, asset enqueue, AJAX handler.
- `includes/data.php` – Hardcoded location/package/add-on pricing data.
- `templates/quote-builder-template.php` – Alpine-powered multi-step form.
- `assets/js/quote-builder.js` – Registers `Alpine.data('quoteBuilder', …)` and exposes `window.quoteBuilder`.
- `assets/css/quote-builder.css` – Styling (not inspected by default; leverage Flowbite).

## Frontend Notes
- Alpine component is registered immediately and on `alpine:init`; ensure scripts remain enqueued in the header (see `enqueue_assets()`).
- Global config for AJAX/nonce is `window.quoteBuilderConfig`.
- Template assumes `quoteData.data.packages` et al. are defined; JS now seeds safe defaults.
- If adding new Alpine functionality, keep in mind caching can serve stale JS—advise hard refresh or cache busting.

## AJAX Flow
- Client posts to `quoteBuilderConfig.ajaxUrl` (`admin-ajax.php`) with action `submit_quote`.
- Nonce key: `quote_builder_nonce`; created in `enqueue_assets()` and sent as `nonce`.
- Response payloads currently just send success/error messages; expand via `wp_send_json_success()` / `wp_send_json_error()` as needed.

## Extending Data
- To add locations or packages, update `includes/data.php`.
- For dynamic data, consider replacing `includes/data.php` with options or CPTs; wrap `require` calls in safeguards.
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

