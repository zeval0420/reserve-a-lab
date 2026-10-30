/*
 * RalDatePicker: inline calendar for choosing one or many dates (single days and ranges).
 *
 *   const picker = RalDatePicker.create('#date-picker', {
 *       min: '2026-10-30',          // first selectable day (default: today)
 *       maxDates: 62,
 *       onChange: dates => {},      // sorted 'YYYY-MM-DD' list, after every user change
 *       notify: (message, type) => {}
 *   });
 *   picker.get() / picker.add(list) / picker.clear() / picker.setUnavailable(list)
 *
 * Pick days: click toggles a day, Shift+click adds everything since the last click.
 * Pick a range: click the first day, then the last day.
 * Keyboard: arrows move, Home/End jump to the week's ends, PageUp/PageDown change month,
 * Enter/Space picks, Escape cancels a half-made range.
 */
(function (global) {
    'use strict';

    var MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    var SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    var WEEK = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
    var DAY_MS = 86400000;

    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function iso(y, m, d) { return y + '-' + pad(m + 1) + '-' + pad(d); }
    function parse(s) { var p = s.split('-'); return { y: +p[0], m: +p[1] - 1, d: +p[2] }; }
    function utc(s) { var p = parse(s); return Date.UTC(p.y, p.m, p.d); }
    function fromUtc(ms) { var d = new Date(ms); return iso(d.getUTCFullYear(), d.getUTCMonth(), d.getUTCDate()); }
    function addDays(s, n) { return fromUtc(utc(s) + n * DAY_MS); }
    function todayIso() { var t = new Date(); return iso(t.getFullYear(), t.getMonth(), t.getDate()); }
    function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }

    /* Runs of consecutive days: [{from, to}] */
    function runs(dates) {
        var out = [];
        dates.forEach(function (d) {
            var last = out[out.length - 1];
            if (last && utc(d) - utc(last.to) === DAY_MS) last.to = d;
            else out.push({ from: d, to: d });
        });
        return out;
    }

    function runLabel(run) {
        var a = parse(run.from), b = parse(run.to);
        var start = SHORT[a.m] + ' ' + a.d;
        if (run.from === run.to) return start;
        if (a.m === b.m && a.y === b.y) return start + '-' + b.d;
        return start + ' - ' + SHORT[b.m] + ' ' + b.d;
    }

    /* "Nov 3, 5, 9-14, 2026": same wording as scilab_format_dates() on the server. */
    function summary(dates) {
        if (!dates.length) return '';
        if (dates.length === 1) return dates[0];
        var months = {}, order = [];
        dates.forEach(function (d) {
            var ym = d.slice(0, 7);
            if (!months[ym]) { months[ym] = []; order.push(ym); }
            months[ym].push(+d.slice(8));
        });
        var years = {};
        order.forEach(function (ym) { years[ym.slice(0, 4)] = 1; });
        var yearKeys = Object.keys(years), perMonth = yearKeys.length > 1;
        var parts = order.map(function (ym) {
            var days = months[ym], chunks = [];
            for (var i = 0; i < days.length;) {
                var j = i;
                while (j + 1 < days.length && days[j + 1] === days[j] + 1) j++;
                if (j - i >= 2) chunks.push(days[i] + '-' + days[j]);
                else for (var k = i; k <= j; k++) chunks.push(String(days[k]));
                i = j + 1;
            }
            return SHORT[+ym.slice(5) - 1] + ' ' + chunks.join(', ') + (perMonth ? ', ' + ym.slice(0, 4) : '');
        });
        return parts.join('; ') + (perMonth ? '' : ', ' + yearKeys[0]);
    }

    function create(target, options) {
        var root = typeof target === 'string' ? document.querySelector(target) : target;
        if (!root) return null;
        options = options || {};

        var min = options.min || todayIso();
        var maxDates = options.maxDates || 62;
        var notify = options.notify || function () {};
        var onChange = options.onChange || function () {};

        var selected = [];          // sorted
        var unavailable = {};       // iso -> true (booked for the chosen venue)
        var mode = 'single';        // 'single' | 'range'
        var anchor = null;          // first click of a range
        var lastClicked = null;     // for Shift+click
        var hover = null;
        var view = { y: parse(min).y, m: parse(min).m };
        var focusIso = min;
        var wantFocus = false;

        root.classList.add('rdp');
        root.innerHTML =
            '<div class="rdp-cal">' +
                '<div class="rdp-head">' +
                    '<button type="button" class="rdp-nav" data-nav="-1" aria-label="Previous month">&#8249;</button>' +
                    '<div class="rdp-title" aria-live="polite"></div>' +
                    '<button type="button" class="rdp-nav" data-nav="1" aria-label="Next month">&#8250;</button>' +
                '</div>' +
                '<div class="rdp-modes" role="group" aria-label="Selection mode">' +
                    '<button type="button" data-mode="single">Pick days</button>' +
                    '<button type="button" data-mode="range">Pick a range</button>' +
                '</div>' +
                '<div class="rdp-week" aria-hidden="true">' + WEEK.map(function (w) { return '<span>' + w + '</span>'; }).join('') + '</div>' +
                '<div class="rdp-grid" role="grid"></div>' +
                '<p class="rdp-hint"></p>' +
                '<div class="rdp-legend" aria-hidden="true">' +
                    '<span><i class="rdp-dot rdp-dot-sel"></i>Selected</span>' +
                    '<span><i class="rdp-dot rdp-dot-off"></i>Unavailable</span>' +
                    '<span><i class="rdp-dot rdp-dot-today"></i>Today</span>' +
                '</div>' +
            '</div>' +
            '<div class="rdp-side">' +
                '<div class="rdp-count"></div>' +
                '<div class="rdp-summary"></div>' +
                '<div class="rdp-chips"></div>' +
                '<button type="button" class="rdp-clear">Clear all</button>' +
            '</div>';

        var $title = root.querySelector('.rdp-title');
        var $grid = root.querySelector('.rdp-grid');
        var $hint = root.querySelector('.rdp-hint');
        var $count = root.querySelector('.rdp-count');
        var $summary = root.querySelector('.rdp-summary');
        var $chips = root.querySelector('.rdp-chips');
        var $clear = root.querySelector('.rdp-clear');
        var $prev = root.querySelector('[data-nav="-1"]');

        function isPast(d) { return d < min; }
        function isBlocked(d) { return isPast(d) || !!unavailable[d]; }
        function has(d) { return selected.indexOf(d) !== -1; }

        function renderGrid() {
            $title.textContent = MONTHS[view.m] + ' ' + view.y;
            $prev.disabled = view.y === parse(min).y && view.m <= parse(min).m;

            var set = {};
            selected.forEach(function (d) { set[d] = true; });

            var first = new Date(Date.UTC(view.y, view.m, 1));
            var lead = first.getUTCDay();
            var count = new Date(Date.UTC(view.y, view.m + 1, 0)).getUTCDate();
            var today = todayIso();
            var lo = null, hi = null;
            if (mode === 'range' && anchor && hover) { lo = anchor < hover ? anchor : hover; hi = anchor < hover ? hover : anchor; }

            // Roving tabindex target must be visible in this month
            var inMonth = focusIso.slice(0, 7) === view.y + '-' + pad(view.m + 1);
            var tabIso = inMonth ? focusIso : iso(view.y, view.m, 1);

            var html = '';
            for (var i = 0; i < lead; i++) html += '<span class="rdp-empty"></span>';
            for (var day = 1; day <= count; day++) {
                var d = iso(view.y, view.m, day), col = (lead + day - 1) % 7;
                var cls = ['rdp-day'];
                var sel = !!set[d];
                if (sel) {
                    var prev = col > 0 && set[addDays(d, -1)];
                    var next = col < 6 && set[addDays(d, 1)];
                    cls.push('is-selected', prev && next ? 'is-mid' : prev ? 'is-end' : next ? 'is-start' : 'is-single');
                }
                if (d === today) cls.push('is-today');
                if (isPast(d)) cls.push('is-past');
                else if (unavailable[d]) cls.push('is-unavailable');
                if (d === anchor) cls.push('is-anchor');
                if (lo && d >= lo && d <= hi && !sel) cls.push('is-preview');
                var blocked = isBlocked(d);
                var label = day + ' ' + MONTHS[view.m] + ' ' + view.y + (sel ? ', selected' : '') + (unavailable[d] ? ', unavailable for this venue' : isPast(d) ? ', past date' : '');
                html += '<button type="button" class="' + cls.join(' ') + '" data-date="' + d + '" role="gridcell"' +
                    ' tabindex="' + (d === tabIso ? 0 : -1) + '" aria-pressed="' + sel + '"' + (blocked ? ' aria-disabled="true"' : '') +
                    ' aria-label="' + esc(label) + '"' + (unavailable[d] ? ' title="Already booked for this venue"' : '') + '>' + day + '</button>';
            }
            $grid.innerHTML = html;

            if (wantFocus) {
                var el = $grid.querySelector('[data-date="' + focusIso + '"]');
                if (el) el.focus();
                wantFocus = false;
            }
        }

        function renderSide() {
            var n = selected.length;
            $count.innerHTML = n ? '<strong>' + n + '</strong> day' + (n === 1 ? '' : 's') + ' selected' : 'No date selected yet';
            $summary.textContent = summary(selected);
            $summary.style.display = n ? '' : 'none';
            $clear.style.display = n ? '' : 'none';
            $chips.innerHTML = runs(selected).map(function (r) {
                return '<span class="rdp-chip">' + esc(runLabel(r)) +
                    '<button type="button" data-from="' + r.from + '" data-to="' + r.to + '" aria-label="Remove ' + esc(runLabel(r)) + '">&times;</button></span>';
            }).join('');
        }

        function renderMode() {
            [].forEach.call(root.querySelectorAll('.rdp-modes button'), function (b) {
                var on = b.getAttribute('data-mode') === mode;
                b.classList.toggle('is-on', on);
                b.setAttribute('aria-pressed', on);
            });
            $hint.textContent = mode === 'range'
                ? (anchor ? 'Now click the last day of the range.' : 'Click the first day, then the last day.')
                : 'Click days to pick or unpick them. Shift+click adds all days in between.';
        }

        function render() { renderGrid(); renderSide(); renderMode(); }

        function changed() { onChange(selected.slice()); }

        /* Add days, skipping blocked ones and respecting the cap; returns false if nothing was added. */
        function addList(list, silent) {
            var set = {};
            selected.forEach(function (d) { set[d] = true; });
            var total = selected.length, skipped = 0, capped = false, added = 0;
            list.forEach(function (d) {
                if (set[d]) return;
                if (isBlocked(d)) { skipped++; return; }
                if (total >= maxDates) { capped = true; return; }
                set[d] = true; total++; added++;
            });
            selected = Object.keys(set).sort();
            if (skipped && !silent) notify(skipped + ' day' + (skipped === 1 ? '' : 's') + ' skipped: past or unavailable for this venue.', 'warning');
            if (capped && !silent) notify('A request can cover at most ' + maxDates + ' dates.', 'warning');
            return added > 0;
        }

        function rangeList(a, b) {
            var lo = a < b ? a : b, hi = a < b ? b : a, out = [];
            for (var d = lo; d <= hi; d = addDays(d, 1)) out.push(d);
            return out;
        }

        function pick(d, shift) {
            if (isBlocked(d)) {
                notify(isPast(d) ? 'That date has already passed.' : 'That date is already booked for this venue.', 'warning');
                return;
            }
            if (mode === 'range') {
                if (!anchor) { anchor = d; hover = d; }
                else { addList(rangeList(anchor, d)); anchor = null; hover = null; lastClicked = d; changed(); }
            } else if (shift && lastClicked) {
                addList(rangeList(lastClicked, d)); lastClicked = d; changed();
            } else {
                if (has(d)) selected = selected.filter(function (x) { return x !== d; });
                else addList([d]);
                lastClicked = d; changed();
            }
            focusIso = d;
            render();
        }

        function moveFocus(d) {
            if (d < min) d = min;
            focusIso = d;
            var p = parse(d);
            view = { y: p.y, m: p.m };
            wantFocus = true;
            renderGrid();
        }

        /* ---- events ---- */
        root.addEventListener('click', function (e) {
            var t = e.target;
            var day = t.closest && t.closest('.rdp-day');
            if (day) { pick(day.getAttribute('data-date'), e.shiftKey); return; }

            var nav = t.closest && t.closest('[data-nav]');
            if (nav) {
                var m = view.m + (+nav.getAttribute('data-nav')), y = view.y;
                if (m < 0) { m = 11; y--; } else if (m > 11) { m = 0; y++; }
                view = { y: y, m: m };
                renderGrid();
                return;
            }
            var mb = t.closest && t.closest('[data-mode]');
            if (mb) { mode = mb.getAttribute('data-mode'); anchor = null; hover = null; render(); return; }

            var chip = t.closest && t.closest('.rdp-chip button');
            if (chip) {
                var from = chip.getAttribute('data-from'), to = chip.getAttribute('data-to');
                selected = selected.filter(function (d) { return d < from || d > to; });
                changed(); render();
                return;
            }
            if (t.closest && t.closest('.rdp-clear')) { api.clear(true); }
        });

        $grid.addEventListener('mouseover', function (e) {
            if (mode !== 'range' || !anchor) return;
            var day = e.target.closest && e.target.closest('.rdp-day');
            if (day && day.getAttribute('data-date') !== hover) { hover = day.getAttribute('data-date'); renderGrid(); }
        });
        $grid.addEventListener('mouseleave', function () {
            if (mode === 'range' && anchor && hover !== anchor) { hover = anchor; renderGrid(); }
        });

        $grid.addEventListener('keydown', function (e) {
            var day = e.target.closest && e.target.closest('.rdp-day');
            if (!day) return;
            var d = day.getAttribute('data-date'), next = null, p = parse(d);
            switch (e.key) {
                case 'ArrowLeft': next = addDays(d, -1); break;
                case 'ArrowRight': next = addDays(d, 1); break;
                case 'ArrowUp': next = addDays(d, -7); break;
                case 'ArrowDown': next = addDays(d, 7); break;
                case 'Home': next = addDays(d, -new Date(Date.UTC(p.y, p.m, p.d)).getUTCDay()); break;
                case 'End': next = addDays(d, 6 - new Date(Date.UTC(p.y, p.m, p.d)).getUTCDay()); break;
                case 'PageUp': next = iso(p.m === 0 ? p.y - 1 : p.y, p.m === 0 ? 11 : p.m - 1, 1); break;
                case 'PageDown': next = iso(p.m === 11 ? p.y + 1 : p.y, p.m === 11 ? 0 : p.m + 1, 1); break;
                case 'Escape':
                    if (anchor) { anchor = null; hover = null; e.preventDefault(); wantFocus = true; render(); }
                    return;
                default: return;
            }
            e.preventDefault();
            if (mode === 'range' && anchor) hover = next;
            moveFocus(next);
        });

        /* ---- public api ---- */
        var api = {
            get: function () { return selected.slice(); },
/* silent: a preset (e.g. the day picked on the home calendar), so no warnings and the view jumps to it */
            add: function (list, silent) {
                var ok = addList(list, silent);
                if (silent && selected.length) {
                    var f = parse(selected[0]);
                    view = { y: f.y, m: f.m };
                    focusIso = selected[0];
                }
                render();
                if (ok) changed();
                return ok;
            },
            clear: function (fromUser) {
                var had = selected.length > 0;
                selected = []; anchor = null; hover = null; lastClicked = null;
                render();
                if (fromUser && had) changed();
            },
            /* Days the venue already has approved. Returns how many selected days that removed. */
            setUnavailable: function (list) {
                unavailable = {};
                (list || []).forEach(function (d) { unavailable[d] = true; });
                var kept = selected.filter(function (d) { return !unavailable[d]; });
                var removed = selected.length - kept.length;
                selected = kept;
                render();
                if (removed) changed();
                return removed;
            },
            invalid: function (on) { root.classList.toggle('is-invalid', !!on); },
            summary: summary
        };

        render();
        return api;
    }

    global.RalDatePicker = { create: create, summary: summary, runs: runs };
})(window);
