// fullscreen-control.js
//
// Responsibility: optional fullscreen toggle only, using the browser's
// native Fullscreen API. page-flip (the flip engine) has no fullscreen
// feature of its own, so this is genuinely new functionality rather than
// something being reimplemented on top of the library.
//
// This control is optional by design: if the Fullscreen API isn't
// available (older Safari, some embedded webviews), the button simply
// isn't rendered, and the flipbook works normally without it.

(function () {
  function isFullscreenSupported(rootEl) {
    return Boolean(
      rootEl.requestFullscreen || document.exitFullscreen !== undefined
    );
  }

  function isCurrentlyFullscreen() {
    return Boolean(document.fullscreenElement);
  }

  function toggleFullscreen(rootEl) {
    if (isCurrentlyFullscreen()) {
      document.exitFullscreen();
    } else {
      rootEl.requestFullscreen();
    }
  }

  function updateButtonLabel(button) {
    const active = isCurrentlyFullscreen();
    button.setAttribute(
      'aria-label',
      active ? 'Exit fullscreen' : 'View fullscreen'
    );
    button.classList.toggle('is-active', active);
  }

  function initFullscreenControl() {
    const rootEl = document.getElementById('magazine-flipbook');
    const button = document.getElementById('mf-fullscreen');

    if (!rootEl || !button) return;

    if (!isFullscreenSupported(rootEl)) {
      button.remove();
      return;
    }

    button.addEventListener('click', () => toggleFullscreen(rootEl));
    document.addEventListener('fullscreenchange', () =>
      updateButtonLabel(button)
    );
  }

  window.FullscreenControl = { init: initFullscreenControl };
})();
