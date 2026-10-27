// Test 5a, double booking of one lab time slot.
// N teachers submit a request for the SAME lab, date and time at the same instant;
// then N admin sessions each open one of those requests and approve it at the same
// instant. Synchronisation: setup() fixes two wall-clock instants that every VU
// waits for (a barrier), after logging in and loading the pages beforehand.
//
// MODE=race (default): each admin first runs the conflict check the way a correct
//   client would (with the csrf_token field) and does not approve if it reports an
//   approved conflict; i.e. the most favourable client behaviour.
// MODE=sequential: control run. Admins approve one after another, 3 s apart, and approve
//   even when the check reports an approved conflict (a stale page or a direct POST), so the
//   server-side check is what decides. Before the fix admin_approve.php sent the check without
//   csrf_token (HTTP 403, no warning); BROWSER_CHECK=no_csrf replays that old request.
// The database is checked by tools/concurrency.ps1 after the run.
import http from 'k6/http';
import { sleep } from 'k6';
import exec from 'k6/execution';
import { Counter, Trend } from 'k6/metrics';
import { BASE, login, teacherEmail, teacherId, adminEmail, submissionBody, getPage, track, ajax, pendingRequests, summary, TREND_STATS } from './lib/common.js';

const N = Number(__ENV.N || 20);
const MODE = __ENV.MODE || 'race';
const LAB = __ENV.LAB || 'Science Laboratory 3';
const DATE = __ENV.DATE || '2027-03-15';
const START = '09:00';
const END = '11:00';

const skew = new Trend('barrier_skew', true);
const outcome = new Counter('outcome');

export const options = {
    summaryTrendStats: TREND_STATS,
    scenarios: { slot: { executor: 'per-vu-iterations', vus: N, iterations: 1, maxDuration: '4m' } },
};

export function setup() {
    const now = Date.now();
    return { submitAt: now + 20000, approveAt: now + 40000 };
}

function waitUntil(t) {
    const d = t - Date.now();
    if (d > 0) sleep(d / 1000);
}

export default function (data) {
    const i = exec.vu.idInTest;
    const tJar = new http.CookieJar();
    const aJar = new http.CookieJar();

    // Before the barrier: log both sessions in and open the pages
    const tCsrf = login(teacherEmail(i), 'requester', tJar);
    const form = getPage('forms.php', tJar);
    const aCsrf = login(adminEmail(i), 'admin', aJar);
    if (!tCsrf || !aCsrf) { outcome.add(1, { result: 'login_failed' }); return; }
    const body = submissionBody(form.body, tCsrf, { lab: LAB, date: DATE, start: START, end: END, materials: [], teacher: 'Tester, Load T0001' });

    waitUntil(data.submitAt);
    skew.add(Date.now() - data.submitAt, { phase: 'submit' });
    const sub = http.post(`${BASE}/ajax/ajax_supervisor_action.php`, body, {
        jar: tJar, tags: { name: 'POST ajax_supervisor_action.php request_submission' },
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8', 'X-CSRF-Token': tCsrf },
    });
    const submitted = track(sub, 'POST ajax_supervisor_action.php request_submission', (x) => x.body.trim() === 'success');
    outcome.add(1, { result: submitted ? 'submitted' : `submit_rejected:${sub.body.trim().slice(0, 40)}` });
    if (!submitted) return;

    // The admin opens the approvals page and the request filed by "their" teacher
    waitUntil(data.approveAt - 10000);
    const page = getPage('admin_approve.php', aJar);
    const mine = pendingRequests(page.body, (r) => r.requesterEmployeeID === teacherId(i) && r.inclusiveDate === DATE && r.scilabName === LAB);
    if (!mine.length) { outcome.add(1, { result: 'request_not_listed' }); return; }
    const req = mine[0];

    const at = MODE === 'sequential' ? data.approveAt + (i - 1) * 3000 : data.approveAt;
    waitUntil(at);
    skew.add(Date.now() - at, { phase: 'approve' });

    const check = { scilabName: LAB, date: DATE, startTime: START, endTime: END, exclude_id: req.id };
    if (MODE === 'sequential' && __ENV.BROWSER_CHECK === 'no_csrf') {
        // What the page sent before the fix: no csrf_token field, so 403 and no warning was shown
        const b = ajax('POST', 'ajax/ajax_forms.php', 'check_conflict', check, aCsrf, aJar, { expect: [403], ok: () => true });
        outcome.add(1, { browser_check_status: String(b.status) });
    }
    const c = ajax('POST', 'ajax/ajax_forms.php', 'check_conflict', { csrf_token: aCsrf, ...check }, aCsrf, aJar,
        { ok: (x) => x.json('status') === 'success' });
    const conflict = c.status === 200 ? c.json('conflict_type') : `http_${c.status}`;
    outcome.add(1, { correct_check: conflict });
    if (MODE === 'race' && conflict === 'approved') { outcome.add(1, { result: 'blocked_by_check' }); return; }

    const r = ajax('POST', 'ajax/ajax_admin_action.php', 'force_approve',
        { id: req.id, control_equipment: `LT-${req.id}`, control_reagent: '', control_permit: '', control_reservation: '', remarks: '' },
        aCsrf, aJar, { ok: (x) => /approved|Update failed|Cannot approve/.test(x.body) });
    outcome.add(1, { result: /approved/.test(r.body) ? 'approved' : /Cannot approve/.test(r.body) ? 'refused_by_server' : `approve_response:${r.body.trim().slice(0, 40)}` });
}

export const handleSummary = summary;
