<?php
/**
 * index.php
 * ------------------------------------------------------------------
 * The kiosk entry point. This file only renders the page shell — every
 * screen in the workflow (frame gallery -> camera -> review -> final
 * preview -> done) lives as a <section class="screen"> below, and
 * assets/js/app.js shows/hides them as the user progresses.
 * All real logic (camera access, compositing, saving, printing) is
 * handled by the JS modules + the PHP api/*.php endpoints; this file
 * stays a thin, readable shell on purpose.
 *
 * The step indicator in every top bar (.stepper-slot) is filled in by
 * app.js; the gear button opens the passcode-gated kiosk settings
 * (who the finished prints are emailed to, and so on).
 * ------------------------------------------------------------------
 */
require_once __DIR__ . '/includes/bootstrap.php';
$v = time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<meta name="theme-color" content="#0c0b1d">
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 48 48'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0' y1='0' x2='1' y2='1'%3E%3Cstop offset='0' stop-color='%237c5cff'/%3E%3Cstop offset='1' stop-color='%23ff5fa2'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='48' height='48' rx='14' fill='url(%23g)'/%3E%3Ccircle cx='24' cy='24' r='11' fill='none' stroke='%23fff' stroke-width='3'/%3E%3Ccircle cx='24' cy='24' r='4.5' fill='%23fff'/%3E%3C/svg%3E">
<title>Natibari Photobooth</title>

<link rel="preload" href="assets/fonts/plus-jakarta-sans-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="assets/fonts/inter-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="assets/css/variables.css?v=<?= $v ?>">
<link rel="stylesheet" href="assets/css/main.css?v=<?= $v ?>">
<link rel="stylesheet" href="assets/css/components.css?v=<?= $v ?>">
<link rel="stylesheet" href="assets/css/animations.css?v=<?= $v ?>">
</head>
<body class="kiosk">

<!-- Shared brand mark (aperture), reused in every top bar -->
<svg width="0" height="0" style="position:absolute" aria-hidden="true">
  <defs>
    <linearGradient id="brand-grad" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#7c5cff"/><stop offset="1" stop-color="#ff5fa2"/>
    </linearGradient>
    <symbol id="brand-mark" viewBox="0 0 48 48">
      <rect width="48" height="48" rx="14" fill="url(#brand-grad)"/>
      <circle cx="24" cy="24" r="11" fill="none" stroke="#fff" stroke-width="3"/>
      <circle cx="24" cy="24" r="4.5" fill="#fff"/>
      <circle cx="37" cy="11" r="2.4" fill="#fff" opacity=".85"/>
    </symbol>
  </defs>
</svg>

<div id="app">

  <!-- ============================================================ -->
  <!-- STEP 1 — Frame gallery                                        -->
  <!-- ============================================================ -->
  <section id="screen-gallery" class="screen screen--active">
    <div class="topbar">
      <div class="topbar__start">
        <div class="brand" id="brand-logo">
          <svg class="brand__mark"><use href="#brand-mark"/></svg>
          <div class="brand__text">
            <span class="brand__eyebrow">Opening of Classes</span>
            <span class="brand__name">Natibari Photobooth</span>
          </div>
        </div>
      </div>
      <div class="stepper-slot" data-step="1"></div>
      <div class="topbar__end">
        <button class="btn btn--icon" id="btn-print-picker" title="Printer &amp; paper size" aria-label="Printer and paper size">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
        </button>
        <button class="btn btn--icon" id="btn-camera-picker" title="Preview camera" aria-label="Preview and switch camera">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
        </button>
        <button class="btn btn--icon" id="btn-settings" title="Settings" aria-label="Settings">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
        </button>
      </div>
    </div>
    <div class="screen__inner">
      <div class="screen-heading">
        <span class="eyebrow">Step 1 · Choose a look</span>
        <h1>Pick your frame</h1>
        <p>Tap a design you love, then get ready to strike a pose.</p>
      </div>
      <div class="scroll-region">
        <div class="template-grid" id="template-grid"></div>
      </div>
      <div class="cta-bar">
        <div class="cta-bar__info">
          <span class="cta-bar__label">Selected frame</span>
          <span class="cta-bar__name" id="gallery-selected-name">—</span>
        </div>
        <button class="btn btn--primary btn--xl" id="btn-gallery-continue">Continue <span aria-hidden="true">→</span></button>
      </div>
    </div>
  </section>

  <!-- ============================================================ -->
  <!-- STEP 2 — Camera / capture                                     -->
  <!-- ============================================================ -->
  <section id="screen-camera" class="screen">
    <div class="topbar">
      <div class="topbar__start">
        <button class="btn btn--ghost" id="btn-camera-cancel">✕ Cancel</button>
      </div>
      <div class="stepper-slot" data-step="2"></div>
      <div class="topbar__end"></div>
    </div>
    <div class="screen__inner">
      <div class="screen-heading screen-heading--compact">
        <h2 id="camera-title">Get Ready — Photo 1 of 4</h2>
      </div>
      <div class="camera-layout">
        <div class="camera-stage" id="camera-stage">
          <video id="camera-video" autoplay playsinline muted></video>
          <div class="camera-stage__corners"><i></i><i></i><i></i><i></i></div>
          <div class="camera-stage__live">LIVE</div>
          <div class="camera-stage__flash" id="camera-flash"></div>
          <div class="camera-stage__countdown">
            <div class="camera-stage__countdown-number" id="countdown-number"></div>
          </div>
          <div class="camera-stage__progress" id="camera-progress"></div>
        </div>
        <div class="camera-template-preview" id="camera-template-preview">
          <div class="camera-template-preview__captures" id="camera-slot-captures"></div>
          <div class="camera-template-preview__slot-highlight" id="camera-slot-highlight" style="opacity:0"></div>
          <img id="camera-frame-overlay" class="camera-template-preview__frame" alt="">
        </div>
      </div>
      <div class="camera-controls">
        <button class="btn btn--primary btn--xl" id="btn-camera-start">Start Capturing</button>
      </div>
    </div>
  </section>

  <!-- ============================================================ -->
  <!-- STEP 3 — Review + retake                                      -->
  <!-- ============================================================ -->
  <section id="screen-review" class="screen">
    <div class="topbar">
      <div class="topbar__start">
        <button class="btn btn--ghost" id="btn-review-restart">↺ Start Over</button>
      </div>
      <div class="stepper-slot" data-step="3"></div>
      <div class="topbar__end"></div>
    </div>
    <div class="screen__inner">
      <div class="screen-heading screen-heading--compact">
        <h2>Review your photos</h2>
        <p>Not happy with one? Tap ↻ on that photo to retake just that shot.</p>
      </div>
      <div class="review-stage" id="review-grid"></div>
      <div class="screen-actions">
        <button class="btn btn--primary btn--xl" id="btn-review-continue">Looks great — continue <span aria-hidden="true">→</span></button>
      </div>
    </div>
  </section>

  <!-- ============================================================ -->
  <!-- STEP 4 — Final strip preview                                  -->
  <!-- ============================================================ -->
  <section id="screen-final" class="screen">
    <div class="topbar">
      <div class="topbar__start">
        <button class="btn btn--ghost" id="btn-final-back">← Back</button>
      </div>
      <div class="stepper-slot" data-step="4"></div>
      <div class="topbar__end"></div>
    </div>
    <div class="screen__inner">
      <div class="screen-heading screen-heading--compact">
        <h2>Your strip is ready!</h2>
      </div>
      <div class="final-preview">
        <img class="final-preview__strip" id="final-strip-img" alt="Your final photo strip">
        <div class="final-preview__side">
          <div class="final-preview__qr is-hidden" id="final-qr">
            <div class="final-preview__qr-item">
              <div class="final-preview__qr-code" id="qr-strip"></div>
              <div class="final-preview__qr-label">Photo strip</div>
            </div>
            <div class="final-preview__qr-item">
              <div class="final-preview__qr-code" id="qr-raw"></div>
              <div class="final-preview__qr-label">Raw photos (ZIP)</div>
            </div>
            <p class="faint final-preview__qr-note" id="qr-note">Scan with your phone camera to download. Anyone with the link can view the photos.</p>
          </div>
          <div class="final-preview__actions">
            <button class="btn btn--primary btn--xl" id="btn-final-accept">Accept &amp; Finish</button>
            <button class="btn btn--secondary btn--xl" id="btn-final-redo">Retake Everything</button>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ============================================================ -->
  <!-- Done                                                           -->
  <!-- ============================================================ -->
  <section id="screen-done" class="screen">
    <div class="done">
      <div class="done-check">✓</div>
      <h1 class="done__title">All set!</h1>
      <p class="done__message" id="done-message">Your strip has been saved.</p>
      <p class="faint done__countdown" id="done-countdown">Returning to start in 5s…</p>
    </div>
  </section>

</div>

<div id="toast-stack"></div>

<!-- ============================================================ -->
<!-- Camera preview modal                                          -->
<!-- ============================================================ -->
<div class="camera-modal is-hidden" id="camera-modal">
  <div class="camera-modal__backdrop" id="camera-modal-backdrop"></div>
  <div class="camera-modal__dialog">
    <button class="camera-modal__close" id="btn-camera-modal-close" aria-label="Close">&times;</button>
    <div class="camera-modal__video-wrap">
      <video id="camera-modal-video" autoplay playsinline muted></video>
    </div>
    <div class="camera-modal__header">Switch Camera</div>
    <div class="camera-modal__list" id="camera-modal-list"></div>
  </div>
</div>

<!-- ============================================================ -->
<!-- Printer & paper modal                                         -->
<!-- ============================================================ -->
<div class="camera-modal is-hidden" id="print-modal">
  <div class="camera-modal__backdrop" id="print-modal-backdrop"></div>
  <div class="camera-modal__dialog">
    <button class="camera-modal__close" id="btn-print-modal-close" aria-label="Close">&times;</button>
    <div class="camera-modal__header">Printer</div>
    <div class="camera-modal__list" id="print-modal-printers"></div>
    <div class="camera-modal__header print-modal__gap">Paper size</div>
    <div class="print-modal__papers" id="print-modal-papers"></div>
    <p class="faint print-modal__note">The strip is scaled to fit the selected paper. The default is 5R (5 &times; 7 in).</p>
  </div>
</div>

<!-- ============================================================ -->
<!-- Kiosk settings modal (passcode-gated; content built by        -->
<!-- assets/js/kioskSettings.js)                                    -->
<!-- ============================================================ -->
<div class="camera-modal is-hidden" id="settings-modal" role="dialog" aria-modal="true" aria-labelledby="settings-modal-title">
  <div class="camera-modal__backdrop" id="settings-modal-backdrop"></div>
  <div class="camera-modal__dialog camera-modal__dialog--wide">
    <button class="camera-modal__close" id="btn-settings-modal-close" aria-label="Close">&times;</button>
    <div id="settings-modal-body"></div>
  </div>
</div>

<!-- Hidden audio elements; sources are set dynamically from Settings -->
<audio id="audio-countdown" preload="auto"></audio>
<audio id="audio-shutter" preload="auto"></audio>

<script src="assets/js/vendor/qrcode.js"></script>
<script type="module" src="assets/js/app.js?v=<?= $v ?>"></script>
</body>
</html>
