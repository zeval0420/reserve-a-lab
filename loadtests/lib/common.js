// Shared helpers and user journeys for the Reserve-a-Lab k6 tests.
// Every request is tagged with name=<endpoint> so results can be split per endpoint.
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Counter, Rate, Trend } from 'k6/metrics';

// ---------------------------------------------------------------------------
// Target: localhost only. Any other host aborts the test before a request is sent.
// ---------------------------------------------------------------------------
export const BASE = (__ENV.BASE_URL || 'http://localhost/reserve-a-lab').replace(/\/+$/, '');
const host = BASE.replace(/^https?:\/\//, '').split(/[/:]/)[0];
if (!['localhost', '127.0.0.1', '[::1]'].includes(host)) {
    throw new Error(`Refusing to run: BASE_URL host "${host}" is not localhost`);
}

const envFile = open('../.env');
export const PASSWORD = (envFile.split(/\r?\n/).find((l) => l.startsWith('LT_PASSWORD=')) || '').slice(12).trim();
if (!PASSWORD) throw new Error('loadtests/.env has no LT_PASSWORD (run tools/make_fixtures.py)');

export const N_TEACHERS = 500;
export const N_ADMINS = 5;
export const LABS = ['Science Laboratory 1', 'Science Laboratory 3', 'Science Laboratory 4',
    'Specialized Equipment Room', 'Physics Laboratory 1', 'Physics Laboratory 2', 'Physics Laboratory 3'];
export const SECTIONS_G9 = ['Lithium', 'Beryllium', 'Cesium', 'Barium'];

export const teacherEmail = (i) => `lt.t${String(((i - 1) % N_TEACHERS) + 1).padStart(4, '0')}@example.invalid`;
export const teacherId = (i) => `LT-T${String(((i - 1) % N_TEACHERS) + 1).padStart(4, '0')}`;
export const adminEmail = (i) => `lt.a${String(((i - 1) % N_ADMINS) + 1).padStart(2, '0')}@example.invalid`;

// ---------------------------------------------------------------------------
// Metrics
// ---------------------------------------------------------------------------
export const errors = new Counter('errors');                // tagged type + name
export const stepOk = new Rate('step_ok');                  // every journey step: functional success
export const journeyOk = new Rate('journey_ok');            // whole journey completed
export const journeyTime = new Trend('journey_duration', true);
export const approvalOutcome = new Counter('approval_outcome'); // tagged result
export const submissionOutcome = new Counter('submission_outcome'); // tagged result

const AJAX = { 'X-Requested-With': 'XMLHttpRequest' };
const FORM = { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' };

const THINK_SCALE = Number(__ENV.THINK_SCALE || 1); // 0 only for smoke checks

export function think(min, max) {
    sleep((min + Math.random() * (max - min)) * THINK_SCALE);
}

function params(name, extra = {}) {
    const p = { tags: { name }, ...extra };
    if (extra.jar === undefined) delete p.jar;
    return p;
}

/** Record a step: HTTP-level and functional (body) success. Returns true on success. */
export function track(res, name, bodyOk = () => true, expected = []) {
    let type = null;
    if (res.error_code === 1050 || (res.error && /timeout/i.test(res.error))) type = 'timeout';
    else if (res.status === 0) type = 'connection';
    else if (res.status >= 500) type = 'http_5xx';
    else if (res.status >= 400 && !expected.includes(res.status)) type = 'http_4xx';
    let ok = type === null;
    if (ok) {
        try { ok = !!bodyOk(res); } catch (e) { ok = false; }
        if (!ok) type = 'functional';
    }
    if (type) errors.add(1, { type, name });
    stepOk.add(ok, { name });
    check(res, { [`${name} ok`]: () => ok });
    return ok;
}

export const decode = (s) => s.replace(/&quot;/g, '"').replace(/&#0?39;/g, "'").replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>').replace(/&amp;/g, '&');
// index.php exposes the token as RAL_CSRF_TOKEN; pages using headData.php as a meta tag
const metaCsrf = (html) => ((html || '').match(/<meta name="csrf-token" content="([0-9a-f]{64})"|RAL_CSRF_TOKEN = "([0-9a-f]{64})"/) || []).slice(1).find(Boolean);
const inputValue = (html, name) => {
    const m = (html || '').match(new RegExp(`name="${name.replace(/[[\]]/g, '\\$&')}"\\s+value="([^"]*)"`));
    return m ? decode(m[1]) : null;
};

export function urlencode(pairs) {
    return pairs.map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`).join('&');
}

// ---------------------------------------------------------------------------
// Steps shared by the journeys
// ---------------------------------------------------------------------------

/** Open the login page and log in. Returns the session's CSRF token, or null. */
export function login(email, expectRole, jar) {
    let r = http.get(`${BASE}/index.php`, params('GET index.php', { jar }));
    const csrf = metaCsrf(r.body);
    if (!track(r, 'GET index.php', () => !!csrf)) return null;
    r = http.post(`${BASE}/ajax/ajax_login.php`, { action: 'loginUser', email, password: PASSWORD },
        params('POST ajax_login.php loginUser', { jar, headers: { ...AJAX, 'X-CSRF-Token': csrf } }));
    if (!track(r, 'POST ajax_login.php loginUser', (x) => x.body.trim() === expectRole)) return null;
    return csrf;
}

export function getPage(page, jar) {
    const name = `GET ${page.split('?')[0]}`;
    const r = http.get(`${BASE}/${page}`, params(name, { jar }));
    // A lost session redirects to index.php; the final URL tells the two apart.
    track(r, name, (x) => x.url.includes(page.split('?')[0]));
    return r;
}

export function ajax(method, path, action, body, csrf, jar, opts = {}) {
    const name = `${method} ${path.split('/').pop()}${action ? ' ' + action : ''}`;
    const headers = { ...AJAX, ...(csrf ? { 'X-CSRF-Token': csrf } : {}), ...(opts.headers || {}) };
    const p = params(name, { jar, headers });
    if (opts.expect) p.responseCallback = http.expectedStatuses(...opts.expect);
    const r = method === 'GET'
        ? http.get(`${BASE}/${path}?action=${action}${body ? '&' + urlencode(Object.entries(body)) : ''}`, p)
        : http.post(`${BASE}/${path}`, typeof body === 'string' ? body : (action ? { action, ...body } : body), p);
    track(r, name, opts.ok || (() => true), opts.expect || []);
    return r;
}

export function logout(jar) {
    const r = http.get(`${BASE}/helperFiles/logout.php`, params('GET logout.php', { jar, redirects: 0 }));
    track(r, 'GET logout.php', (x) => x.status === 302);
}

function randomFutureSlot() {
    // Random weekday in 2027, start 07:00-15:00, 1-2 hours long
    let d;
    do {
        d = new Date(Date.UTC(2027, 0, 1) + Math.floor(Math.random() * 364) * 86400000);
    } while (d.getUTCDay() === 0 || d.getUTCDay() === 6);
    const start = 7 + Math.floor(Math.random() * 9);
    const end = start + 1 + Math.floor(Math.random() * 2);
    const hh = (h) => `${String(h).padStart(2, '0')}:00`;
    return { date: d.toISOString().slice(0, 10), start: hh(start), end: hh(end) };
}

function randomMaterials() {
    const n = 1 + Math.floor(Math.random() * 2);
    const picked = new Set();
    while (picked.size < n) picked.add(1 + Math.floor(Math.random() * 10));
    return [...picked].map((i) => {
        const item = `LT Consumable ${String(i).padStart(2, '0')}`;
        return { item, itemLabel: item, desc: 'loadtest', qty: 1 + Math.floor(Math.random() * 3), unit: 'piece' };
    });
}

/** Build the request_submission body exactly as forms.php does (FormData of #reservationForm). */
export function submissionBody(formHtml, csrf, { lab, date, start, end, materials, teacher }) {
    const employeeId = inputValue(formHtml, 'employee_id') || '';
    return urlencode([
        ['csrf_token', csrf], ['employee_id', employeeId], ['venue', lab], ['grade_level', '9'],
        ['sections[]', SECTIONS_G9[Math.floor(Math.random() * 4)]], ['subject', 'Chemistry 1'],
        ['topic', 'Load test topic'], ['unit', 'Chemistry'], ['teacher[]', teacher],
        ['inclusive_date', date], ['start_time', start], ['end_time', end],
        ['action', 'request_submission'], ['mergedMaterials', JSON.stringify(materials)],
    ]);
}

export function teacherOption(formHtml) {
    const sel = (formHtml.match(/<select id="teacher-checkboxes"[\s\S]*?<\/select>/) || [''])[0];
    const opts = [...sel.matchAll(/<option value="([^"]+)"/g)].map((m) => decode(m[1])).filter(Boolean);
    return opts.length ? opts[Math.floor(Math.random() * opts.length)] : 'Tester, Load T0001';
}

/** Pending requests on admin_approve.php, parsed from the Force Approve buttons' data-request JSON. */
export function pendingRequests(html, filter = () => true) {
    const out = [];
    for (const m of (html || '').matchAll(/class="btn-liquid-success approve-btn"[^>]*data-request='([^']*)'/g)) {
        try {
            const row = JSON.parse(decode(m[1]));
            if (filter(row)) out.push(row);
        } catch (e) { /* skip malformed */ }
    }
    return out;
}

// ---------------------------------------------------------------------------
// Journeys
// ---------------------------------------------------------------------------

/** Teacher: log in, home, open the form, submit a request with materials, check the tracker, log out. */
export function requesterJourney() {
    const t0 = Date.now();
    const csrf = login(teacherEmail(__VU), 'requester');
    if (!csrf) { journeyOk.add(false, { journey: 'requester' }); return; }
    think(2, 4);

    const today = new Date().toISOString().slice(0, 10);
    getPage('requester_home.php');
    ajax('POST', 'ajax/store_date.php', null, { date: today }, csrf);
    ajax('POST', 'ajax/ajax_requester.php', 'get_day_snapshot', { date: today }, csrf);
    think(3, 6);

    const form = getPage('forms.php');
    const lab = LABS[Math.floor(Math.random() * LABS.length)];
    const slot = randomFutureSlot();
    ajax('POST', 'ajax/ajax_forms.php', 'get_disabled_dates', { scilabName: lab }, csrf);
    ajax('POST', 'ajax/ajax_forms.php', 'get_subjects_by_grade', { grade: 9 }, csrf);
    ajax('GET', 'ajax/ajax_forms.php', 'get_sections', { grade: 9 }, csrf);
    // Live warning on date/time change (forms.php:1327), which sends csrf_token since the
    // concurrency fixes (before them the page omitted it and got 403).
    ajax('POST', 'ajax/ajax_forms.php', 'check_conflict', { csrf_token: csrf, scilabName: lab, date: slot.date, startTime: slot.start, endTime: slot.end },
        csrf, undefined, { ok: (x) => x.json('status') === 'success' });
    think(5, 10);

    // Confirmation step (forms.php:1003) sends the token and gets an answer.
    ajax('POST', 'ajax/ajax_forms.php', 'check_conflict', { csrf_token: csrf, scilabName: lab, date: slot.date, startTime: slot.start, endTime: slot.end },
        csrf, undefined, { ok: (x) => x.json('status') === 'success' });
    think(2, 4);

    const body = submissionBody(form.body, csrf, { lab, ...slot, materials: randomMaterials(), teacher: teacherOption(form.body || '') });
    const sub = http.post(`${BASE}/ajax/ajax_supervisor_action.php`, body,
        { tags: { name: 'POST ajax_supervisor_action.php request_submission' }, headers: { ...AJAX, ...FORM, 'X-CSRF-Token': csrf } });
    // A slot an approved request already holds is refused by design (random slots can collide)
    const submitted = track(sub, 'POST ajax_supervisor_action.php request_submission', (x) => x.body.trim() === 'success' || /is already booked/.test(x.body));
    submissionOutcome.add(1, { result: /is already booked/.test(sub.body || '') ? 'slot_taken' : (submitted ? 'submitted' : 'failed') });
    think(2, 4);

    getPage('requests.php');
    think(2, 4);
    logout();
    journeyOk.add(submitted, { journey: 'requester' });
    journeyTime.add(Date.now() - t0, { journey: 'requester' });
}

/** Admin: log in, admin home, approvals page, conflict check, approve one pending request, inventory, log out. */
export function adminJourney() {
    const t0 = Date.now();
    const csrf = login(adminEmail(__VU), 'admin');
    if (!csrf) { journeyOk.add(false, { journey: 'admin' }); return; }
    think(2, 4);

    getPage('admin_home.php');
    ajax('POST', 'ajax/ajax_admin.php', 'get_availability', {}, csrf);
    ajax('GET', 'ajax/ajax_admin.php', 'notification_feed', null, csrf);
    ajax('GET', 'ajax/ajax_admin.php', 'get_pending_count', null, csrf);
    think(3, 6);

    const page = getPage('admin_approve.php');
    const pending = pendingRequests(page.body, (r) => String(r.requesterEmployeeID || '').startsWith('LT-'));
    let approved = true;
    if (pending.length) {
        const req = pending[Math.floor(Math.random() * pending.length)];
        const [s, e] = String(req.inclusiveTime).split(' to ');
        // admin_approve.php:1604 sends csrf_token since the concurrency fixes (403 before them).
        ajax('POST', 'ajax/ajax_forms.php', 'check_conflict', { csrf_token: csrf, scilabName: req.scilabName, date: req.inclusiveDate, startTime: s, endTime: e, exclude_id: req.id },
            csrf, undefined, { ok: (x) => x.json('status') === 'success' });
        think(3, 6);
        const r = ajax('POST', 'ajax/ajax_admin_action.php', 'force_approve',
            { id: req.id, control_equipment: `LT-${req.id}`, control_reagent: '', control_permit: '', control_reservation: '', remarks: '' }, csrf, undefined,
            { ok: (x) => /approved|Update failed|Cannot approve/.test(x.body) });
        // A refusal (slot already booked, not enough stock) is a correct outcome, not an error
        const refused = /Cannot approve/.test(r.body);
        approvalOutcome.add(1, { result: /approved/.test(r.body) ? 'approved' : (refused ? 'refused_by_server' : 'update_failed') });
        approved = /approved/.test(r.body) || refused;
    }
    think(2, 4);
    getPage('inventory.php');
    think(2, 4);
    logout();
    journeyOk.add(approved, { journey: 'admin' });
    journeyTime.add(Date.now() - t0, { journey: 'admin' });
}

// ---------------------------------------------------------------------------
// Summary: JSON for the analysis scripts plus a short console digest
// ---------------------------------------------------------------------------
export function summary(data) {
    const name = __ENV.RUN_NAME || 'run';
    const m = data.metrics;
    const d = m.http_req_duration?.values || {};
    const f = (v) => (v === undefined ? 'n/a' : `${Math.round(v)} ms`);
    const lines = [
        `run ${name}`,
        `http_reqs ${m.http_reqs?.values.count ?? 0} (${(m.http_reqs?.values.rate ?? 0).toFixed(2)}/s)`,
        `duration avg ${f(d.avg)} med ${f(d.med)} p90 ${f(d['p(90)'])} p95 ${f(d['p(95)'])} max ${f(d.max)}`,
        `http_req_failed ${((m.http_req_failed?.values.rate ?? 0) * 100).toFixed(2)}%  step_ok ${((m.step_ok?.values.rate ?? 0) * 100).toFixed(2)}%  journey_ok ${((m.journey_ok?.values.rate ?? 0) * 100).toFixed(2)}%`,
        '',
    ];
    return { stdout: lines.join('\n'), [`results/${name}_summary.json`]: JSON.stringify(data, null, 1) };
}

export const TREND_STATS = ['avg', 'min', 'med', 'max', 'p(90)', 'p(95)', 'p(99)'];
