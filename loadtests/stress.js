// Test 2, stress: one step of the staircase. tools/stress.ps1 runs the steps
// (10, 25, 50, 100, 200, 300, ...) one after another and stops after the first
// step whose p95 exceeds 10 s or whose error rate exceeds 5%.
// Each step: 20 s ramp to VUS, 3 min hold (the measured window), 10 s ramp down.
import { requesterJourney, adminJourney, summary, TREND_STATS } from './lib/common.js';

const VUS = Number(__ENV.VUS || 10);
const admins = Math.max(1, Math.round(VUS * 0.2));
const requesters = VUS - admins;
const stages = (n) => [
    { duration: '20s', target: n },
    { duration: '3m', target: n },
    { duration: '10s', target: 0 },
];

export const options = {
    summaryTrendStats: TREND_STATS,
    scenarios: {
        requesters: { executor: 'ramping-vus', startVUs: 0, stages: stages(requesters), exec: 'requester', gracefulRampDown: '60s' },
        admins: { executor: 'ramping-vus', startVUs: 0, stages: stages(admins), exec: 'admin', gracefulRampDown: '60s' },
    },
};

export function requester() { requesterJourney(); }
export function admin() { adminJourney(); }
export const handleSummary = summary;
