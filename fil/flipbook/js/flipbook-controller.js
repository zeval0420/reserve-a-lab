// flipbook-controller.js
//
// Responsibility: flipbook engine initialization + navigation API only.
// This is the ONLY file that talks directly to St.PageFlip (page-flip).
// Everything else in the app (UI controls, keyboard handling, download,
// fullscreen) goes through window.FlipbookController instead of touching
// the engine directly.

(function () {
  let pageFlipInstance = null;

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

    pageFlipInstance.on('flip', (event) => {
      emitPageChange(event.data);
    });

    // Native drag-state feedback: swap the cursor to "grabbing" while the
    // user is actively folding/dragging a page, without any custom drag
    // overlay. "user_fold" and "flipping" both count as active dragging;
    // "read" means the page has settled.
    pageFlipInstance.on('changeState', (event) => {
      const isActive = event.data === 'user_fold' || event.data === 'flipping';
      containerEl.classList.toggle('is-dragging', isActive);
    });

    emitPageChange(pageFlipInstance.getCurrentPageIndex());

    return pageFlipInstance;
  }

  function emitPageChange(currentPageIndex) {
    document.dispatchEvent(
      new CustomEvent('flipbook:pagechange', {
        detail: {
          currentPage: currentPageIndex,
          pageCount: pageFlipInstance.getPageCount()
        }
      })
    );
  }

  window.FlipbookController = {
    init: createFlipbook,
    next: () => pageFlipInstance && pageFlipInstance.flipNext(),
    prev: () => pageFlipInstance && pageFlipInstance.flipPrev(),
    goTo: (pageIndex) => pageFlipInstance && pageFlipInstance.flip(pageIndex),
    getCurrentPage: () =>
      pageFlipInstance ? pageFlipInstance.getCurrentPageIndex() : 0,
    getPageCount: () => (pageFlipInstance ? pageFlipInstance.getPageCount() : 0)
  };
})();
