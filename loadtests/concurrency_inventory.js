// Test 5b, last units of one inventory item.
// The item "LT Last Units" holds STOCK units (reset before each repetition). N teachers
// each request QTY units at the same instant (different dates, so lab-slot logic plays
// no part); then N admin sessions approve all N requests at the same instant. Approval
// is where stock is deducted (scilab_deduct_inventory).
// MODE=sequential: control run, approvals 3 s apart instead of simultaneous.
// The database is checked by tools/concurrency.ps1 after the run.
import http from 'k6/http';
import { sleep } from 'k6';
import exec from 'k6/execution';
import { Counter, Trend } from 'k6/metrics';
import { BASE, login, teacherEmail, teacherId, adminEmail, submissionBody, getPage, track, ajax, pendingRequests, summary, TREND_STATS } from './lib/common.js';

const N = Number(__ENV.N || 20);
const QTY = Number(__ENV.QTY || 2);
const MODE = __ENV.MODE || 'race';
const LAB = 'Science Laboratory 4';
const BASE_DATE = Date.UTC(2027, 5, 1); // 1 June 2027; VU i books BASE_DATE + i days

const skew = new Trend('barrier_skew', true);
const outcome = new Counter('outcome');

export const options = {
    summaryTrendStats: TREND_STATS,
    scenarios: { inventory: { executor: 'per-vu-iterations', vus: N, iterations: 1, maxDuration: '4m' } },
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
    const date = new Date(BASE_DATE + i * 86400000).toISOString().slice(0, 10);
    const tJar = new http.CookieJar();
    const aJar = new http.CookieJar();

    const tCsrf = login(teacherEmail(i), 'requester', tJar);
    const form = getPage('forms.php', tJar);
    const aCsrf = login(adminEmail(i), 'admin', aJar);
    if (!tCsrf || !aCsrf) { outcome.add(1, { result: 'login_failed' }); return; }
    const materials = [{ item: 'LT Last Units', itemLabel: 'LT Last Units', desc: 'loadtest', qty: QTY, unit: 'piece' }];
    const body = submissionBody(form.body, tCsrf, { lab: LAB, date, start: '13:00', end: '14:00', materials, teacher: 'Tester, Load T0001' });

    waitUntil(data.submitAt);
    skew.add(Date.now() - data.submitAt, { phase: 'submit' });
    const sub = http.post(`${BASE}/ajax/ajax_supervisor_action.php`, body, {
        jar: tJar, tags: { name: 'POST ajax_supervisor_action.php request_submission' },
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8', 'X-CSRF-Token': tCsrf },
    });
    const submitted = track(sub, 'POST ajax_supervisor_action.php request_submission', (x) => x.body.trim() === 'success');
    outcome.add(1, { result: submitted ? 'submitted' : `submit_rejected:${sub.body.trim().slice(0, 40)}` });
    if (!submitted) return;

    waitUntil(data.approveAt - 10000);
    const page = getPage('admin_approve.php', aJar);
    const mine = pendingRequests(page.body, (r) => r.requesterEmployeeID === teacherId(i) && r.inclusiveDate === date);
    if (!mine.length) { outcome.add(1, { result: 'request_not_listed' }); return; }
    const req = mine[0];

    const at = MODE === 'sequential' ? data.approveAt + (i - 1) * 3000 : data.approveAt;
    waitUntil(at);
    skew.add(Date.now() - at, { phase: 'approve' });
    const r = ajax('POST', 'ajax/ajax_admin_action.php', 'force_approve',
        { id: req.id, control_equipment: `LT-${req.id}`, control_reagent: '', control_permit: '', control_reservation: '', remarks: '' },
        aCsrf, aJar, { ok: (x) => /approved|Update failed|Cannot approve/.test(x.body) });
    outcome.add(1, { result: /approved/.test(r.body) ? 'approved' : /Cannot approve/.test(r.body) ? 'refused_by_server' : `approve_response:${r.body.trim().slice(0, 40)}` });
}

export const handleSummary = summary;
