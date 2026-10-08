# Imagify DevTools

Diagnostics for Imagify support. Shows the checks behind slow or stuck optimization: blocked requests, memory and timeout, image editor, queue state, and Media Library totals.

## Requirements

- WordPress 6.0 or later
- PHP 8.1 or later
- Imagify installed and active

## Installation

1. Download `imagify-devtools.zip`. Do not unpack it.
2. In WordPress, go to Plugins > Add New > Upload Plugin.
3. Upload the zip, install, and activate.
4. Open **Imagify DevTools** in the admin menu.

Access is limited to users with the `manage_options` capability.

## Triage

The Triage tab shows three checks, in this order.

**Requests**
Tests requests to Imagify (`imagify.io`, `app.imagify.io`) and to the site's own `admin-ajax.php`, `admin-post.php` and `wp-cron.php`. Shows "None blocked", or each blocked request with its HTTP status or error, grouped as:

- External (Imagify API): requests to Imagify's servers.
- Loopback: requests from the site to itself.

A blocked line also shows `Cloudflare: challenge` when Cloudflare produced the response, or `Cloudflare: proxied` when the site is behind Cloudflare. If `WP_HTTP_BLOCK_EXTERNAL` is blocking Imagify, it is named on the External line.

**Memory & timeout**
- Server: PHP `memory_limit`
- WP: `WP_MEMORY_LIMIT`
- WP max: `WP_MAX_MEMORY_LIMIT`
- Timeout: `max_execution_time`

Informational only; never flagged.

**Image editor**
Shows the editor WordPress uses (Imagick or GD). The **Enable GD** checkbox moves Imagick to the end of the editor list so GD is tried first. It appears only when WordPress is using its own Imagick editor and GD is available, or when it is already enabled so it can be turned off.

Below the cards, "N other checks flagged" links to Full diagnostics.

### Optimization pipeline

- **Pending batches**: queued Imagify optimization batches.
- **Background actions (Action Scheduler)**: pending or running Imagify actions.
- **Imagify transients**: Imagify transients in the database.
- **Purge queue**: clears the pending batches and Imagify's job state (locks, scheduler hooks, stale bulk flags). It does not change settings, the API key, or optimized images.

### Buttons

- **Refresh**: clears cached results and runs the checks again. Results are otherwise cached for 30 minutes.
- **Copy report**: copies a plain-text summary: site URL, time (UTC), Imagify version and plan, WordPress and PHP versions, attachments and optimized count, the three cards, and the number of other flagged checks.
- **Download PDF**: opens the browser print dialog. Choose "Save as PDF". The PDF contains Triage and all of Full diagnostics.

## Full diagnostics

Sections:

- WordPress Filesystem
- Imagify Filesystem
- Image Editor Component
- cURL
- Requests Tests
- Media
- Media Library Stats (total, optimized, unoptimized and error counts, space saved, optimized images missing next-gen)
- Various Tests and Values

A section opens automatically when it has a flagged row. Each cached test has a **Clear cache** button.

The Imagify API key, cookies and authorization values are shown as `[redacted]`.

## Live view

Traces the optimization of one image as it runs. The page has no menu entry. Open:

`/wp-admin/admin.php?page=imagify-devtools-live`

Recently active images are listed; select one to see its events (started, each size, done or error). Add `&media=ID` to open a specific image. Events are kept for 15 minutes. A notice appears when the Imagify API key is invalid.

## Media Library

On the edit screen of an image, the **Imagify DevTools** box shows:

- File info
- Formats: original and WebP/AVIF files per size, and recorded vs. actual mime type
- Optimization results
- Other metadata

## Uninstall

Deleting the plugin removes its cached results (`imgdt_` transients) and the `imgdt_prefer_gd` option. Imagify's data is not touched.
