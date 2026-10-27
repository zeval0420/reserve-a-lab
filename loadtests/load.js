// Test 1, load: ramp to the expected peak, hold for 10 minutes, ramp down.
// 80% of virtual users follow the requester journey, 20% the admin journey.
import { requesterJourney, adminJourney, summary, TREND_STATS } from './lib/common.js';

const PEAK = Number(__ENV.PEAK || 50);
const stages = (share) => [
    { duration: '2m', target: Math.round(PEAK * share) },
    { duration: '10m', target: Math.round(PEAK * share) },
    { duration: '1m', target: 0 },
];

export const options = {
    summaryTrendStats: TREND_STATS,
    scenarios: {
        requesters: { executor: 'ramping-vus', startVUs: 0, stages: stages(0.8), exec: 'requester', gracefulRampDown: '60s' },
        admins: { executor: 'ramping-vus', startVUs: 0, stages: stages(0.2), exec: 'admin', gracefulRampDown: '60s' },
    },
};

export function requester() { requesterJourney(); }
export function admin() { adminJourney(); }
export const handleSummary = summary;
