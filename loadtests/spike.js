// Test 3, spike: 5 users for 1 min (baseline), jump to CAP in 10 s, hold 1 min,
// drop back to 5 in 10 s, then 3 min at 5 users to measure recovery.
import { requesterJourney, adminJourney, summary, TREND_STATS } from './lib/common.js';

const CAP = Number(__ENV.CAP || 100);
const split = (n) => [Math.max(1, Math.round(n * 0.2)), n - Math.max(1, Math.round(n * 0.2))];
const [aBase, rBase] = split(5);
const [aCap, rCap] = split(CAP);
const stages = (base, peak) => [
    { duration: '10s', target: base },
    { duration: '1m', target: base },
    { duration: '10s', target: peak },
    { duration: '1m', target: peak },
    { duration: '10s', target: base },
    { duration: '3m', target: base },
];

export const options = {
    summaryTrendStats: TREND_STATS,
    scenarios: {
        requesters: { executor: 'ramping-vus', startVUs: 0, stages: stages(rBase, rCap), exec: 'requester', gracefulRampDown: '0s' },
        admins: { executor: 'ramping-vus', startVUs: 0, stages: stages(aBase, aCap), exec: 'admin', gracefulRampDown: '0s' },
    },
};

export function requester() { requesterJourney(); }
export function admin() { adminJourney(); }
export const handleSummary = summary;
