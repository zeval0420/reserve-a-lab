// main.js
//
// Responsibility: orchestration and loading/error UI only. Waits for
// pdf-renderer.js to finish rasterizing book.pdf, then builds the book
// element, initializes the flip engine and controls, and swaps out the
// loading state. Contains no PDF-parsing or page-flip-engine logic
// itself — that lives in pdf-renderer.js / flipbook-controller.js.

(function () {
  function getStage() {
    return document.getElementById('mf-stage');
  }

  function showError(error) {
    // Never expose the raw JS error to the user — log it for developers,
    // show a plain-language message in the UI.
    console.error('Flipbook: failed to load book.pdf', error);

    const stage = getStage();
    if (!stage) return;

    stage.innerHTML = `
      <div class="magazine-flipbook__error" role="alert">
        <p class="magazine-flipbook__error-title">Unable to load the magazine.</p>
        <p class="magazine-flipbook__error-detail">Please try again.</p>
      </div>
    `;
  }

  function mountBook(images, pageCount) {
    const stage = getStage();
    if (!stage) {
      console.error('Flipbook: #mf-stage element not found.');
      return;
    }

    if (typeof St === 'undefined' || !St.PageFlip) {
      showError(new Error('page-flip library failed to load from CDN.'));
      return;
    }

    // Replace the loading markup with a fresh book container.
    stage.innerHTML = '';
    const bookEl = document.createElement('div');
    bookEl.id = 'mf-book';
    bookEl.className = 'magazine-flipbook__book';
    bookEl.setAttribute('role', 'region');
    bookEl.setAttribute('aria-label', 'Magazine pages');
    stage.appendChild(bookEl);

    window.FlipbookController.init(bookEl, images);
    window.NavigationControls.init();

    if (window.FullscreenControl) {
      window.FullscreenControl.init();
    }

    const indicatorNumber = document.getElementById('mf-indicator-number');
    if (indicatorNumber) indicatorNumber.textContent = `1 / ${pageCount}`;
  }

  document.addEventListener('flipbook:pdfready', (event) => {
    const { images, pageCount } = event.detail;
    mountBook(images, pageCount);
  });

  document.addEventListener('flipbook:pdferror', (event) => {
    showError(event.detail.error);
  });
})();
