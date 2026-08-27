// navigation-controls.js
//
// Responsibility: UI controls only (buttons, page indicator, keyboard).
// Never touches St.PageFlip directly — everything goes through
// window.FlipbookController so this file would keep working unchanged
// even if the underlying flip engine were swapped out later.

(function () {
  /**
   * page-flip reports currentPage as a 0-indexed "left page of the
   * visible spread". Convert that into the human-readable folio label:
   * "1 / 8" for a cover, "2–3 / 8" for a spread, "8 / 8" for the back
   * cover.
   */
  function formatIndicator(currentPage, pageCount) {
    const isCoverSpread = currentPage === 0 || currentPage === pageCount - 1;

    if (isCoverSpread) {
      return `${currentPage + 1} / ${pageCount}`;
    }

    const leftPage = currentPage + 1;
    const rightPage = Math.min(leftPage + 1, pageCount);
    return `${leftPage}\u2013${rightPage} / ${pageCount}`;
  }

  function updateIndicator(currentPage, pageCount) {
    const indicatorNumber = document.getElementById('mf-indicator-number');
    if (indicatorNumber) {
      indicatorNumber.textContent = formatIndicator(currentPage, pageCount);
    }
  }

  function updateButtonState(currentPage, pageCount) {
    const prevBtn = document.getElementById('mf-prev');
    const nextBtn = document.getElementById('mf-next');
    if (prevBtn) prevBtn.disabled = currentPage <= 0;
    if (nextBtn) nextBtn.disabled = currentPage >= pageCount - 1;
  }

  function isTypingContext(target) {
    const tagName = (target && target.tagName) || '';
    return (
      tagName.toLowerCase() === 'input' ||
      tagName.toLowerCase() === 'textarea' ||
      Boolean(target && target.isContentEditable)
    );
  }

  function bindButtons() {
    const prevBtn = document.getElementById('mf-prev');
    const nextBtn = document.getElementById('mf-next');

    if (prevBtn) {
      prevBtn.addEventListener('click', () => window.FlipbookController.prev());
    }
    if (nextBtn) {
      nextBtn.addEventListener('click', () => window.FlipbookController.next());
    }
  }

  function bindKeyboard() {
    document.addEventListener('keydown', (event) => {
      // Don't hijack arrow keys while the user is typing somewhere else
      // on the host PHP page (e.g. a search box outside the flipbook).
      if (isTypingContext(event.target)) return;

      if (event.key === 'ArrowLeft') {
        event.preventDefault();
        window.FlipbookController.prev();
      } else if (event.key === 'ArrowRight') {
        event.preventDefault();
        window.FlipbookController.next();
      }
    });
  }

  function bindPageChangeSync() {
    document.addEventListener('flipbook:pagechange', (event) => {
      const { currentPage, pageCount } = event.detail;
      updateIndicator(currentPage, pageCount);
      updateButtonState(currentPage, pageCount);
    });
  }

  function initNavigationControls() {
    bindButtons();
    bindKeyboard();
    bindPageChangeSync();
  }

  window.NavigationControls = { init: initNavigationControls };
})();
