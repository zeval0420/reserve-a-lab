"""Turn raw k6 CSV and resource samples into the numbers used by the report.

For every run in results/raw/ it writes results/analysis/<run>.json with:
  * overall and per-endpoint response times (avg, median, p90, p95, p99, max) for the
    measured window, throughput (requests/s), error rate and error types
  * a 10-second time series (VUs, median, p95, requests/s, errors)
  * Apache / MariaDB / k6 CPU and memory (mean, max) for the window, and the samples
  * soak: least-squares trend of per-minute p95 and of process memory against time
  * spike: baseline / spike / recovery response times and the recovery time
Percentiles use linear interpolation (numpy default).

Usage: python analysis/analyze.py [run ...]   (default: every run found)
"""
import csv
import gzip
import json
import pathlib
import sys
from datetime import datetime

import numpy as np
from scipy import stats

ROOT = pathlib.Path(__file__).resolve().parents[1]
RAW = ROOT / "results" / "raw"
OUT = ROOT / "results" / "analysis"

# Measured window per test, in seconds from the first request: (skip, length)
WINDOWS = {"load": (120, 600), "stress": (20, 180), "soak": (60, 1800), "spike": (0, None), "conc": (0, None), "smoke": (0, None)}


def window_for(run):
    return WINDOWS[run.split("_")[0]]


def pct(a, q):
    return round(float(np.percentile(a, q)), 1) if len(a) else None


def describe(d):
    d = np.asarray(d, dtype=float)
    if not len(d):
        return dict(n=0)
    return dict(n=int(len(d)), avg=round(float(d.mean()), 1), med=pct(d, 50), p90=pct(d, 90), p95=pct(d, 95),
                p99=pct(d, 99), max=round(float(d.max()), 1))


def load_run(run):
    rows = list(csv.DictReader(gzip.open(RAW / f"{run}.csv.gz", "rt", newline="")))
    t0 = min(int(r["timestamp"]) for r in rows if r["metric_name"] == "http_reqs")
    return rows, t0


def load_resources(run):
    path = RAW / f"{run}_resources.csv"
    if not path.exists():
        return []
    out = []
    for r in csv.DictReader(open(path, newline="")):
        ts = datetime.fromisoformat(r.pop("timestamp")).timestamp()
        out.append(dict(t=ts, **{k: float(v) for k, v in r.items()}))
    return out


def analyse(run):
    rows, t0 = load_run(run)
    skip, length = window_for(run)
    lo = t0 + skip
    hi = lo + length if length else float("inf")

    reqs, errs, vus_ts = [], [], []
    for r in rows:
        t = int(r["timestamp"])
        m = r["metric_name"]
        if m == "http_req_duration":
            reqs.append((t, r["name"], float(r["metric_value"]), r["status"], r["expected_response"]))
        elif m == "errors":
            tags = dict(kv.split("=", 1) for kv in r["extra_tags"].split("&") if "=" in kv)
            errs.append((t, r["name"], tags.get("type", "?")))
        elif m == "vus":
            vus_ts.append((t, float(r["metric_value"])))

    win = [x for x in reqs if lo <= x[0] < hi]
    werr = [x for x in errs if lo <= x[0] < hi]
    span = (min(hi, max(x[0] for x in reqs) + 1) - lo) if win else 1

    per_ep = {}
    for _, name, dur, status, _ in win:
        per_ep.setdefault(name, []).append(dur)
    ep_errors = {}
    for _, name, typ in werr:
        ep_errors.setdefault(name, {}).setdefault(typ, 0)
        ep_errors[name][typ] += 1
    endpoints = {name: dict(describe(d), errors=sum(ep_errors.get(name, {}).values()), rps=round(len(d) / span, 3))
                 for name, d in sorted(per_ep.items())}
    err_types = {}
    for _, _, typ in werr:
        err_types[typ] = err_types.get(typ, 0) + 1
    statuses = {}
    for x in win:
        statuses[x[3]] = statuses.get(x[3], 0) + 1

    overall = dict(describe([x[2] for x in win]), throughput_rps=round(len(win) / span, 2),
                   errors=len(werr), error_pct=round(100 * len(werr) / len(win), 3) if win else None,
                   error_types=err_types, statuses=statuses, window_s=[skip, length], span_s=span)

    # 10-second series over the whole run
    end = max(x[0] for x in reqs)
    series = []
    for b in range(t0, end + 1, 10):
        d = [x[2] for x in reqs if b <= x[0] < b + 10]
        v = [x[1] for x in vus_ts if b <= x[0] < b + 10]
        e = sum(1 for x in errs if b <= x[0] < b + 10)
        series.append(dict(t=b - t0, vus=max(v) if v else 0, n=len(d), rps=round(len(d) / 10, 2),
                           med=pct(d, 50), p95=pct(d, 95), errors=e))

    res = load_resources(run)
    res_win = [r for r in res if lo <= r["t"] < hi]
    cols = ["httpd_cpu_pct", "httpd_mem_mb", "mysqld_cpu_pct", "mysqld_mem_mb", "k6_cpu_pct", "total_cpu_pct", "available_mem_mb"]
    resources = {c: dict(mean=round(float(np.mean([r[c] for r in res_win])), 2), max=round(float(np.max([r[c] for r in res_win])), 2),
                         min=round(float(np.min([r[c] for r in res_win])), 2)) for c in cols} if res_win else {}
    res_series = [dict(t=round(r["t"] - t0, 1), **{c: r[c] for c in cols}) for r in res]

    out = dict(run=run, overall=overall, endpoints=endpoints, series=series, resources=resources, resource_series=res_series)
    server = ROOT / "results" / f"{run}_server.json"
    if server.exists():
        out["server"] = json.loads(server.read_text(encoding="utf-8-sig"))

    if run.startswith("soak"):
        out["trend"] = soak_trend(win, res_win, lo)
    if run.startswith("spike"):
        out["spike"] = spike_phases(reqs, errs, t0)
    return out


def regress(x, y):
    lr = stats.linregress(x, y)
    return dict(slope_per_min=round(lr.slope, 3), intercept=round(lr.intercept, 2), r2=round(lr.rvalue ** 2, 3), p=lr.pvalue,
                first=round(float(y[0]), 1), last=round(float(y[-1]), 1))


def soak_trend(win, res_win, lo):
    minutes = {}
    for t, _, d, _, _ in win:
        minutes.setdefault(int((t - lo) // 60), []).append(d)
    keys = sorted(k for k in minutes if len(minutes[k]) >= 20)
    p95 = np.array([np.percentile(minutes[k], 95) for k in keys])
    med = np.array([np.median(minutes[k]) for k in keys])
    out = dict(minutes=keys, p95_per_min=[round(float(v), 1) for v in p95], med_per_min=[round(float(v), 1) for v in med],
               p95_trend=regress(np.array(keys, float), p95), med_trend=regress(np.array(keys, float), med))
    if res_win:
        x = np.array([(r["t"] - lo) / 60 for r in res_win])
        for c in ("httpd_mem_mb", "mysqld_mem_mb", "httpd_cpu_pct", "mysqld_cpu_pct"):
            out[f"{c}_trend"] = regress(x, np.array([r[c] for r in res_win]))
        # first vs last 5 minutes
        for c in ("httpd_mem_mb", "mysqld_mem_mb"):
            first = [r[c] for r in res_win if (r["t"] - lo) < 300]
            last = [r[c] for r in res_win if (r["t"] - lo) >= 1500]
            out[f"{c}_first5_last5"] = [round(float(np.mean(first)), 1), round(float(np.mean(last)), 1)]
    return out


def spike_phases(reqs, errs, t0):
    # spike.js: 0-10 s ramp, 10-70 s baseline (5 VUs), 70-80 s jump, 80-140 s peak,
    # 140-150 s drop, 150-330 s recovery at 5 VUs
    def phase(a, b):
        d = [x[2] for x in reqs if t0 + a <= x[0] < t0 + b]
        e = sum(1 for x in errs if t0 + a <= x[0] < t0 + b)
        return dict(describe(d), errors=e, error_pct=round(100 * e / len(d), 2) if d else None, rps=round(len(d) / (b - a), 2))
    out = dict(baseline=phase(10, 70), jump=phase(70, 80), peak=phase(80, 140), drop=phase(140, 150), recovery=phase(150, 330))
    base = out["baseline"]["p95"]
    # Recovery time: first 10 s bin after the drop begins (t >= 140 s) from which the
    # 10 s p95 stays within 1.2 x baseline p95 for 30 s (3 consecutive bins).
    bins = []
    for b in range(140, 330, 10):
        d = [x[2] for x in reqs if t0 + b <= x[0] < t0 + b + 10]
        bins.append((b, float(np.percentile(d, 95)) if d else None))
    def recovery(ref):
        for i in range(len(bins) - 2):
            if all(v is not None and v <= 1.2 * ref for _, v in bins[i:i + 3]):
                return bins[i][0] - 140
        return None

    out["recovery_time_s"] = recovery(base)
    out["recovery_rule"] = "time after the drop begins until the 10-s p95 stays within 1.2x baseline p95 for 30 s"
    out["baseline_bin_p95"] = [round(float(np.percentile([x[2] for x in reqs if t0 + b <= x[0] < t0 + b + 10], 95)), 1)
                               for b in range(10, 70, 10)]
    # Second reference, chosen after seeing the data: at 5 VUs a 60-s baseline holds ~230
    # requests and its p95 depends on how many ~200 ms write calls it happens to contain.
    # The 10-VU stress step's hold window (~1,000 requests) gives a steadier low-load p95.
    ref_path = OUT / "stress_10.json"
    if ref_path.exists():
        ref = json.loads(ref_path.read_text(encoding="utf-8"))["overall"]["p95"]
        out["steady_low_load_p95"] = ref
        out["recovery_time_s_vs_steady"] = recovery(ref)
    out["bins_after_drop"] = [dict(t=b, p95=None if v is None else round(v, 1)) for b, v in bins]
    return out


def main():
    OUT.mkdir(parents=True, exist_ok=True)
    runs = sys.argv[1:] or sorted(p.name[:-7] for p in RAW.glob("*.csv.gz"))
    for run in runs:
        a = analyse(run)
        (OUT / f"{run}.json").write_text(json.dumps(a, indent=1, default=float), encoding="utf-8")
        o = a["overall"]
        print(f"{run:32s} n={o['n']:6d} avg={o.get('avg')} med={o.get('med')} p90={o.get('p90')} p95={o.get('p95')} "
              f"rps={o['throughput_rps']} err={o['error_pct']}% {o['error_types']}")


if __name__ == "__main__":
    main()
