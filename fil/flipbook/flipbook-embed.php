<?php
/**
 * Embed of the magazine flipbook into an existing PHP page.
 *
 * book.pdf is served as a normal static file — PHP never touches or
 * re-streams it. Adjust $pdfUrl to wherever book.pdf actually lives
 * relative to your site's document root.
 */
$pdfUrl = 'book.pdf';
?>

<link rel="stylesheet" href="css/flipbook.css">

<div id="magazine-flipbook" class="magazine-flipbook">

  <div id="mf-stage" class="magazine-flipbook__stage">
    <!-- Minimal loading state. Replaced entirely once book.pdf is
         rendered (see main.js) — never shown alongside the book. -->
    <div class="magazine-flipbook__loading">
      <div class="magazine-flipbook__loading-mark" aria-hidden="true"></div>
      <span>Loading magazine&hellip;</span>
    </div>
  </div>

  <div class="magazine-flipbook__controls">
    <button
      id="mf-prev"
      class="magazine-flipbook__nav-btn"
      type="button"
      aria-label="Previous page"
    >
      <span class="magazine-flipbook__nav-glyph" aria-hidden="true">&lsaquo;</span>
      <span>Previous</span>
    </button>

    <div class="magazine-flipbook__indicator">
      <span id="mf-indicator-number" class="magazine-flipbook__indicator-number" aria-live="polite">
        1 / 8
      </span>
      <span class="magazine-flipbook__indicator-rule" aria-hidden="true"></span>
    </div>

    <button
      id="mf-next"
      class="magazine-flipbook__nav-btn"
      type="button"
      aria-label="Next page"
    >
      <span>Next</span>
      <span class="magazine-flipbook__nav-glyph" aria-hidden="true">&rsaquo;</span>
    </button>

    <a
      id="mf-download"
      class="magazine-flipbook__download"
      href="<?php echo htmlspecialchars($pdfUrl, ENT_QUOTES, 'UTF-8'); ?>"
      download="book.pdf"
      aria-label="Download PDF"
    >
      <svg viewBox="0 0 16 16" fill="none" aria-hidden="true">
        <path d="M8 1.5v8.5m0 0L4.5 6.5M8 10l3.5-3.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M2 12.5v1a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1v-1" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <span>Download PDF</span>
    </a>

    <button
      id="mf-fullscreen"
      class="magazine-flipbook__fullscreen-btn"
      type="button"
      aria-label="View fullscreen"
    >
      <svg viewBox="0 0 16 16" fill="none" aria-hidden="true">
        <path d="M2 5.5V3a1 1 0 0 1 1-1h2.5M14 5.5V3a1 1 0 0 0-1-1h-2.5M2 10.5V13a1 1 0 0 0 1 1h2.5M14 10.5V13a1 1 0 0 1-1 1h-2.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </button>
  </div>

</div>

<script>
  // Read by pdf-renderer.js.
  window.FLIPBOOK_CONFIG = {
    pdfUrl: <?php echo json_encode($pdfUrl); ?>
  };
</script>

<!--
  Script order matters:
  1) page-flip must be defined (window.St) before flipbook-controller.js runs.
  2) flipbook-controller.js, navigation-controls.js and fullscreen-control.js
     must all be defined before main.js registers its event listeners.
  3) pdf-renderer.js is a module, so it's deferred automatically and will
     dispatch flipbook:pdfready only after the above are already loaded.
-->
<script src="https://cdn.jsdelivr.net/npm/page-flip@2.0.7/dist/js/page-flip.browser.js"></script>
<script src="js/flipbook-controller.js"></script>
<script src="js/navigation-controls.js"></script>
<script src="js/fullscreen-control.js"></script>
<script src="js/main.js"></script>
<script type="module" src="js/pdf-renderer.js"></script>
