# Performance plan: Reserve-a-Lab

Branch: `perf/lighthouse-optimization`. Nothing is committed until the owner reviews it.
Goal: raise Lighthouse performance on every page, mobile and desktop, without changing behaviour or look.

## Rules for every task

1. **Do not change behaviour.** Every page must pass the smoke checks (`smoke.mjs`) with no new console errors, page errors or failed requests.
2. **Do not change the look.** The screenshots of every page must match the baseline (`diff.mjs`). Accept a change only when it is explained (for example, a font now loads earlier).
3. **jQuery, Bootstrap JS, DataTables and bootstrap-multiselect stay classic, blocking scripts** loaded before the first inline script that uses them. The pages have many inline `$(...)` calls during parsing; `defer` or `async` on these would break them.
4. **Keep library versions as they are now.** Self-host the exact version the page already runs. Quagga is loaded twice today (1.2.6, then the unversioned latest, which wins); keep the version that actually runs.
5. **Do not rename, move or delete images that the database or the gallery code points to.** Originals stay at their paths; they are recompressed in place, keeping format and name.
6. **Own only your files** (see the table). If you need a change in another owner's file, write it in your report; do not edit it.
7. **No commits, no branch switches, no `git stash`, no `git checkout --`.**
8. Do not run the full Lighthouse suite (it skews timing while others work). Use `smoke.mjs` and single-page Lighthouse runs. The orchestrator runs the final measurements.

## Contracts between tasks

### C1. Page asset opt-in (`$pageAssets`)

A page sets `$pageAssets` before `include('helperFiles/headData.php')`:

```php
<?php $pageAssets = ['datatables', 'multiselect']; ?>
<?php include('helperFiles/headData.php'); ?>
```

| Key | Loads |
| --- | --- |
| `datatables` | DataTables 1.13.6 core + Bootstrap 3 integration + Responsive 2.5.0 + Select 1.6.2 (CSS and JS) |
| `multiselect` | bootstrap-multiselect 0.9.13 (CSS and JS) |
| `barcode` | Quagga2 (the version that runs today) |

Always loaded: jQuery 3.3.1, Bootstrap 3.3.7 CSS + JS, Bootstrap Icons, the Raleway font, `universal.css`, `header.css`, `footer.css`.
If `$pageAssets` is **not set**, `headData.php` loads everything, as today. That keeps any page nobody updates working.

Usage today (from code search):

| Page | Keys |
| --- | --- |
| about.php, admin_home.php, requester_home.php, supervisor_approve.php, reset_password.php | none (`[]`) |
| forms.php | `multiselect` |
| requests.php | `datatables` |
| admin_approve.php | `datatables`, `multiselect` |
| inventory.php | `datatables`, `barcode` |

JsBarcode is loaded today but no page calls it: drop it.

### C2. Modern image formats (WebP mirror)

For an original `img/<rel>` (jpg, jpeg or png), the optimiser writes:

- `img/_opt/<rel>.webp`: same size as the (recompressed) original, WebP.
- `img/_opt/<rel>.640w.webp`: 640 px wide WebP, only when the original is wider than 640 px.

`.htaccess` serves `img/_opt/<rel>.webp` in place of `img/<rel>` when the browser's `Accept` header includes `image/webp` and the mirror file exists, with `Vary: Accept`. URLs, database paths and gallery listings do not change. The mirror sits outside the gallery folders, so gallery listings never show it.

Every PHP code path that writes, replaces, renames or deletes a lab image must keep the mirror in sync, through `helperFiles/image_optimizer.php`.

### C3. Self-hosted files

- Vendor libraries: `assets/vendor/<name>@<version>/...` (or the existing `bootstrap3.3.7/` and `jQuery-3.3.1/` folders when they hold the same version).
- Fonts: `assets/fonts/<family>/...woff2`, with `font-display: swap`.
- Static files with a `?v=` query string are cached for 1 year (`immutable`). Other static files get a shorter cache (see Task D).

## Tasks and owners

| Task | Owner (subagent) | Files it may edit |
| --- | --- | --- |
| A. Images and media | images agent | `img/**`, `tools/optimize_images.php` (new), `helperFiles/image_optimizer.php` (new), `ajax/ajax_admin.php` (image hooks only), `helperFiles/laboratory-card.js`, `css/laboratory-card.css`, `about.php`, `admin_home.php`, `requester_home.php`, `css/home-heading.css`, `css/game-section.css`, `helperFiles/game-section.js` |
| B. Shared head, scripts and fonts | shared-assets agent | `helperFiles/headData.php`, `helperFiles/header.php`, `helperFiles/footer.php`, `helperFiles/calendar.php`, `helperFiles/tables.js`, `helperFiles/HeadFoot.css`, `css/universal.css`, `css/header.css`, `css/footer.css`, `assets/**` (new) |
| C. Other pages | pages agent | `index.php`, `forms.php`, `requests.php`, `inventory.php`, `admin_approve.php`, `supervisor_approve.php`, `reset_password.php`, `assets/fonts/<page fonts>/**` (new, only families these pages use) |
| D. Server caching and compression | server agent | `.htaccess` (new), `C:\xampp\apache\conf\httpd.conf` (back it up first) |
| E. Measure, verify, report | orchestrator | `plan.md`, `PERFORMANCE-REPORT.md`, test accounts |

### Task A: images and media

1. Recompress every image under `img/` in place: longest edge at most 1920 px, JPEG quality 82 progressive, metadata stripped; PNG photos resized the same way and optimised. Keep name and format. Originals are in Git history.
2. Write the WebP mirror and the 640 px thumbnails (contract C2) with `tools/optimize_images.php` (CLI, idempotent: rebuilds a mirror only when it is missing or older than the original).
3. `helperFiles/image_optimizer.php`: GD functions used by upload code: downsize a new upload in place (same rule as step 1), write or refresh its mirror, delete the mirror, rename a mirror folder. Each function must catch its own errors and never stop an upload.
4. Call those functions in every image write path in `ajax/ajax_admin.php`: add laboratory, edit laboratory (image replaced and folder renamed), both gallery upload handlers, gallery delete.
5. Lab cards: `<picture>` with a WebP `srcset` (640w thumbnail + full) and `sizes`, fallback `<img>` to the original. Keep the `lab-card__image` class on the `<img>`. The first visible card image loads eagerly with `fetchpriority="high"`; the rest stay `loading="lazy"`.
6. `about.php`: lazy-load every image below the fold, add `width` and `height` (or `aspect-ratio`) to stop layout shifts, `decoding="async"`. Opt into assets with `$pageAssets = []`.
7. `admin_home.php`, `requester_home.php`: pass the thumbnail and WebP URLs for each lab to the card script; set `$pageAssets = []`; lazy-load gallery images in modals.

### Task B: shared head, scripts and fonts

1. Implement contract C1 in `headData.php`.
2. Self-host jQuery 3.3.1, Bootstrap 3.3.7 (CSS, theme CSS, JS, glyphicon fonts), Bootstrap Icons 1.10.5, and the DataTables, multiselect and Quagga files, at the versions in use. Remove the duplicate Quagga and JsBarcode.
3. Self-host Raleway (latin subset, variable woff2), `font-display: swap`, preload it.
4. Bootstrap Icons: self-host; subset the font only if a code search proves that no icon class is built dynamically.
5. `bootstrap-theme.min.css`: remove only if screenshots of every page show no difference.
6. `calendar.php`: load EventCalendar CSS and JS only when "View Calendar" is first clicked (today they block rendering in the middle of `<body>` on every page). The calendar must open and behave as before.
7. Header and footer logo: `width`/`height` attributes; footer logo `loading="lazy"`.
8. Delete files nothing references (for example `helperFiles/HeadFoot.css`, if a search confirms it).
9. Add `?v=filemtime` to local CSS and JS that lack it, so they can be cached for a long time.

### Task C: other pages

1. Set `$pageAssets` in each page (contract C1 table).
2. `index.php` (login): self-host Inter and Playfair Display (only the weights used), preload the LCP font, add `width`/`height` to the logo, check the LCP element and remove what delays it.
3. `supervisor_approve.php`: self-host Plus Jakarta Sans (weights used).
4. `forms.php`, `reset_password.php`: background images through the C2 mirror happen automatically; check the LCP and preload the hero background if it is the LCP element.
5. Long tasks at load (TBT, INP): look for heavy work at `document.ready` (large DataTables, big DOM loops) and defer non-urgent work (`requestIdleCallback` or after first paint) without changing results. DataTables: consider `deferRender: true` only where data comes from JS or AJAX.
6. Remove dead code inside these pages only when a search proves nothing calls it.

### Task D: server caching and compression

1. Back up `httpd.conf`, then enable `mod_deflate`, `mod_filter`, `mod_expires` and `mod_brotli` (already present in `modules/`). Restart Apache and confirm it starts (`httpd -t` first).
2. `.htaccess` in the project root, every block wrapped in `<IfModule>` so it is safe on a server without the module:
    - Brotli and gzip for HTML, CSS, JS, JSON, SVG, fonts that are not already compressed.
    - Cache: 1 year `immutable` for requests with `?v=`; fonts and `assets/vendor/**` 1 year; images 30 days; HTML and PHP not cached.
    - WebP negotiation (contract C2) with `Vary: Accept`.
    - Block web access to `tools/`.
3. Confirm with `curl -I` that the headers are right for each kind of file and that PHP pages are not cached.

### Task E: measure, verify, report (orchestrator)

1. Baseline: Lighthouse on 9 pages × mobile and desktop, median of 3 runs; Playwright smoke checks, screenshots, and a real throttled load (1.6 Mbps, 150 ms RTT, 4× CPU).
2. After each task: smoke checks and screenshot diff.
3. Final: the same Lighthouse suite and slow-connection run; before and after table in `PERFORMANCE-REPORT.md`.
4. Remove the temporary test accounts (`PERFTEST-A`, `PERFTEST-T`, `PERFTEST-R`).

## Order

1. A, B, C and D run in parallel (their files do not overlap).
2. Orchestrator verifies, fixes integration issues, and re-measures.

## Findings from the baseline that change the tasks

- **Login page, mobile: Lighthouse cannot score it (NO_FCP).** The card and side panel fade in from `opacity: 0`; on mobile the card is the only content, so no contentful paint is recorded. Task C: keep the slide-in but do not start from `opacity: 0`.
- **Lab card LCP:** on both home pages the LCP element is the first lab card photo, which has `loading="lazy"`. Task A: load the first visible cards eagerly with `fetchpriority="high"`.
- **`img/logo.png` is 248 KB** and loads on every page (header, footer, login, favicon, PDFs). Task A: resize and optimise it in place, keeping PNG transparency.
- **`bootstrap-multiselect.min.css` 0.9.13 returns 404 on cdnjs**, so the multiselect has never had its CSS. Task B: drop the dead link and do **not** add the CSS (adding it would change the look).
- **Missing images `img/labimages/lab2.jpg`, `lab3.jpg`, `lab4.jpg`** (404) come from database rows. This is a data problem, out of scope; leave it.
- **About page LCP** is `.hero-bg` (`img/about photos/bg.jpeg`, background image). Task A: preload it.
- **Phone photos carry EXIF orientation.** Any re-encode (ImageMagick or GD) must apply the orientation before stripping metadata, or photos turn sideways.
- `supervisor_approve.php` needs `?id=<request id>`; tests use `?id=48`.

## Baseline (median of 3 Lighthouse runs, Lighthouse 12.8, local XAMPP)

| Page | Mobile perf | Mobile LCP | Desktop perf | Desktop LCP | Page weight |
| --- | --- | --- | --- | --- | --- |
| login | error (NO_FCP) | n/a | 94 | 1.2 s | 425 KB |
| about | 53 | 17.7 s | 83 | 2.6 s | 6,421 KB |
| requester_home | 61 | 8.2 s | 89 | 1.9 s | 1,404 KB |
| forms | 61 | 7.5 s | 91 | 1.6 s | 1,029 KB |
| requests | 64 | 6.5 s | 93 | 1.4 s | 859 KB |
| supervisor_approve | 63 | 6.9 s | 92 | 1.5 s | 901 KB |
| admin_home | 61 | 8.4 s | 92 | 1.7 s | 1,417 KB |
| admin_approve | 62 | 6.9 s | 93 | 1.4 s | 902 KB |
| inventory | 62 | 7.2 s | 92 | 1.5 s | 1,008 KB |

## Result

All tasks are done; the full before and after comparison is in `performance-report/index.html`. Average mobile Lighthouse score on the 8 pages scorable both times: 61 → 86; login mobile: error → 100; every desktop page 99–100. The temporary test accounts are deleted.

Top Lighthouse findings at baseline, on every logged-in page: render-blocking resources (about 3.3 s on mobile), no text compression, no cache headers, unused JavaScript (150 to 200 KB), and images not sized or in a modern format.
