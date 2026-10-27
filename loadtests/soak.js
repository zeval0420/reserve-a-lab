// Test 4, soak: 50% of the stress-test capacity for 30 minutes.
import { requesterJourney, adminJourney, summary, TREND_STATS } from './lib/common.js';

const VUS = Number(__ENV.VUS || 25);
const admins = Math.max(1, Math.round(VUS * 0.2));
const stages = (n) => [
    { duration: '1m', target: n },
    { duration: '30m', target: n },
    { duration: '30s', target: 0 },
];

export const options = {
    summaryTrendStats: TREND_STATS,
    scenarios: {
        requesters: { executor: 'ramping-vus', startVUs: 0, stages: stages(VUS - admins), exec: 'requester', gracefulRampDown: '60s' },
        admins: { executor: 'ramping-vus', startVUs: 0, stages: stages(admins), exec: 'admin', gracefulRampDown: '60s' },
    },
};

export function requester() { requesterJourney(); }
export function admin() { adminJourney(); }
export const handleSummary = summary;
