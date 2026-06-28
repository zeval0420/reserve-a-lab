# Opening of Classes Photobooth

A complete, production-quality, standalone kiosk photobooth web app — built with
plain PHP, HTML, CSS and JavaScript (Canvas API + WebRTC `getUserMedia`).
No database. Everything is stored on the filesystem as JSON + images.

---

## 1. Quick Start

**Requirements:** PHP 7.4+ (tested on 8.3) with the **GD** extension enabled.
A webcam-equipped device with a modern browser (Chrome, Edge, Safari).

```bash
cd photobooth
php -S 0.0.0.0:8000
```

Open **http://localhost:8000/index.php** on the kiosk display (use Chrome's
kiosk mode: `chrome --kiosk http://localhost:8000/index.php`).

Open the **hidden admin Settings page** at `http://localhost:8000/admin/settings.php`
(or tap the small logo on the welcome screen 5 times). Default passcode: **`1234`**
— change it immediately from the Settings → Admin Access section.

The **admin Gallery** lives at `http://localhost:8000/admin/gallery.php`.

> Deploying on Apache instead of the PHP dev server? Point the document root at
> this `photobooth/` folder and make sure `AllowOverride All` is set so the
> bundled `.htaccess` files take effect (they hide directory listings and block
> direct access to `includes/` and `config/`).

---

## 2. Folder Structure

```
photobooth/
├── index.php                 Kiosk app shell (single page, all workflow screens)
├── admin/
│   ├── settings.php          Hidden, passcode-gated settings page
│   └── gallery.php           Admin gallery of past sessions + reprint
├── api/                      JSON endpoints called by the frontend (fetch)
├── includes/                 PHP "backend" classes (one responsibility each)
│   ├── bootstrap.php         Paths, autoloading, app constants
│   ├── helpers.php           Small shared functions + admin-auth gate
│   ├── Settings.php          Reads/writes config/settings.json
│   ├── TemplateManager.php   Scans templates/ on every request
│   ├── SessionManager.php    Creates/reads sessions/<id>/ folders
│   ├── Compositor.php        GD-based final strip.png composition
│   └── PrintManager.php      Shells out to `lp` (CUPS) or simulates
├── templates/                 5 example templates (see below)
├── sessions/                  Created at runtime — one folder per photo session
├── config/
│   ├── settings.default.json  Shipped defaults
│   └── settings.json          Live overrides (admin-editable)
├── assets/
│   ├── css/                   variables / main / components / animations / dark-mode / admin
│   ├── js/                    ES modules — camera, countdown, capture flow, etc.
│   └── sounds/                beep.wav, shutter.wav (synthesized, see tools/)
└── tools/                     One-off asset generator scripts (Python)
```

---

## 3. The Workflow (matches the brief exactly)

```
Welcome → Template Gallery → Camera Preview → 3s Countdown → Capture
   → (repeat x4) → Review (retake any photo) → Generate Strip → Preview
   → Accept → Auto-save (already done) → Auto-print (if enabled) → Welcome
```

Every step is its own `<section class="screen">` in `index.php`; `assets/js/app.js`
is the state machine that shows/hides them and calls the right module at each step.

---

## 4. Templates — fully modular, zero code changes

Drop a new folder into `templates/`:

```
templates/my-new-template/
├── config.json
├── frame.png        (RGBA, same size as output — transparent over photo windows)
└── thumbnail.png    (small gallery preview)
```

`config.json` schema:

```json
{
  "name": "My New Template",
  "description": "Optional short description",
  "thumbnail": "thumbnail.png",
  "frame": "frame.png",
  "background": "#FFFFFF",
  "output": { "width": 600, "height": 1800 },
  "photos": [
    { "x": 40, "y": 90,   "width": 520, "height": 380, "rotation": 0 },
    { "x": 40, "y": 490,  "width": 520, "height": 380, "rotation": 0 },
    { "x": 40, "y": 890,  "width": 520, "height": 380, "rotation": 0 },
    { "x": 40, "y": 1290, "width": 520, "height": 380, "rotation": 0 }
  ]
}
```

`TemplateManager::getAll()` re-scans the directory on every page load — **no code
changes, no restart needed.** Invalid folders (missing files/fields) are skipped
and logged, never crash the app.

5 ready-made templates are included: **Classic Strip, Modern Grid, School
Spirit, Polaroid Fun** (demonstrates the `rotation` field), and **Elegant Gold**.
Regenerate or design new ones with `tools/generate_templates.py` (Python +
Pillow) — it's a convenience generator, not a runtime dependency.

---

## 5. Storage layout (no database, ever)

```
sessions/2026-06-28_14-05-02/
├── raw/photo1.jpg .. photo4.jpg
├── final/strip.png
└── metadata.json    { session_id, template, photos, strip, print_status, ... }
```

`SessionManager` is the only class that touches this structure. The admin
Gallery (`api/sessions_list.php`) simply reads every `metadata.json` on disk.

---

## 6. Printing

`PrintManager` shells out to the system's CUPS `lp` command with the paper
size / copies / margins / scaling from Settings. If no printer is configured,
or `lp` isn't available on the host OS, printing is **simulated** (clearly
logged in `metadata.json`) so the kiosk remains fully testable without
hardware. Windows deployments should swap `PrintManager::buildPrintCommand()`
for a call to a vendor CLI or `SumatraPDF.exe -print-to`.

---

## 7. Settings

All settings live in `config/settings.json` (merged on top of
`config/settings.default.json`), editable from the hidden Settings page:
Camera, Templates, Printing, Countdown, Storage, Audio, Appearance (dark
mode), and Admin passcode. Nothing here ever touches a database.

---

## 8. Architecture notes / extending the app

The codebase is deliberately split by responsibility so new features are
additive, not invasive:

| Want to add...        | Touch only...                                                   |
|------------------------|------------------------------------------------------------------|
| A new template         | `templates/<id>/` — zero code                                   |
| GIF mode                | new `assets/js/gifFlow.js` reusing `CameraController`/`CaptureFlow`, new `api/gif_generate.php` using GD frame capture |
| AI background removal   | a step inside `CaptureFlow.captureOne()` before upload, or a new `api/remove_bg.php` the compositor calls before `Compositor::stampPhoto()` |
| QR code download         | new `api/qr.php` (render a QR pointing at a public session URL) + a button on the Final screen |
| Stickers                  | extend `config.json` with a `stickers` array + a few lines in `Compositor.php` |
| New event theme           | a new template + new CSS variable palette (everything already reads CSS vars) |

Frontend modules (`assets/js/*.js`) are plain ES modules with single
responsibilities (`camera.js`, `countdown.js`, `captureFlow.js`, `review.js`,
`templateGallery.js`, `sessionClient.js`) orchestrated by `app.js`. Backend
classes (`includes/*.php`) mirror that same separation. Both sides talk only
through the small, documented JSON contracts in `api/*.php`.

---

## 9. Security notes for real deployments

- Change the default admin passcode (`1234`) immediately.
- Set `APP_DEBUG = false` in `includes/bootstrap.php` before going live.
- Serve over HTTPS if the kiosk is reachable beyond a local network — webcam
  access (`getUserMedia`) requires a secure context on most browsers anyway
  (localhost is exempt).
- `config/` and `includes/` are blocked from direct HTTP access via the
  bundled `.htaccess` files (Apache only — see deployment note above).
