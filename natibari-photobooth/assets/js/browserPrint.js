/**
 * assets/js/browserPrint.js
 * ------------------------------------------------------------------
 * Prints an image from the kiosk browser. Used when the app is hosted
 * away from the printer (the server answers with status "browser"),
 * so the server can't reach the printer plugged into the kiosk.
 *
 * Run the kiosk as `chrome --kiosk --kiosk-printing <url>` to skip the
 * print dialog and print straight to the kiosk's default printer.
 * ------------------------------------------------------------------
 */
const PAPER_IN = {
  '5R': [5, 7], '4x6': [4, 6], '5x7': [5, 7], '2x6': [2, 6],
  A4: [8.27, 11.69], A5: [5.83, 8.27], A6: [4.13, 5.83], Letter: [8.5, 11],
};

/** Print the image at `url` scaled to fit one page of `paper_size`, `copies` times. */
export async function printImage(url, { paper_size = '5R', copies = 1 } = {}) {
  const img = new Image();
  img.src = url;
  await img.decode(); // rejects if the image can't be loaded

  let [w, h] = PAPER_IN[paper_size] || PAPER_IN['5R'];
  if (img.naturalWidth > img.naturalHeight) [w, h] = [h, w]; // landscape image -> landscape page

  const frame = document.createElement('iframe');
  frame.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0';
  document.body.appendChild(frame);
  const doc = frame.contentDocument;
  doc.open();
  doc.write(`<!doctype html><style>
    @page { size: ${w}in ${h}in; margin: 0; }
    html, body { margin: 0; height: 100%; overflow: hidden; }
    img { display: block; width: 100vw; height: 100vh; object-fit: contain; }
  </style><img src="${img.src}">`);
  doc.close();
  await doc.images[0].decode();

  for (let i = 0; i < Math.max(1, copies); i++) {
    frame.contentWindow.print();
  }
  // print() blocks in Chrome; other browsers may still be spooling, so remove later.
  setTimeout(() => frame.remove(), 60000);
}
