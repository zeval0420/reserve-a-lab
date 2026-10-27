"""Inline-SVG charts for performance-report/load-report.html.

Colours come from CSS custom properties defined by the report (--s1 / --s2 for the
two series, --line for grid, --muted for axes), so light and dark mode both work.
Series palette: reference categorical slots 1-2 (blue, orange), validated with the
dataviz validator; lines are direct-labelled and a legend is shown for 2 series.
Every chart carries data-points JSON for the hover crosshair/tooltip script.
"""
import html
import json
import math


def _nice_ticks(lo, hi, n=5):
    if hi <= lo:
        hi = lo + 1
    raw = (hi - lo) / n
    mag = 10 ** math.floor(math.log10(raw))
    step = min((s * mag for s in (1, 2, 2.5, 5, 10) if s * mag >= raw), default=mag * 10)
    start = math.floor(lo / step) * step
    ticks, v = [], start
    while True:
        ticks.append(round(v, 10))
        if v >= hi - step * 0.001:
            return ticks
        v += step


def _fmt(v, unit):
    if unit == "ms":
        return f"{v / 1000:.1f} s" if v >= 1000 else f"{v:.0f} ms"
    if unit == "%":
        return f"{v:.0f}%"
    if unit == "MB":
        return f"{v:.0f} MB"
    return f"{v:g}"


def line_chart(series, *, x_label, y_unit, title, x_fmt=None, log_y=False, height=300, y_min=None, refs=(), x_ticks=None):
    """series: list of dict(name, cls ('s1'|'s2'|'s3'), points=[(x, y), ...]).
    refs: horizontal reference lines [(y, label)]."""
    W, H = 760, height
    L, R, T, B = 64, 120, 16, 44
    xs = [p[0] for s in series for p in s["points"] if p[1] is not None]
    ys = [p[1] for s in series for p in s["points"] if p[1] is not None] + [r[0] for r in refs]
    x0, x1 = min(xs), max(xs)
    if log_y:
        lo = 10 ** math.floor(math.log10(max(1, min(ys))))
        hi = 10 ** math.ceil(math.log10(max(ys)))
        ticks = [10 ** k for k in range(int(math.log10(lo)), int(math.log10(hi)) + 1)]
        fy = lambda v: T + (H - T - B) * (1 - (math.log10(max(v, lo)) - math.log10(lo)) / (math.log10(hi) - math.log10(lo)))
    else:
        lo = 0 if y_min is None else y_min
        ticks = _nice_ticks(lo, max(ys) * 1.08)
        hi = ticks[-1]
        fy = lambda v: T + (H - T - B) * (1 - (v - lo) / (hi - lo))
    fx = lambda v: L + (W - L - R) * ((v - x0) / (x1 - x0) if x1 > x0 else 0.5)
    xf = x_fmt or (lambda v: f"{v:g}")

    out = [f'<svg viewBox="0 0 {W} {H}" role="img" aria-label="{html.escape(title)}" class="chart">']
    for t in ticks:
        y = fy(t)
        out.append(f'<line class="grid" x1="{L}" x2="{W - R}" y1="{y:.1f}" y2="{y:.1f}"/>'
                   f'<text class="axis" x="{L - 8}" y="{y + 4:.1f}" text-anchor="end">{_fmt(t, y_unit)}</text>')
    for xv in (x_ticks or _nice_ticks(x0, x1, 6)):
        if x0 <= xv <= x1:
            x = fx(xv)
            out.append(f'<text class="axis" x="{x:.1f}" y="{H - B + 18}" text-anchor="middle">{html.escape(xf(xv))}</text>')
    out.append(f'<text class="axis" x="{(L + W - R) / 2:.0f}" y="{H - 6}" text-anchor="middle">{html.escape(x_label)}</text>')
    for yv, lbl in refs:
        y = fy(yv)
        out.append(f'<line class="ref" x1="{L}" x2="{W - R}" y1="{y:.1f}" y2="{y:.1f}"/>'
                   f'<text class="axis" x="{W - R + 6}" y="{y + 4:.1f}">{html.escape(lbl)}</text>')
    labels = []
    for s in series:
        pts = [(fx(x), fy(y)) for x, y in s["points"] if y is not None]
        if not pts:
            continue
        d = "M" + " L".join(f"{x:.1f},{y:.1f}" for x, y in pts)
        out.append(f'<path class="ln {s["cls"]}" d="{d}"/>')
        if len(pts) <= 30:
            out += [f'<circle class="dot {s["cls"]}" cx="{x:.1f}" cy="{y:.1f}" r="4"/>' for x, y in pts]
        labels.append([pts[-1][0], pts[-1][1], s["name"]])
    # direct labels at the line ends, nudged apart so they never overlap
    labels.sort(key=lambda l: l[1])
    for i in range(1, len(labels)):
        labels[i][1] = max(labels[i][1], labels[i - 1][1] + 14)
    for lx, ly, name in labels:
        out.append(f'<text class="lbl" x="{lx + 8:.1f}" y="{ly + 4:.1f}">{html.escape(name)}</text>')
    # hover data: one entry per x, values per series
    xs_all = sorted({x for s in series for x, y in s["points"] if y is not None})
    rows = []
    for xv in xs_all:
        vals = []
        for s in series:
            v = next((y for x, y in s["points"] if x == xv), None)
            if v is not None:
                vals.append([s["name"], _fmt(v, y_unit), s["cls"]])
        rows.append([round(fx(xv), 1), xf(xv), vals])
    out.append(f'<rect class="hit" x="{L}" y="{T}" width="{W - L - R}" height="{H - T - B}" data-points=\'{html.escape(json.dumps(rows))}\' data-top="{T}" data-bottom="{H - B}"/>')
    out.append('<line class="xhair" x1="0" x2="0" y1="0" y2="0"/></svg>')
    legend = "".join(f'<span><i class="{s["cls"]}"></i>{html.escape(s["name"])}</span>' for s in series)
    return f'<div class="legend">{legend}</div>' + "".join(out) if len(series) > 1 else "".join(out)


def bar_chart(bars, *, y_unit, title, ref=None, height=260, y_max=None):
    """bars: list of (label, value, cls, tooltip). ref: (value, label) reference line."""
    W, H = 760, height
    L, R, T, B = 64, 120, 16, 40
    vmax = max([b[1] for b in bars] + ([ref[0]] if ref else []) + ([y_max] if y_max else []))
    ticks = _nice_ticks(0, vmax)
    hi = ticks[-1]
    fy = lambda v: T + (H - T - B) * (1 - v / hi)
    bw = (W - L - R) / len(bars)
    out = [f'<svg viewBox="0 0 {W} {H}" role="img" aria-label="{html.escape(title)}" class="chart">']
    for t in ticks:
        y = fy(t)
        out.append(f'<line class="grid" x1="{L}" x2="{W - R}" y1="{y:.1f}" y2="{y:.1f}"/>'
                   f'<text class="axis" x="{L - 8}" y="{y + 4:.1f}" text-anchor="end">{_fmt(t, y_unit)}</text>')
    for i, (label, v, cls, tip) in enumerate(bars):
        x = L + i * bw + bw * 0.22
        w = bw * 0.56
        y = fy(v)
        h = max(0.0, fy(0) - y)
        r = min(4, w / 2, h)
        # rounded top corners, square at the baseline
        out.append(f'<path class="bar {cls}" d="M{x:.1f},{fy(0):.1f} V{y + r:.1f} Q{x:.1f},{y:.1f} {x + r:.1f},{y:.1f} H{x + w - r:.1f} '
                   f'Q{x + w:.1f},{y:.1f} {x + w:.1f},{y + r:.1f} V{fy(0):.1f} Z"><title>{html.escape(tip)}</title></path>')
        out.append(f'<text class="val strong" x="{x + w / 2:.1f}" y="{y - 6:.1f}" text-anchor="middle">{v:g}</text>')
        out.append(f'<text class="axis" x="{x + w / 2:.1f}" y="{H - B + 18}" text-anchor="middle">{html.escape(label)}</text>')
    if ref:
        y = fy(ref[0])
        out.append(f'<line class="ref" x1="{L}" x2="{W - R}" y1="{y:.1f}" y2="{y:.1f}"/>'
                   f'<text class="axis" x="{W - R + 6}" y="{y + 4:.1f}">{html.escape(ref[1])}</text>')
    out.append("</svg>")
    return "".join(out)


HOVER_JS = """
document.querySelectorAll('svg.chart .hit').forEach(function (hit) {
  var svg = hit.ownerSVGElement, pts = JSON.parse(hit.dataset.points), xh = svg.querySelector('.xhair');
  var tip = document.getElementById('tip');
  function show(e) {
    var r = svg.getBoundingClientRect(), vb = svg.viewBox.baseVal, sx = vb.width / r.width;
    var x = (e.clientX - r.left) * sx, best = null, bd = 1e9;
    pts.forEach(function (p) { var d = Math.abs(p[0] - x); if (d < bd) { bd = d; best = p; } });
    if (!best) return;
    xh.setAttribute('x1', best[0]); xh.setAttribute('x2', best[0]);
    xh.setAttribute('y1', hit.dataset.top); xh.setAttribute('y2', hit.dataset.bottom); xh.style.display = 'block';
    tip.innerHTML = '<b>' + best[1] + '</b>' + best[2].map(function (v) {
      return '<div><i class="' + v[2] + '"></i>' + v[0] + ': <b>' + v[1] + '</b></div>'; }).join('');
    tip.style.display = 'block';
    var tx = e.clientX + 14, ty = e.clientY + 14;
    if (tx + tip.offsetWidth > window.innerWidth - 8) tx = e.clientX - tip.offsetWidth - 14;
    tip.style.left = tx + 'px'; tip.style.top = ty + 'px';
  }
  hit.addEventListener('mousemove', show);
  hit.addEventListener('touchstart', function (e) { show(e.touches[0]); }, { passive: true });
  hit.addEventListener('mouseleave', function () { tip.style.display = 'none'; xh.style.display = 'none'; });
});
"""
