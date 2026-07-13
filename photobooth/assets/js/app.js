/**
 * assets/js/app.js
 * ------------------------------------------------------------------
 * The conductor. Owns the screen state machine and wires together the
 * single-responsibility modules (CameraController, TemplateGallery,
 * CaptureFlow, ReviewGrid, SessionClient) without containing much
 * logic of its own. If you're adding a new feature (GIF mode, stickers,
 * QR download…) it almost certainly belongs in its own module, with
 * just a few new lines here to slot it into the flow.
 * ------------------------------------------------------------------
 */
import { $, el, toast, wait } from './utils.js';
import { CameraController } from './camera.js';
import { TemplateGallery } from './templateGallery.js';
import { CaptureFlow } from './captureFlow.js';
import { ReviewGrid } from './review.js';
import { SessionClient } from './sessionClient.js';

class PhotoboothApp {
  constructor() {
    this.screens = {
      gallery: $('#screen-gallery'),
      camera: $('#screen-camera'),
      review: $('#screen-review'),
      final: $('#screen-final'),
      done: $('#screen-done'),
    };

    this.config = null;       // from api/public_config.php
    this.sessionId = null;
    this.template = null;
    this.photoDataUrls = [];
    this.idleTimer = null;
    this.logoTapCount = 0;
    this.logoTapTimer = null;

    this.camera = new CameraController($('#camera-video'));
    this.gallery = new TemplateGallery($('#template-grid'));
    this.reviewGrid = new ReviewGrid($('#review-grid'));
  }

  async init() {
    try {
      const { config } = await SessionClient.getPublicConfig();
      this.config = config;
    } catch (e) {
      this.config = {
        countdown_seconds: 3, countdown_play_sound: true, mirror_preview: true,
        countdown_sound: 'assets/sounds/beep.wav', shutter_sound: 'assets/sounds/shutter.wav',
        volume: 0.8, idle_return_seconds: 300,
      };
      console.warn('Falling back to default config:', e.message);
    }

    $('#audio-countdown').src = this.config.countdown_sound;
    $('#audio-shutter').src = this.config.shutter_sound;
    $('#audio-countdown').volume = this.config.volume;
    $('#audio-shutter').volume = this.config.volume;

    this.captureFlow = new CaptureFlow({
      camera: this.camera,
      stageEl: $('#camera-stage'),
      previewEl: $('#camera-template-preview'),
      frameImgEl: $('#camera-frame-overlay'),
      highlightEl: $('#camera-slot-highlight'),
      flashEl: $('#camera-flash'),
      countdownNumberEl: $('#countdown-number'),
      shutterAudioEl: $('#audio-shutter'),
      countdownAudioEl: $('#audio-countdown'),
    });
    this.captureFlow.countdown.playSound = this.config.countdown_play_sound;

    this.reviewGrid.onRetake = (slotNumber) => this.retakePhoto(slotNumber);

    this.bindEvents();
    this.resetIdleTimer();
    this.goToGallery();
  }

  /* ---------------------------------------------------------------- */
  /* Screen switching                                                   */
  /* ---------------------------------------------------------------- */
  showScreen(name) {
    Object.entries(this.screens).forEach(([key, node]) => {
      node.classList.toggle('screen--active', key === name);
    });
  }

  /* ---------------------------------------------------------------- */
  /* Idle timeout -> auto-return to gallery                            */
  /* ---------------------------------------------------------------- */
  resetIdleTimer() {
    clearTimeout(this.idleTimer);
    if (this.screens.done.classList.contains('screen--active')) return;
    const seconds = this.config?.idle_return_seconds || 300;
    this.idleTimer = setTimeout(() => {
      toast('Session timed out — returning to start.');
      this.hardReset();
    }, seconds * 1000);
  }

  bindEvents() {
    document.addEventListener('pointerdown', () => this.resetIdleTimer());

    // Hidden admin entrance: tap the brand/logo 5 times within 2s.
    $('#brand-logo').addEventListener('click', () => {
      this.logoTapCount++;
      clearTimeout(this.logoTapTimer);
      this.logoTapTimer = setTimeout(() => (this.logoTapCount = 0), 2000);
      if (this.logoTapCount >= 5) {
        window.location.href = 'admin/settings.php';
      }
    });

    $('#btn-gallery-continue').addEventListener('click', () => this.startCameraScreen());

    $('#btn-camera-cancel').addEventListener('click', () => this.hardReset());
    $('#btn-camera-start').addEventListener('click', () => this.runFullCapture());

    $('#btn-review-restart').addEventListener('click', () => this.hardReset());
    $('#btn-review-continue').addEventListener('click', () => this.goToFinal());

    $('#btn-final-back').addEventListener('click', () => this.showScreen('review'));
    $('#btn-final-redo').addEventListener('click', () => this.startCameraScreen());
    $('#btn-final-accept').addEventListener('click', () => this.acceptAndFinish());
  }

  /* ---------------------------------------------------------------- */
  /* Step: Load + show Gallery                                          */
  /* ---------------------------------------------------------------- */
  async goToGallery() {
    this.showScreen('gallery');
    try {
      await this.gallery.load();
    } catch (e) {
      toast('Could not load templates: ' + e.message, 'error');
    }
  }

  /* ---------------------------------------------------------------- */
  /* Step: Gallery -> Camera (creates the session, starts webcam)      */
  /* ---------------------------------------------------------------- */
  async startCameraScreen() {
    const selected = this.gallery.getSelected();
    if (!selected) {
      toast('Please choose a frame first.', 'error');
      return;
    }
    this.template = selected;

    try {
      const session = await SessionClient.createSession(selected.id);
      this.sessionId = session.session_id;
    } catch (e) {
      toast('Could not start a session: ' + e.message, 'error');
      return;
    }

    this.showScreen('camera');
    this.captureFlow.setTemplate(this.template);
    this.camera.setMirrored(this.config.mirror_preview);
    this.renderProgressDots(0);
    $('#camera-title').textContent = `Get Ready — Photo 1 of ${this.template.photos.length}`;
    $('#btn-camera-start').disabled = false;
    $('#btn-camera-start').textContent = 'Start Capturing';

    try {
      await this.camera.start({
        width: { min: this.template.photos[0].width, ideal: 1280 },
        height: { min: this.template.photos[0].height, ideal: 720 },
      });
    } catch (e) {
      toast('Camera access was denied or unavailable: ' + e.message, 'error');
    }
  }

  renderProgressDots(currentIndex) {
    const wrap = $('#camera-progress');
    wrap.innerHTML = '';
    const total = this.template.photos.length;
    for (let i = 0; i < total; i++) {
      const dot = el('span', { class: 'progress-dot' });
      if (i < currentIndex) dot.classList.add('is-done');
      if (i === currentIndex) dot.classList.add('is-current');
      wrap.appendChild(dot);
    }
  }

  /* ---------------------------------------------------------------- */
  /* Step: run the full 4-photo capture sequence                       */
  /* ---------------------------------------------------------------- */
  async runFullCapture() {
    $('#btn-camera-start').disabled = true;
    $('#btn-camera-start').textContent = 'Capturing…';
    this.photoDataUrls = [];

    try {
      this.photoDataUrls = await this.captureFlow.captureAll(
        this.sessionId,
        this.config.countdown_seconds,
        ({ phase, n, total }) => {
          if (phase === 'starting') {
            $('#camera-title').textContent = `Get Ready — Photo ${n} of ${total}`;
            this.renderProgressDots(n - 1);
          }
          if (phase === 'captured') {
            this.renderProgressDots(n);
          }
        }
      );
    } catch (e) {
      toast('Capture failed: ' + e.message, 'error');
      $('#btn-camera-start').disabled = false;
      $('#btn-camera-start').textContent = 'Start Capturing';
      return;
    }

    this.camera.stop();
    this.reviewGrid.render(this.template, this.photoDataUrls);
    this.showScreen('review');
  }

  /* ---------------------------------------------------------------- */
  /* Retake a single photo from the Review screen                      */
  /* ---------------------------------------------------------------- */
  async retakePhoto(slotNumber) {
    this.showScreen('camera');
    $('#camera-title').textContent = `Retaking Photo ${slotNumber}`;
    this.renderProgressDots(slotNumber - 1);

    try {
      await this.camera.start({
        width: { min: this.template.photos[0].width, ideal: 1280 },
        height: { min: this.template.photos[0].height, ideal: 720 },
      });
      this.camera.setMirrored(this.config.mirror_preview);
      const dataUrl = await this.captureFlow.captureOne(this.sessionId, slotNumber, this.config.countdown_seconds);
      this.photoDataUrls[slotNumber - 1] = dataUrl;
      this.camera.stop();
      this.showScreen('review');
      return dataUrl;
    } catch (e) {
      toast('Retake failed: ' + e.message, 'error');
      this.showScreen('review');
      return null;
    }
  }

  /* ---------------------------------------------------------------- */
  /* Step: Review -> Final (server composites the strip)               */
  /* ---------------------------------------------------------------- */
  async goToFinal() {
    this.showScreen('final');
    $('#final-strip-img').src = '';
    $('#btn-final-accept').disabled = true;
    try {
      const { strip_url } = await SessionClient.generateStrip(this.sessionId);
      $('#final-strip-img').src = strip_url;
      $('#btn-final-accept').disabled = false;
    } catch (e) {
      toast('Could not generate your strip: ' + e.message, 'error');
    }
  }

  /* ---------------------------------------------------------------- */
  /* Step: Final -> Accept (auto-saves + auto-prints) -> Done           */
  /* ---------------------------------------------------------------- */
  async acceptAndFinish() {
    $('#btn-final-accept').disabled = true;
    let printResult = { status: 'skipped' };
    try {
      const res = await SessionClient.acceptStrip(this.sessionId);
      printResult = res.print_result;
    } catch (e) {
      toast('Saved, but printing failed: ' + e.message, 'error');
    }

    const message =
      printResult.status === 'printed' ? 'Your strip has been saved and sent to the printer!' :
      printResult.status === 'simulated' ? 'Your strip has been saved. (Printing is simulated — no printer configured.)' :
      printResult.status === 'failed' ? 'Your strip has been saved, but printing failed.' :
      'Your strip has been saved.';
    $('#done-message').textContent = message;

    this.showScreen('done');
    this.runDoneCountdown(5);
  }

  runDoneCountdown(seconds) {
    let remaining = seconds;
    const label = $('#done-countdown');
    label.textContent = `Returning to start in ${remaining}s…`;
    clearInterval(this._doneInterval);
    this._doneInterval = setInterval(() => {
      remaining--;
      if (remaining <= 0) {
        clearInterval(this._doneInterval);
        this.hardReset();
        return;
      }
      label.textContent = `Returning to start in ${remaining}s…`;
    }, 1000);
  }

  /* ---------------------------------------------------------------- */
  /* Reset everything and go back to Gallery                           */
  /* ---------------------------------------------------------------- */
  hardReset() {
    clearInterval(this._doneInterval);
    this.camera.stop();
    this.sessionId = null;
    this.template = null;
    this.photoDataUrls = [];
    this.showScreen('gallery');
    clearTimeout(this.idleTimer);
  }
}

const app = new PhotoboothApp();
app.init();
