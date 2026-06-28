/**
 * assets/js/review.js
 * ------------------------------------------------------------------
 * Renders the "Review your photos" screen and wires up per-photo
 * retake buttons. Retaking a photo re-runs the countdown/capture flow
 * for just that one slot (via CaptureFlow.captureOne) and refreshes
 * only that card — the other three are untouched.
 * ------------------------------------------------------------------
 */
import { el, $ } from './utils.js';

export class ReviewGrid {
  constructor(containerEl) {
    this.container = containerEl;
    this.onRetake = null; // set by app.js: async (index) => newDataUrl
  }

  render(photoDataUrls) {
    this.container.innerHTML = '';
    photoDataUrls.forEach((dataUrl, i) => {
      const card = this._buildCard(i + 1, dataUrl);
      this.container.appendChild(card);
    });
  }

  _buildCard(slotNumber, dataUrl) {
    const img = el('img', { src: dataUrl, alt: `Photo ${slotNumber}` });
    const card = el('div', { class: 'review-card', dataset: { slot: String(slotNumber) } }, [
      img,
      el('span', { class: 'review-card__label' }, `Photo ${slotNumber}`),
      el('button', {
        class: 'btn btn--secondary btn--icon review-card__retake',
        title: 'Retake this photo',
        onClick: async () => {
          if (!this.onRetake) return;
          card.classList.add('is-retaking');
          try {
            const newDataUrl = await this.onRetake(slotNumber);
            if (newDataUrl) {
              img.src = newDataUrl;
              card.classList.remove('is-fresh');
              void card.offsetWidth;
              card.classList.add('is-fresh');
            }
          } finally {
            card.classList.remove('is-retaking');
          }
        },
      }, '↻'),
    ]);
    return card;
  }
}
