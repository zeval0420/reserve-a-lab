// pdf-renderer.js
//
// Responsibility: PDF loading + rasterization only.
// Loads book.pdf with pdf.js and renders every page to a canvas image at a
// resolution sharp enough for high-DPI screens without producing oversized
// canvases (the book is only 8 pages, so full-quality rendering is cheap).
//
// This file is loaded as <script type="module">, so it can use ES module
// imports directly from the CDN. It has no knowledge of the flip engine —
// it only announces "the pages are ready" via a DOM CustomEvent, which
// keeps PDF rendering decoupled from flipbook initialization.

// NOTE: version-pinned to a specific pdfjs-dist release on purpose (never
// use an unpinned "latest" CDN URL). Verify this is still the version you
// want before deploying, and bump both URLs together if you upgrade.
import * as pdfjsLib from 'https://cdn.jsdelivr.net/npm/pdfjs-dist@4.10.38/build/pdf.min.mjs';

pdfjsLib.GlobalWorkerOptions.workerSrc =
  'https://cdn.jsdelivr.net/npm/pdfjs-dist@4.10.38/build/pdf.worker.min.mjs';

// Cap devicePixelRatio so a 3x-DPR phone doesn't render enormous canvases.
const MAX_DPR = 2;
// Base resolution multiplier — tuned for a small (8-page) magazine where
// visual quality matters more than raw memory savings.
const BASE_SCALE = 1.5;

/**
 * Renders a single PDF page to a canvas and returns it as a data URL
 * along with its pixel dimensions.
 */
async function renderPageToImage(pdfDocument, pageNumber) {
  const page = await pdfDocument.getPage(pageNumber);
  const dpr = Math.min(window.devicePixelRatio || 1, MAX_DPR);
  const viewport = page.getViewport({ scale: BASE_SCALE * dpr });

  const canvas = document.createElement('canvas');
  canvas.width = viewport.width;
  canvas.height = viewport.height;
  const context = canvas.getContext('2d');

  await page.render({ canvasContext: context, viewport }).promise;

  const image = {
    dataUrl: canvas.toDataURL('image/jpeg', 0.92),
    width: viewport.width,
    height: viewport.height
  };

  // Release the canvas memory once we've extracted the data URL.
  canvas.width = 0;
  canvas.height = 0;

  return image;
}

/**
 * Loads the PDF at pdfUrl and renders every page to an image.
 * Returns { images, pageCount }.
 */
export async function renderPdfToImages(pdfUrl) {
  const loadingTask = pdfjsLib.getDocument({
    url: pdfUrl,
    // Defense-in-depth against malicious PDFs, independent of the
    // CVE-2024-4367 fix already present in this pinned pdf.js version.
    isEvalSupported: false
  });

  const pdfDocument = await loadingTask.promise;
  const pageCount = pdfDocument.numPages;

  const images = [];
  for (let pageNumber = 1; pageNumber <= pageCount; pageNumber++) {
    images.push(await renderPageToImage(pdfDocument, pageNumber));
  }

  return { images, pageCount };
}

async function bootstrap() {
  const config = window.FLIPBOOK_CONFIG || {};
  const pdfUrl = config.pdfUrl || 'book.pdf';

  try {
    const { images, pageCount } = await renderPdfToImages(pdfUrl);
    document.dispatchEvent(
      new CustomEvent('flipbook:pdfready', { detail: { images, pageCount } })
    );
  } catch (error) {
    document.dispatchEvent(
      new CustomEvent('flipbook:pdferror', { detail: { error } })
    );
  }
}

bootstrap();
