# Imagify Skipper

☝️ **Test before use, on a staging site first.** ☝️

A single Imagify Helper Plugin that wraps two Imagify filters behind one settings page, so you can configure exclusions without editing PHP:

- `imagify_auto_optimize_attachment` — skip auto-optimization on upload for specific formats.
- `imagify_bulk_optimize_media` — exclude items from Bulk Optimization by format, upload date, or folder/path.

**Author:** Saransh Jain

## Requirements

- [Imagify](https://wordpress.org/plugins/imagify/) active on the site. This plugin does nothing without it.
- WordPress, current version.
- PHP 8.1+.

## Setup

1. Download `imagify-skipper.zip` from this repo (don't unzip it).
2. In wp-admin: **Plugins → Add New → Upload Plugin**, upload the zip, then **Activate**.
3. Go to **Tools → Imagify Skipper**.
4. Check the formats/rules you want, save. Changes take effect immediately — no code editing required.

## What the settings do

- **Skip auto-optimization on upload** — new uploads of the checked formats won't be optimized automatically, but can still be optimized manually or in bulk. JPG/JPEG is a single option, since WordPress always reports uploaded JPEG files under the one canonical MIME type (`image/jpeg`) regardless of the file's extension.
- **Exclude from Bulk Optimization** — by format, by a cutoff date (uploads before that date are skipped), and/or by folder/path fragment (simple substring match against the file's full path, e.g. `/uploads/2019/`). These rules apply **only** to Bulk Optimization on WordPress Media Library items. They do not affect Bulk Restore, "Generate Missing Next-Gen Images", or Custom Folders items. The "X media to optimize" count shown on the Bulk Optimization page is calculated separately and will still include excluded items.

## Reference

Full documentation for these and other Imagify filters, if you want to customize further or write your own: [imagify.io/documentation/imagify-optimization-filter-hooks](https://imagify.io/documentation/imagify-optimization-filter-hooks/). This link, along with a review link and a support link, is also shown at the bottom of the plugin's settings page.

Enjoying Imagify? [Leave a review](https://wordpress.org/support/plugin/imagify/reviews/#new-post). Need help? [Contact support](https://imagify.io/contact/).

## Limitations / what was NOT implemented

- **No custom-folders support for date/path exclusions.** The `imagify_bulk_optimize_media` filter also fires with `context = 'custom-folders'`, but custom-folder files aren't WordPress posts, so the date and path logic (built around `get_the_date()` and `get_attached_file()`) only runs for `context = 'wp'`. Format exclusion still applies to Media Library items only, matching the same scope.
- **No regex/glob path matching.** Path exclusion is a plain substring check (`strpos`), not a pattern language. This matches the plugin's spirit of staying simple and predictable, but means you can't do wildcard folder matching.
- **No network/multisite admin UI.** Settings are per-site. `uninstall.php` clears both the per-site option and (defensively) the site option, but there's no dedicated network-settings screen for multisite.
- **No settings export/import, no REST API, no WP-CLI commands.**
- **No `.pot` translation file included.** Strings are wrapped for translation (text domain `imagify-skipper`) but no compiled `.pot`/`.po`/`.mo` files are shipped — generate one with `wp i18n make-pot` if you need it.
- **No unit tests.**
- **Deactivation doesn't remove filters explicitly** (WordPress already does this automatically on deactivation since the filters are only ever added within a single request's `imagify_loaded` hook — there's no persistent hook state to clean up). Uninstall (delete from Plugins screen) does remove the stored option.

## Support

This is a community Helper Plugin, not an officially maintained Imagify feature — [wp-media/imagify-helpers](https://github.com/wp-media/imagify-helpers) plugins aren't versioned or actively maintained. For help, use [imagify.io/support](https://imagify.io/support/).
