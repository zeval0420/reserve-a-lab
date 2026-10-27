"""Build performance-report/load-report.html and performance-report/manuscript-tables.md
from the analysed results (run analysis/analyze.py and analysis/lighthouse_stats.py first).

Usage: python analysis/build_report.py
"""
import csv
import gzip
import html
import json
import pathlib
import re
import urllib.parse
from collections import Counter

from charts import HOVER_JS, bar_chart, line_chart

ROOT = pathlib.Path(__file__).resolve().parents[1]
RES = ROOT / "results"
AN = RES / "analysis"
REPORT_DIR = ROOT.parent / "performance-report"

E = html.escape


def load(name):
    return json.loads((AN / f"{name}.json").read_text(encoding="utf-8"))


def jload(path):
    return json.loads(pathlib.Path(path).read_text(encoding="utf-8-sig"))


# ---------------------------------------------------------------------------
# Data
# ---------------------------------------------------------------------------
LOAD = load("load_50")
STRESS_STEPS = [10, 25, 50, 100, 200]
STRESS = {v: load(f"stress_{v}") for v in STRESS_STEPS}
SPIKE = load("spike_200")
SOAK = load("soak_100")
CAP = jload(RES / "stress_capacity.json")
LH = jload(RES / "lighthouse_stats.json")
# Concurrency results: the original runs (before the fixes) and the re-runs after them
# (tools/concurrency.ps1 -Tag after writes phase = "after" and "_after" in the name).
CONC = {}
AFTER = {}
for p in sorted(RES.glob("conc_*_db.json")):
    d = jload(p)
    (AFTER if d.get("phase") == "after" else CONC).setdefault(f"{d['test']}_{d.get('mode', 'race')}", []).append(d)
for v in list(CONC.values()) + list(AFTER.values()):
    v.sort(key=lambda d: d["rep"])
EVIDENCE = {p.name[:-7]: p.read_text(encoding="utf-8-sig") for p in RES.glob("conc_*_db.txt") if "_after_" not in p.name}
AFTER_EVIDENCE = {p.name[:-7]: p.read_text(encoding="utf-8-sig") for p in RES.glob("conc_*_after_*_db.txt")}


def outcome_tally(run, metric="outcome"):
    """Count the tagged outcome counters (result=approved / refused_by_server / ...) in a run's raw k6 CSV."""
    c = Counter()
    path = RES / "raw" / f"{run}.csv.gz"
    if not path.exists():
        return c
    for r in csv.DictReader(gzip.open(path, "rt", newline="")):
        if r["metric_name"] == metric:
            tags = urllib.parse.parse_qs(r["extra_tags"])
            if "result" in tags:
                c[tags["result"][0]] += float(r["metric_value"])
    return c


LOAD_AFTER_PATH = AN / "load_50_after.json"
LOAD_AFTER = load("load_50_after") if LOAD_AFTER_PATH.exists() else None

ENDPOINT_LABELS = {
    "GET index.php": "Login page",
    "POST ajax_login.php loginUser": "Log in (AJAX)",
    "GET requester_home.php": "Requester home",
    "POST store_date.php": "Home: store date",
    "POST ajax_requester.php get_day_snapshot": "Home: day snapshot",
    "GET forms.php": "Request form",
    "POST ajax_forms.php get_disabled_dates": "Form: disabled dates",
    "POST ajax_forms.php get_subjects_by_grade": "Form: subjects",
    "GET ajax_forms.php get_sections": "Form: sections",
    "POST ajax_forms.php check_conflict": "Conflict check",
    "POST ajax_supervisor_action.php request_submission": "Submit request",
    "GET requests.php": "Request tracker",
    "GET logout.php": "Log out",
    "GET admin_home.php": "Admin home",
    "POST ajax_admin.php get_availability": "Admin: lab availability",
    "GET ajax_admin.php notification_feed": "Admin: notifications",
    "GET ajax_admin.php get_pending_count": "Admin: pending count",
    "GET admin_approve.php": "Admin approvals",
    "POST ajax_admin_action.php force_approve": "Approve request",
    "GET inventory.php": "Inventory",
}
ORDER = list(ENDPOINT_LABELS)


def ms(v, digits=0):
    if v is None:
        return "–"
    return f"{v / 1000:.2f} s" if v >= 1000 else f"{v:.{digits}f} ms"


def nielsen(v):
    """Nielsen band for a response time in ms."""
    if v is None:
        return ""
    if v <= 100:
        return "≤ 0.1 s"
    if v <= 1000:
        return "≤ 1 s"
    if v <= 10000:
        return "≤ 10 s"
    return "> 10 s"


def band_pill(v):
    b = nielsen(v)
    cls = {"≤ 0.1 s": "good", "≤ 1 s": "good", "≤ 10 s": "ok", "> 10 s": "bad"}.get(b, "")
    return f'<span class="pill {cls}">{b}</span>'


def pf(ok):
    return '<span class="pill good">PASS</span>' if ok else '<span class="pill bad">FAIL</span>'


def endpoint_rows(run):
    eps = run["endpoints"]
    rows = []
    for k in ORDER:
        if k in eps:
            rows.append((k, eps[k]))
    return rows


# ---------------------------------------------------------------------------
# Derived numbers used in the text
# ---------------------------------------------------------------------------
lo = LOAD["overall"]
st200 = STRESS[200]["overall"]
worst_ep = max(STRESS[200]["endpoints"].items(), key=lambda kv: kv[1]["p95"])
slot_race = CONC.get("slot_race", [])
slot_seq = CONC.get("slot_sequential", [])
inv_race = CONC.get("inventory_race", [])
inv_seq = CONC.get("inventory_sequential", [])
manual = CONC.get("manual_race", [])
neg_reps = sum(1 for d in inv_race if d["went_negative"])
lh = {r["metric"]: r for r in LH["results"]}
sp = SPIKE["spike"]
so = SOAK["overall"]
tr = SOAK.get("trend", {})


def emails(run, kind):
    return run.get("server", {}).get("php_log_types", {}).get(kind, 0)


def stat_line(r):
    """One-line statistics for the manuscript text."""
    pe = lambda p: fmt_p(p) if p < 0.001 else "= " + fmt_p(p)
    return (f"t({r['df']}) = {r['t']:.2f}, p {pe(r['t_p'])}, d_z = {r['cohen_dz']:.2f}; "
            f"Wilcoxon W = {r['wilcoxon_W']:.0f}, p {pe(r['wilcoxon_p'])}, r_rb = {r['rank_biserial']:.2f}")


def fmt_p(p):
    if p < 0.001:
        return "< .001"
    return (f"{p:.4f}" if p < 0.01 else f"{p:.3f}").lstrip("0")


# ---------------------------------------------------------------------------
# HTML pieces
# ---------------------------------------------------------------------------
def table(headers, rows, cls=""):
    th = "".join(f"<th>{h}</th>" for h in headers)
    body = "".join("<tr>" + "".join(f"<td>{c}</td>" if i else f"<th scope=\"row\">{c}</th>" for i, c in enumerate(r)) + "</tr>" for r in rows)
    return f'<div class="tablewrap"><table class="{cls}"><thead><tr>{th}</tr></thead><tbody>{body}</tbody></table></div>'


def ep_table(run):
    rows = []
    for k, v in endpoint_rows(run):
        rows.append([E(ENDPOINT_LABELS[k]) + f' <span class="note">{E(k)}</span>', f"{v['n']:,}", ms(v["avg"]), ms(v["med"]),
                     ms(v["p90"]), f"<b>{ms(v['p95'])}</b>", ms(v["max"]), f"{v['rps']:.2f}", str(v["errors"]), band_pill(v["p95"])])
    o = run["overall"]
    rows.append(["<b>All requests</b>", f"<b>{o['n']:,}</b>", f"<b>{ms(o['avg'])}</b>", f"<b>{ms(o['med'])}</b>", f"<b>{ms(o['p90'])}</b>",
                 f"<b>{ms(o['p95'])}</b>", f"<b>{ms(o['max'])}</b>", f"<b>{o['throughput_rps']:.2f}</b>", f"<b>{o['errors']}</b>", band_pill(o["p95"])])
    return table(["Endpoint", "Requests", "Average", "Median", "p90", "p95", "Max", "Req/s", "Errors", "Nielsen band (p95)"], rows)


def res_table(runs):
    rows = []
    for label, run in runs:
        r = run["resources"]
        rows.append([E(label), f"{r['httpd_cpu_pct']['mean']:.1f}% / {r['httpd_cpu_pct']['max']:.1f}%",
                     f"{r['httpd_mem_mb']['mean']:.0f} / {r['httpd_mem_mb']['max']:.0f} MB",
                     f"{r['mysqld_cpu_pct']['mean']:.1f}% / {r['mysqld_cpu_pct']['max']:.1f}%",
                     f"{r['mysqld_mem_mb']['mean']:.0f} / {r['mysqld_mem_mb']['max']:.0f} MB",
                     f"{r['k6_cpu_pct']['mean']:.1f}% / {r['k6_cpu_pct']['max']:.1f}%",
                     f"{r['total_cpu_pct']['mean']:.1f}% / {r['total_cpu_pct']['max']:.1f}%"])
    return table(["Run", "Apache CPU (mean / max)", "Apache memory", "MariaDB CPU", "MariaDB memory", "k6 CPU", "Whole machine CPU"], rows)


def series_chart(run, title, t_max=None, log_y=False):
    s = [p for p in run["series"] if p["n"] and (t_max is None or p["t"] <= t_max)]
    mins = lambda t: f"{int(t // 60)}:{int(t % 60):02d}"
    c1 = line_chart([dict(name="p95", cls="s1", points=[(p["t"], p["p95"]) for p in s]),
                     dict(name="Median", cls="s2", points=[(p["t"], p["med"]) for p in s])],
                    x_label="Elapsed time (min:s), 10-second bins", y_unit="ms", title=title, x_fmt=mins, log_y=log_y)
    c2 = line_chart([dict(name="Virtual users", cls="s3", points=[(p["t"], p["vus"]) for p in s])],
                    x_label="Elapsed time (min:s)", y_unit="", title="Active virtual users", x_fmt=mins, height=150)
    return c1 + '<p class="note">Active virtual users over the same time axis:</p>' + c2


CSS = """
:root{--bg:#f6f7fb;--card:#fff;--ink:#14182b;--muted:#5b6478;--line:#e3e6ef;--accent:#0036af;--good:#137a3f;--good-bg:#e5f5ec;--ok:#8a5a00;--ok-bg:#fdf1d9;--bad:#b42318;--bad-bg:#fde8e7;--s1:#2a78d6;--s2:#eb6834;--s3:#5b6478}
@media (prefers-color-scheme:dark){:root:not([data-theme="light"]){--bg:#0f1220;--card:#171b2e;--ink:#e8eaf3;--muted:#a1a8bd;--line:#2a3050;--accent:#7aa2ff;--good:#5fd28f;--good-bg:#12301f;--ok:#f2c05c;--ok-bg:#33270c;--bad:#ff8a80;--bad-bg:#3a1513;--s1:#3987e5;--s2:#d95926;--s3:#a1a8bd}}
:root[data-theme="dark"]{--bg:#0f1220;--card:#171b2e;--ink:#e8eaf3;--muted:#a1a8bd;--line:#2a3050;--accent:#7aa2ff;--good:#5fd28f;--good-bg:#12301f;--ok:#f2c05c;--ok-bg:#33270c;--bad:#ff8a80;--bad-bg:#3a1513;--s1:#3987e5;--s2:#d95926;--s3:#a1a8bd}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--ink);font:15px/1.55 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}
main{max-width:1180px;margin:0 auto;padding:32px 16px 64px}
h1{font-size:28px;margin:0 0 4px}h2{font-size:20px;margin:40px 0 10px}h3{font-size:16px;margin:20px 0 8px}
.sub{color:var(--muted);margin:0 0 24px}
.kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px}
.kpi{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:16px}
.kpi .k{color:var(--muted);font-size:13px}.kpi .v{font-size:26px;font-weight:700;margin-top:4px}.kpi .v small{font-size:15px;color:var(--muted);font-weight:500}
.card{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:16px;margin-top:12px}
.tablewrap{overflow-x:auto}table{border-collapse:collapse;width:100%;min-width:760px;font-size:14px}
th,td{padding:8px 10px;border-bottom:1px solid var(--line);text-align:left;white-space:nowrap}thead th{color:var(--muted);font-weight:600;font-size:13px}
tbody th{font-weight:600}td b{font-weight:650}
.pill{display:inline-block;min-width:34px;text-align:center;padding:1px 8px;border-radius:999px;font-weight:650;font-size:13px}
.pill.good{background:var(--good-bg);color:var(--good)}.pill.ok{background:var(--ok-bg);color:var(--ok)}.pill.bad{background:var(--bad-bg);color:var(--bad)}
a{color:var(--accent)}ul{margin:6px 0 0;padding-left:20px}li{margin:4px 0}code{font-size:13px;background:var(--bg);padding:1px 5px;border-radius:4px}
.note{color:var(--muted);font-size:13px}
details{background:var(--card);border:1px solid var(--line);border-radius:12px;margin-top:10px}summary{cursor:pointer;padding:12px 16px;font-weight:600}
details pre{margin:0;padding:0 16px 16px;overflow-x:auto;font-size:12px;line-height:1.4}
table.wrap td{white-space:normal;vertical-align:top}
.verdict{border-left:4px solid var(--bad);padding:10px 14px;background:var(--bad-bg);border-radius:8px;margin:12px 0}
.verdict.good{border-color:var(--good);background:var(--good-bg)}
svg{width:100%;height:auto;display:block}svg .grid{stroke:var(--line)}svg .axis,svg .val{fill:var(--muted);font-size:11px}svg .val.strong{fill:var(--ink);font-weight:650}svg .lbl{fill:var(--ink);font-size:12px}
svg .ln{fill:none;stroke-width:2;stroke-linejoin:round;stroke-linecap:round}svg .ln.s1{stroke:var(--s1)}svg .ln.s2{stroke:var(--s2)}svg .ln.s3{stroke:var(--s3)}
svg .dot{stroke:var(--card);stroke-width:2}svg .dot.s1{fill:var(--s1)}svg .dot.s2{fill:var(--s2)}svg .dot.s3{fill:var(--s3)}
svg .bar.s1{fill:var(--s1)}svg .bar.bad{fill:var(--bad)}svg .bar.good{fill:var(--good)}
svg .ref{stroke:var(--muted);stroke-dasharray:4 4}svg .hit{fill:transparent}svg .xhair{stroke:var(--muted);display:none}
.legend{display:flex;gap:16px;color:var(--muted);font-size:13px;margin-bottom:8px}.legend i,#tip i{display:inline-block;width:12px;height:3px;border-radius:2px;margin-right:6px;vertical-align:3px}
.legend i.s1,#tip i.s1{background:var(--s1)}.legend i.s2,#tip i.s2{background:var(--s2)}.legend i.s3,#tip i.s3{background:var(--s3)}
#tip{position:fixed;display:none;pointer-events:none;background:var(--card);color:var(--ink);border:1px solid var(--line);border-radius:8px;padding:8px 10px;font-size:13px;box-shadow:0 4px 16px rgba(0,0,0,.15);z-index:10}
@media (max-width:640px){h1{font-size:23px}}
"""


def build_html(T):
    return f"""<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Load Test Report</title>
<style>{CSS}</style></head><body><main>
{T}
<div id="tip"></div>
<script>{HOVER_JS}</script>
</main></body></html>
"""


if __name__ == "__main__":
    from report_text import html_body, markdown

    (REPORT_DIR / "load-report.html").write_text(build_html(html_body(globals())), encoding="utf-8")
    (REPORT_DIR / "manuscript-tables.md").write_text(markdown(globals()), encoding="utf-8")
    print("wrote", REPORT_DIR / "load-report.html", "and", REPORT_DIR / "manuscript-tables.md")
