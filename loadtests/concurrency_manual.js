// Test 5c, control: the inventory page's manual deduction (ajax_inventory.php,
// deduct_inventory), which uses a transaction with SELECT ... FOR UPDATE. N admin
// sessions each deduct 1 unit of "LT Manual Deduct" (STOCK units) at the same instant.
// A correct implementation lets exactly STOCK of them succeed and ends at 0.
// Shows that the method detects correct locking, not only missing locking.
import http from 'k6/http';
import { sleep } from 'k6';
import exec from 'k6/execution';
import { Counter, Trend } from 'k6/metrics';
import { login, adminEmail, getPage, ajax, urlencode, summary, TREND_STATS } from './lib/common.js';

const N = Number(__ENV.N || 20);
const ITEM_ID = __ENV.ITEM_ID;
if (!ITEM_ID) throw new Error('ITEM_ID is required');

const skew = new Trend('barrier_skew', true);
const outcome = new Counter('outcome');

export const options = {
    summaryTrendStats: TREND_STATS,
    scenarios: { manual: { executor: 'per-vu-iterations', vus: N, iterations: 1, maxDuration: '3m' } },
};

export function setup() {
    return { goAt: Date.now() + 15000 };
}

export default function (data) {
    const i = exec.vu.idInTest;
    const jar = new http.CookieJar();
    const csrf = login(adminEmail(i), 'admin', jar);
    if (!csrf) { outcome.add(1, { result: 'login_failed' }); return; }
    getPage('inventory.php', jar);

    const d = data.goAt - Date.now();
    if (d > 0) sleep(d / 1000);
    skew.add(Date.now() - data.goAt, { phase: 'deduct' });
    const body = urlencode([['action', 'deduct_inventory'], ['reason', `load test ${i}`], ['items[0][id]', ITEM_ID], ['items[0][quantity]', '1']]);
    const r = ajax('POST', 'ajax/ajax_inventory.php', 'deduct_inventory', body, csrf, jar,
        { headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }, ok: (x) => ['success', 'error'].includes(x.json('status')) });
    let result = 'unparsed';
    try { result = r.json('status') === 'success' ? 'deducted' : `refused:${String(r.json('message')).slice(0, 50)}`; } catch (e) { /* keep */ }
    outcome.add(1, { result });
}

export const handleSummary = summary;
