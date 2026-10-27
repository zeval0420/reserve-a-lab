// Smoke check: one requester journey and one admin journey, to validate the
// scripts and the endpoints before any load is applied.
import { requesterJourney, adminJourney, summary, TREND_STATS } from './lib/common.js';

export const options = {
    summaryTrendStats: TREND_STATS,
    scenarios: {
        requester: { executor: 'per-vu-iterations', vus: 1, iterations: 2, exec: 'requester' },
        admin: { executor: 'per-vu-iterations', vus: 1, iterations: 1, exec: 'admin', startTime: '3s' },
    },
};

export function requester() { requesterJourney(); }
export function admin() { adminJourney(); }
export const handleSummary = summary;
