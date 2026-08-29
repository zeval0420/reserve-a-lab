// flipbook-controller.js
//
// Responsibility: flipbook engine initialization + navigation API only.
// This is the ONLY file that talks directly to St.PageFlip (page-flip).
// Everything else in the app (UI controls, keyboard handling, download,
// fullscreen) goes through window.FlipbookController instead of touching
// the engine directly.

(function () {
  let pageFlipInstance = null;
  // Keep a reference to the book container so the cover-slot classes can
  // be toggled on it from emitPageChange (page-flip resizes this element,
  // and the CSS shifts it left/right by a quarter of the 2-page spread to
  // seat a standalone cover in its own page's slot).
  let bookContainerEl = null;

  function prefersReducedMotion() {
    return (
      window.matchMedia &&
      window.matchMedia('(prefers-reduced-motion: reduce)').matches
    );
  }

  /**
   * page-flip's min/max width & height bound how far it's allowed to
   * scale a single page. Rather than hardcoding arbitrary pixel bounds,
   * derive them from the PDF's actual page aspect ratio so the book
   * never gets stretched out of proportion at any of the tested
   * viewport sizes (390px phones through 1920px desktops).
   */
  function computeSizeBounds(referenceImage) {
    const ratio = referenceImage.width / referenceImage.height;
    const minWidth = 220;
    const maxWidth = 620;

    return {
      width: referenceImage.width,
      height: referenceImage.height,
      minWidth,
      maxWidth,
      minHeight: Math.round(minWidth / ratio),
      maxHeight: Math.round(maxWidth / ratio)
    };
  }

  function createFlipbook(containerEl, images) {
    if (!images || images.length === 0) {
      throw new Error('FlipbookController.init: no page images provided.');
    }

    bookContainerEl = containerEl;
    const bounds = computeSizeBounds(images[0]);
    const reducedMotion = prefersReducedMotion();

    pageFlipInstance = new St.PageFlip(containerEl, {
      width: bounds.width,
      height: bounds.height,
      size: 'stretch',
      minWidth: bounds.minWidth,
      maxWidth: bounds.maxWidth,
      minHeight: bounds.minHeight,
      maxHeight: bounds.maxHeight,
      showCover: true,
      usePortrait: true,
      // Same physical animation for both drag-release and click/API
      // flips — page-flip uses one flip mechanism for both, we just
      // shorten it when the user has asked for reduced motion.
      flippingTime: reducedMotion ? 200 : 700,
      maxShadowOpacity: 0.5,
      useMouseEvents: true,
      swipeDistance: 30,
      clickEventForward: true,
      disableFlipByClick: false,
      mobileScrollSupport: false
    });

    pageFlipInstance.loadFromImages(images.map((image) => image.dataUrl));

    // page-flip's canvas renderer paints the ENTIRE canvas white on every
    // animation frame (clear(): ctx.fillStyle="white"; fillRect(0,0,w,h)).
    // We now leave vertical headroom around the book so turning-page corners
    // can poke out past the resting rectangle, so that headroom must be
    // transparent — wipe to a clear canvas instead of a white one, letting
    // the wood table show through. This patch runs right after the renderer
    // exists and is picked up on every subsequent drawFrame().
    const renderer = pageFlipInstance.getRender();
    if (renderer && typeof renderer.clear === 'function') {
      renderer.clear = function () {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
      };
    }

    pageFlipInstance.on('flip', (event) => {
      emitPageChange(event.data);
    });

    // Native drag-state feedback: swap the cursor to "grabbing" while the
    // user is actively folding/dragging a page, without any custom drag
    // overlay. "user_fold" and "flipping" both count as active dragging;
    // "read" means the page has settled.
    // Also re-sync the cover clip: on a cover the blank-half trim must only
    // apply at rest, never mid-flip (see syncCoverResting).
    pageFlipInstance.on('changeState', (event) => {
      const state = event.data;
      const isActive = state === 'user_fold' || state === 'flipping';
      containerEl.classList.toggle('is-dragging', isActive);
      syncCoverResting(state);
    });

    emitPageChange(pageFlipInstance.getCurrentPageIndex());
    // page-flip starts in the 'read' state but doesn't emit a changeState on
    // load, so seed the resting cover clip explicitly.
    syncCoverResting('read');

    return pageFlipInstance;
  }

  /**
   * The blank-half trim on a cover (clip-path) is applied ONLY while the
   * book has settled ('read') on that cover. If we clipped while a flip or
   * drag fold is in progress, the 3D-rotating page would be cut off to a
   * flat rectangle instead of visibly sticking out. Toggle the
   * is-cover-resting class to match: on when the current page is a cover
   * (index 0 or last) AND the engine reports 'read' (idle); otherwise off.
   */
  function syncCoverResting(state) {
    if (!bookContainerEl || !pageFlipInstance) return;
    const pageCount = pageFlipInstance.getPageCount();
    const currentPage = pageFlipInstance.getCurrentPageIndex();
    const isCover =
      pageCount > 1 && (currentPage === 0 || currentPage === pageCount - 1);
    bookContainerEl.classList.toggle(
      'is-cover-resting',
      isCover && state === 'read'
    );
  }

  function emitPageChange(currentPageIndex) {
    // Seat a standalone cover properly: page-flip renders the cover on a
    // full (2-page) width box with the art on only one half and the facing
    // half blank, so a cover page looks off-kilter. Tag the book with a
    // cover-slot class so the CSS shifts it a quarter of the spread width
    // (centering the visible single page) and clips the blank half away.
    // Front cover (page 1) shifts left, back cover (last page) shifts right;
    // on any normal spread both classes are removed and the book centers.
    const pageCount = pageFlipInstance.getPageCount();
    if (bookContainerEl) {
      bookContainerEl.classList.toggle(
        'is-cover-left',
        pageCount > 1 && currentPageIndex === 0
      );
      bookContainerEl.classList.toggle(
        'is-cover-right',
        pageCount > 1 && currentPageIndex === pageCount - 1
      );
    }

    document.dispatchEvent(
      new CustomEvent('flipbook:pagechange', {
        detail: {
          currentPage: currentPageIndex,
          pageCount
        }
      })
    );
  }

  window.FlipbookController = {
    init: createFlipbook,
    next: () => {
      // Flip-lock: a single press must produce a single animated turn.
      // page-flip's flip() eagerly finishes any in-flight animation; if a
      // second flip is invoked while one is mid-air it advances a page and
      // starts yet another animation, which reads as "turns twice". Ignore
      // navigation while the book is not idle ('read').
      if (!pageFlipInstance || pageFlipInstance.getState() !== 'read') return;
      pageFlipInstance.flipNext();
    },
    prev: () => {
      if (!pageFlipInstance || pageFlipInstance.getState() !== 'read') return;
      pageFlipInstance.flipPrev();
    },
    goTo: (pageIndex) => pageFlipInstance && pageFlipInstance.flip(pageIndex),
    getCurrentPage: () =>
      pageFlipInstance ? pageFlipInstance.getCurrentPageIndex() : 0,
    getPageCount: () => (pageFlipInstance ? pageFlipInstance.getPageCount() : 0)
  };
})();
