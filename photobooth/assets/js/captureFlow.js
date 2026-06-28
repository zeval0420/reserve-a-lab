/**
 * assets/js/captureFlow.js
 * ------------------------------------------------------------------
 * Orchestrates the actual photo-taking: positions the template's frame
 * overlay + the current slot's highlight box on top of the live video,
 * runs the countdown, fires the flash + shutter sound, grabs the frame,
 * and uploads it. Used both for the initial 4-photo run and for single
 * re-takes triggered from the Review screen.
 * ------------------------------------------------------------------
 */
import { wait } from './utils.js';
import { Countdown } from './countdown.js';
import { SessionClient } from './sessionClient.js';

export class CaptureFlow {
  /**
   * @param {object} deps
   *  camera: CameraController
   *  stageEl: .camera-stage element
   *  frameImgEl: <img> overlay element
   *  highlightEl: slot highlight <div>
   *  flashEl: flash <div>
   *  countdownNumberEl, shutterAudioEl, countdownAudioEl
   */
  constructor(deps) {
    Object.assign(this, deps);
    this.countdown = new Countdown({
      numberEl: this.countdownNumberEl,
      audioEl: this.countdownAudioEl,
      playSound: true,
    });
  }

  /** Set the template currently framing the shot; positions the overlay image. */
  setTemplate(template) {
    this.template = template;
    this.frameImgEl.src = template.frame_url;
    this.frameImgEl.style.aspectRatio = `${template.output.width} / ${template.output.height}`;
  }

  /** Compute where the template's overlay image actually renders inside the stage (object-fit:contain math). */
  _overlayBox() {
    const stageRect = this.stageEl.getBoundingClientRect();
    const imgRatio = this.template.output.width / this.template.output.height;
    const stageRatio = stageRect.width / stageRect.height;
    let w, h;
    if (imgRatio > stageRatio) {
      w = stageRect.width;
      h = w / imgRatio;
    } else {
      h = stageRect.height;
      w = h * imgRatio;
    }
    return {
      x: (stageRect.width - w) / 2,
      y: (stageRect.height - h) / 2,
      w, h,
    };
  }

  /** Move the dashed highlight box onto the slot the user is about to fill. */
  highlightSlot(index) {
    const slot = this.template.photos[index];
    const box = this._overlayBox();
    const scaleX = box.w / this.template.output.width;
    const scaleY = box.h / this.template.output.height;

    this.highlightEl.style.left = `${box.x + slot.x * scaleX}px`;
    this.highlightEl.style.top = `${box.y + slot.y * scaleY}px`;
    this.highlightEl.style.width = `${slot.width * scaleX}px`;
    this.highlightEl.style.height = `${slot.height * scaleY}px`;
    this.highlightEl.style.opacity = '1';
  }

  hideHighlight() {
    this.highlightEl.style.opacity = '0';
  }

  /** Run the countdown, flash, shutter, capture, and upload for ONE slot (1-based index). */
  async captureOne(sessionId, slotNumber, countdownSeconds, onProgress = () => {}) {
    this.highlightSlot(slotNumber - 1);
    await this.countdown.run(countdownSeconds, (n) => onProgress({ phase: 'countdown', n }));

    // Flash + shutter sound fire together at the instant of capture.
    this.flashEl.classList.remove('is-firing');
    this.stageEl.classList.remove('is-capturing');
    void this.flashEl.offsetWidth; // restart animation
    this.flashEl.classList.add('is-firing');
    this.stageEl.classList.add('is-capturing');
    if (this.shutterAudioEl) {
      this.shutterAudioEl.currentTime = 0;
      this.shutterAudioEl.play().catch(() => {});
    }

    const dataUrl = this.camera.captureFrame();
    onProgress({ phase: 'captured', n: slotNumber });

    await SessionClient.savePhoto(sessionId, slotNumber, dataUrl);
    await wait(120); // let the flash visually register before moving on
    this.hideHighlight();

    return dataUrl;
  }

  /** Run all 4 captures back-to-back, calling onProgress for UI updates between each. */
  async captureAll(sessionId, countdownSeconds, onProgress = () => {}) {
    const results = [];
    for (let i = 1; i <= this.template.photos.length; i++) {
      onProgress({ phase: 'starting', n: i, total: this.template.photos.length });
      const dataUrl = await this.captureOne(sessionId, i, countdownSeconds, onProgress);
      results.push(dataUrl);
      if (i < this.template.photos.length) await wait(650); // brief breather between shots
    }
    return results;
  }
}
