"""Response-time and error statistics for a time window of one k6 run.

Used by tools/stress.ps1 to apply the stop rule to the measured hold window of a
stress step (ramp-up and ramp-down excluded).

Error rate = requests that failed (HTTP error, timeout, connection error, or a
functional failure such as a lost session or a refused submission) / all requests.

Usage: python tools/step_stats.py <run.csv.gz> [skip_s] [window_s]
Prints one JSON object.
"""
import csv
import gzip
import json
import sys

import numpy as np

path = sys.argv[1]
skip = float(sys.argv[2]) if len(sys.argv) > 2 else 0.0
window = float(sys.argv[3]) if len(sys.argv) > 3 else float("inf")

rows = list(csv.DictReader(gzip.open(path, "rt", newline="")))
t0 = min(int(r["timestamp"]) for r in rows if r["metric_name"] == "http_reqs")
lo, hi = t0 + skip, t0 + skip + window

dur, failed, errors = [], 0, 0
for r in rows:
    t = int(r["timestamp"])
    if not lo <= t < hi:
        continue
    m = r["metric_name"]
    if m == "http_req_duration":
        dur.append(float(r["metric_value"]))
    elif m == "http_req_failed":
        failed += int(float(r["metric_value"]))
    elif m == "errors":
        errors += int(float(r["metric_value"]))

d = np.array(dur) if dur else np.array([np.nan])
n = len(dur)
print(json.dumps({
    "requests": n,
    "throughput_rps": round(n / min(window, max(1, hi - lo)), 2) if n else 0,
    "avg_ms": round(float(np.mean(d)), 1), "med_ms": round(float(np.median(d)), 1),
    "p90_ms": round(float(np.percentile(d, 90)), 1), "p95_ms": round(float(np.percentile(d, 95)), 1),
    "max_ms": round(float(np.max(d)), 1),
    "http_failed_pct": round(100 * failed / n, 3) if n else None,
    "error_pct": round(100 * errors / n, 3) if n else None,
}))
