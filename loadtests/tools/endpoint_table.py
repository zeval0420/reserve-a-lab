"""Print per-endpoint status codes, timings and error tags from a k6 CSV(.gz) file.

Usage: python tools/endpoint_table.py results/raw/<run>.csv.gz
"""
import collections
import csv
import gzip
import sys

path = sys.argv[1]
opener = gzip.open if path.endswith(".gz") else open
per = collections.OrderedDict()
errs = collections.Counter()
for r in csv.DictReader(opener(path, "rt", newline="")):
    if r["metric_name"] == "http_req_duration":
        per.setdefault(r["name"], []).append((r["status"], round(float(r["metric_value"]))))
    elif r["metric_name"] == "errors":
        errs[(r["extra_tags"], r["name"])] += 1
for name, v in per.items():
    print(f"{name:58s} n={len(v):<4d} {v[:6]}")
print("errors:", dict(errs) or "none")

outcomes = collections.Counter()
for r in csv.DictReader(opener(path, "rt", newline="")):
    if r["metric_name"] in ("outcome", "approval_outcome"):
        outcomes[r["extra_tags"]] += int(float(r["metric_value"]))
    elif r["metric_name"] == "barrier_skew":
        outcomes[f"skew {r['extra_tags']} <= {round(float(r['metric_value']))} ms (sample)"] += 0
if outcomes:
    print("outcomes:")
    for k, v in sorted(outcomes.items()):
        print(f"  {v:4d}  {k}")
