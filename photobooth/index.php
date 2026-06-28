<?php
/**
 * index.php
 * ------------------------------------------------------------------
 * The kiosk entry point. This file only renders the page shell — every
 * screen in the workflow (welcome -> template gallery -> camera ->
 * review -> final preview -> done) lives as a <section class="screen">
 * below, and assets/js/app.js shows/hides them as the user progresses.
 * All real logic (camera access, compositing, saving, printing) is
 * handled by the JS modules + the PHP api/*.php endpoints; this file
 * stays a thin, readable shell on purpose.
 * ------------------------------------------------------------------
 */
require_once __DIR__ . '/includes/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title>Opening of Classes Photobooth</title>

<link rel="stylesheet" href="assets/css/variables.css">
<link rel="stylesheet" href="assets/css/main.css">
<link rel="stylesheet" href="assets/css/components.css">
<link rel="stylesheet" href="assets/css/animations.css">
<link rel="stylesheet" href="assets/css/dark-mode.css">
</head>
<body>

<div id="app">

  <!-- ============================================================ -->
  <!-- SCREEN 1 — Welcome                                            -->
  <!-- ============================================================ -->
  <section id="screen-welcome" class="screen screen--active">
    <div class="topbar">
      <div class="brand"><span class="brand__dot"></span> Opening of Classes Photobooth</div>
      <button class="theme-toggle" id="theme-toggle" title="Toggle dark mode">🌓</button>
    </div>
    <div class="welcome">
      <div class="welcome__glow"></div>
      <span class="welcome__badge">SCHOOL YEAR 2026</span>
      <h1 class="welcome__title">Welcome! Let's take<br>your photo strip.</h1>
      <p class="welcome__subtitle">Pick a fun frame, strike a pose four times, and walk away with a printed keepsake from Opening of Classes.</p>
      <button class="btn btn--primary btn--xl welcome__cta" id="btn-start">Tap to Start</button>
      <div class="welcome__footer">Hidden admin access: tap the logo 5 times</div>
    </div>
  </section>

  <!-- ============================================================ -->
  <!-- SCREEN 2 — Template gallery                                   -->
  <!-- ============================================================ -->
  <section id="screen-gallery" class="screen">
    <div class="topbar">
      <button class="btn btn--ghost" id="btn-gallery-back">← Back</button>
      <h2>Choose a Frame</h2>
      <span style="width:90px"></span>
    </div>
    <div class="screen__inner">
      <div class="scroll-region">
        <div class="template-grid" id="template-grid"></div>
      </div>
      <button class="btn btn--primary btn--xl btn--block" id="btn-gallery-continue">Continue</button>
    </div>
  </section>

  <!-- ============================================================ -->
  <!-- SCREEN 3 — Camera / capture                                   -->
  <!-- ============================================================ -->
  <section id="screen-camera" class="screen">
    <div class="topbar">
      <button class="btn btn--ghost" id="btn-camera-cancel">Cancel</button>
      <h2 id="camera-title">Get Ready — Photo 1 of 4</h2>
      <span style="width:90px"></span>
    </div>
    <div class="screen__inner">
      <div class="camera-stage" id="camera-stage">
        <video id="camera-video" autoplay playsinline muted></video>
        <img id="camera-frame-overlay" class="camera-stage__frame-overlay" alt="">
        <div class="camera-stage__slot-highlight" id="camera-slot-highlight" style="opacity:0"></div>
        <div class="camera-stage__flash" id="camera-flash"></div>
        <div class="camera-stage__countdown">
          <div class="camera-stage__countdown-number" id="countdown-number"></div>
        </div>
        <div class="camera-stage__progress" id="camera-progress"></div>
      </div>
      <div class="camera-controls">
        <button class="btn btn--primary btn--xl" id="btn-camera-start">Start Capturing</button>
      </div>
    </div>
  </section>

  <!-- ============================================================ -->
  <!-- SCREEN 4 — Review + retake                                    -->
  <!-- ============================================================ -->
  <section id="screen-review" class="screen">
    <div class="topbar">
      <button class="btn btn--ghost" id="btn-review-restart">Start Over</button>
      <h2>Review Your Photos</h2>
      <span style="width:120px"></span>
    </div>
    <div class="screen__inner">
      <p class="muted center-col" style="margin-bottom:16px">Not happy with one? Tap ↻ on that photo to retake just that shot.</p>
      <div class="scroll-region">
        <div class="review-grid" id="review-grid"></div>
      </div>
      <button class="btn btn--primary btn--xl btn--block" id="btn-review-continue">Looks Great — Continue</button>
    </div>
  </section>

  <!-- ============================================================ -->
  <!-- SCREEN 5 — Final strip preview                                -->
  <!-- ============================================================ -->
  <section id="screen-final" class="screen">
    <div class="topbar">
      <button class="btn btn--ghost" id="btn-final-back">← Back</button>
      <h2>Your Strip is Ready!</h2>
      <span style="width:90px"></span>
    </div>
    <div class="screen__inner">
      <div class="final-preview">
        <img class="final-preview__strip" id="final-strip-img" alt="Your final photo strip">
        <div class="final-preview__actions">
          <button class="btn btn--primary btn--xl" id="btn-final-accept">Accept &amp; Finish</button>
          <button class="btn btn--secondary btn--xl" id="btn-final-redo">Retake Everything</button>
        </div>
      </div>
    </div>
  </section>

  <!-- ============================================================ -->
  <!-- SCREEN 6 — Done                                                -->
  <!-- ============================================================ -->
  <section id="screen-done" class="screen">
    <div class="welcome">
      <div class="done-check">✓</div>
      <h1 class="welcome__title" style="margin-top:24px">All set!</h1>
      <p class="welcome__subtitle" id="done-message">Your strip has been saved.</p>
      <p class="faint" id="done-countdown" style="margin-top:24px">Returning to start in 5s…</p>
    </div>
  </section>

</div>

<div id="toast-stack"></div>

<!-- Hidden audio elements; sources are set dynamically from Settings -->
<audio id="audio-countdown" preload="auto"></audio>
<audio id="audio-shutter" preload="auto"></audio>

<script type="module" src="assets/js/app.js"></script>
</body>
</html>
