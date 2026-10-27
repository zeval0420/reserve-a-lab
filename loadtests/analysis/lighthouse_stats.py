"""Paired before/after statistics for the Lighthouse mobile results (Step 4).

Data: the 9 pages of performance-report/index.html (Lighthouse 12.8, mobile preset,
median of 3 runs). Values come from the saved Lighthouse JSON (lighthouse/*.mobile.html),
which match the published medians at the published precision, with one exception:
About "before" LCP, where the saved report is not the median run (14.6 s) and the
published median (17.7 s) is used. The login page is excluded (no "before" value:
Lighthouse NO_FCP), so n = 8 pairs.

For each metric (performance score, LCP, FCP, page weight):
  * differences d = after - before
  * Shapiro-Wilk on d (normality of the differences)
  * paired t-test, mean difference with 95% CI, Cohen's d_z = mean(d) / sd(d)
  * Wilcoxon signed-rank test (exact), matched-pairs rank-biserial correlation r_rb
  * Holm adjustment across the 4 metrics, for each test family
Sensitivity: the same tests on the rounded values exactly as printed in the report table.

Usage: python analysis/lighthouse_stats.py  -> results/lighthouse_stats.json
"""
import json
import pathlib
import re

import numpy as np
from scipy import stats

ROOT = pathlib.Path(__file__).resolve().parents[2]
LH = ROOT / "performance-report" / "lighthouse"
OUT = ROOT / "loadtests" / "results" / "lighthouse_stats.json"

PAGES = [("about", "About"), ("requester_home", "Requester home"), ("forms", "Request form"),
         ("requests", "Request tracker"), ("supervisor_approve", "Teacher approval"),
         ("admin_home", "Admin home"), ("admin_approve", "Admin approvals"), ("inventory", "Inventory")]
METRICS = [("score", "Performance score", "points"), ("lcp", "LCP", "s"), ("fcp", "FCP", "s"), ("weight", "Page weight", "KB")]

# Rounded values as printed in performance-report/index.html (sensitivity analysis)
TABLE = {
    "about": dict(score=(53, 77), fcp=(4.8, 2.4), lcp=(17.7, 5.5), weight=(6.3 * 1024, 979)),
    "requester_home": dict(score=(61, 85), fcp=(4.5, 2.3), lcp=(8.2, 4.0), weight=(1.4 * 1024, 656)),
    "forms": dict(score=(61, 89), fcp=(5.3, 2.7), lcp=(7.5, 3.2), weight=(1.0 * 1024, 346)),
    "requests": dict(score=(64, 88), fcp=(4.5, 2.9), lcp=(6.5, 3.2), weight=(859, 319)),
    "supervisor_approve": dict(score=(63, 92), fcp=(4.8, 2.6), lcp=(6.9, 2.9), weight=(901, 305)),
    "admin_home": dict(score=(61, 84), fcp=(4.6, 2.4), lcp=(8.4, 4.0), weight=(1.4 * 1024, 658)),
    "admin_approve": dict(score=(62, 88), fcp=(4.8, 2.9), lcp=(6.9, 3.2), weight=(902, 332)),
    "inventory": dict(score=(62, 85), fcp=(5.2, 2.9), lcp=(7.2, 3.5), weight=(1008, 380)),
}


def lighthouse(phase, page):
    html = (LH / f"{phase}.{page}.mobile.html").read_text(encoding="utf-8")
    j = json.loads(re.search(r"window\.__LIGHTHOUSE_JSON__ = (\{.*?\});\s*</script>", html, re.S).group(1))
    a = j["audits"]
    return dict(score=round(j["categories"]["performance"]["score"] * 100),
                fcp=a["first-contentful-paint"]["numericValue"] / 1000,
                lcp=a["largest-contentful-paint"]["numericValue"] / 1000,
                weight=a["total-byte-weight"]["numericValue"] / 1024)


def holm(ps):
    order = np.argsort(ps)
    adj, running = [0.0] * len(ps), 0.0
    for rank, i in enumerate(order):
        running = max(running, min(1.0, (len(ps) - rank) * ps[i]))
        adj[i] = running
    return adj


def analyse(data):
    rows = []
    for key, label, unit in METRICS:
        b = np.array([data[p][key][0] for p, _ in PAGES], dtype=float)
        a = np.array([data[p][key][1] for p, _ in PAGES], dtype=float)
        d = a - b
        n = len(d)
        sw = stats.shapiro(d)
        t = stats.ttest_rel(a, b)
        ci = stats.t.interval(0.95, n - 1, loc=d.mean(), scale=stats.sem(d))
        w = stats.wilcoxon(a, b, method="exact")
        ranks = stats.rankdata(np.abs(d))
        w_pos, w_neg = ranks[d > 0].sum(), ranks[d < 0].sum()
        rows.append(dict(
            metric=label, unit=unit, n=n,
            mean_before=b.mean(), mean_after=a.mean(), median_before=float(np.median(b)), median_after=float(np.median(a)),
            mean_diff=d.mean(), sd_diff=d.std(ddof=1), ci95=[ci[0], ci[1]], median_diff=float(np.median(d)),
            shapiro_W=sw.statistic, shapiro_p=sw.pvalue,
            t=t.statistic, df=n - 1, t_p=t.pvalue, cohen_dz=d.mean() / d.std(ddof=1),
            wilcoxon_W=w.statistic, wilcoxon_p=w.pvalue, rank_biserial=(w_pos - w_neg) / (n * (n + 1) / 2),
            pages_improved=int((d > 0).sum() if key == "score" else (d < 0).sum()),
        ))
    for fam, pk in (("t_p_holm", "t_p"), ("wilcoxon_p_holm", "wilcoxon_p")):
        for r, p in zip(rows, holm([r[pk] for r in rows])):
            r[fam] = p
    for r in rows:
        normal = r["shapiro_p"] >= 0.05
        r["recommended"] = "paired t-test" if normal else "Wilcoxon signed-rank"
        r["reason"] = ("differences consistent with normality (Shapiro-Wilk p >= .05)" if normal
                       else "differences not normal (Shapiro-Wilk p < .05)")
    return rows


def main():
    precise = {}
    for p, _ in PAGES:
        bf, af = lighthouse("before", p), lighthouse("after", p)
        precise[p] = {k: (bf[k], af[k]) for k, _, _ in METRICS}
    precise["about"]["lcp"] = (17.7, precise["about"]["lcp"][1])  # published median, see docstring

    main_rows = analyse(precise)
    sens_rows = analyse(TABLE)
    out = dict(
        pages=[lbl for _, lbl in PAGES],
        data={lbl: {k: precise[p][k] for k, _, _ in METRICS} for p, lbl in PAGES},
        results=main_rows,
        sensitivity_rounded_table=[{k: r[k] for k in ("metric", "shapiro_p", "t", "t_p", "wilcoxon_W", "wilcoxon_p", "cohen_dz")} for r in sens_rows],
    )
    OUT.write_text(json.dumps(out, indent=1, default=float), encoding="utf-8")
    for r in main_rows:
        print(f"{r['metric']:18s} n={r['n']} diff={r['mean_diff']:+.2f} {r['unit']} CI[{r['ci95'][0]:+.2f},{r['ci95'][1]:+.2f}] "
              f"SW W={r['shapiro_W']:.3f} p={r['shapiro_p']:.3f} | t({r['df']})={r['t']:.2f} p={r['t_p']:.2g} dz={r['cohen_dz']:.2f} "
              f"| W={r['wilcoxon_W']:.0f} p={r['wilcoxon_p']:.4f} rrb={r['rank_biserial']:.2f} -> {r['recommended']}")
    print("sensitivity (rounded table):")
    for r in sens_rows:
        print(f"  {r['metric']:18s} SW p={r['shapiro_p']:.3f} t p={r['t_p']:.2g} W p={r['wilcoxon_p']:.4f}")


if __name__ == "__main__":
    main()
