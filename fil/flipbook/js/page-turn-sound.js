// page-turn-sound.js
//
// Responsibility: play a short "paper page flip" sound once per completed
// page turn. It listens for the app-wide 'flipbook:pagechange' CustomEvent
// (dispatched by flipbook-controller.js) and replays a reusable <audio>
// element on each emission.
//
// Notes:
//  - The controller also emits 'flipbook:pagechange' once when the book is
//    first built (an initial render, not a turn). This module skips that
//    first emission so nothing plays on page load.
//  - Flips only ever happen from a user gesture (button click / keyboard /
//    drag), so calling audio.play() satisfies the browser autoplay policy.
//  - A single shared <audio> element is reused, so successive turns just
//    rewind and replay it instead of allocating a new one each time.

(function () {
  var seen = false;

  function getAudio() {
    var el = document.getElementById('mf-page-turn-sound');
    if (!el) {
      el = document.createElement('audio');
      el.id = 'mf-page-turn-sound';
      el.preload = 'auto';
      el.volume = 0.9;
      // Relative path, not root-absolute: the flipbook lives under a
      // subdirectory document root (real URLs are
      // /reserve-a-lab/fil/flipbook/...), so root-absolute /fil/... paths 404.
      el.src = 'audio/page-turn.mp3';
      document.body.appendChild(el);
    }
    return el;
  }

  function playTurnSound() {
    var audio = getAudio();
    var playPromise;
    try {
      audio.currentTime = 0;
      playPromise = audio.play();
    } catch (err) {
      return;
    }
    if (playPromise && typeof playPromise.catch === 'function') {
      playPromise.catch(function () {
        // Autoplay not yet permitted (no gesture yet) or file failed to
        // load — fail silently; the sound is a nice-to-have.
      });
    }
  }

  document.addEventListener('flipbook:pagechange', function () {
    // Skip the very first emission (the initial render at book load).
    if (!seen) {
      seen = true;
      return;
    }
    playTurnSound();
  });

  window.PageTurnSound = { play: playTurnSound };
})();
