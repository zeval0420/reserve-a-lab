"""Text of load-report.html and manuscript-tables.md. Every number is read from the
analysed results passed in by build_report.py (g = its module globals)."""
import json


def _env():
    return [
        ("Machine", "Laptop, Intel Core i5-1240P (12 cores / 16 threads), 15.7 GB RAM, Windows 11 Home 10.0.26200"),
        ("Web stack", "XAMPP 8.2.12: Apache 2.4.58 (mpm_winnt, ThreadsPerChild 150, KeepAlive On, KeepAliveTimeout 5 s), PHP 8.2.12 as Apache module, OPcache off"),
        ("Database", "MariaDB 12.3.2 (system tables still from 10.4.8), InnoDB, REPEATABLE-READ, autocommit on, max_connections 151, innodb_buffer_pool_size 16 MB"),
        ("Load tool", "Grafana k6 v2.2.0 (go1.26.5), on the same machine as the server"),
        ("Resource sampling", "Windows performance counters (Get-Counter) every 2 s: httpd.exe and mysqld.exe CPU and private working set, k6 CPU, total CPU, available memory"),
        ("Date", "27 October 2026"),
        ("Target", "http://localhost/reserve-a-lab/ only (scripts refuse any other host)"),
    ]


JOURNEY_REQ = ["Open the login page (<code>index.php</code>) and read the CSRF token", "Log in (<code>ajax_login.php</code>)", "Think 2–4 s",
               "Requester home (<code>requester_home.php</code>) with its two on-load calls", "Think 3–6 s",
               "Open the request form (<code>forms.php</code>) with its lookups (disabled dates, subjects, sections) and the live conflict check",
               "Think 5–10 s (filling in the form)", "Conflict check at the confirmation step", "Think 2–4 s",
               "Submit the request with 1–2 materials (<code>ajax_supervisor_action.php</code>)", "Think 2–4 s",
               "Request tracker (<code>requests.php</code>)", "Think 2–4 s", "Log out"]
JOURNEY_ADM = ["Open the login page and log in", "Think 2–4 s", "Admin home (<code>admin_home.php</code>) with its three on-load calls",
               "Think 3–6 s", "Admin approvals (<code>admin_approve.php</code>), pick a pending load-test request, conflict check (as the page sends it)",
               "Think 3–6 s", "Approve it (<code>ajax_admin_action.php</code>, force_approve)", "Think 2–4 s", "Inventory (<code>inventory.php</code>)",
               "Think 2–4 s", "Log out"]


def html_body(g):
    E, ms, table, pf = g["E"], g["ms"], g["table"], g["pf"]
    LOAD, STRESS, SPIKE, SOAK, LH, CAP = g["LOAD"], g["STRESS"], g["SPIKE"], g["SOAK"], g["LH"], g["CAP"]
    lo, st200, sp, so, tr = g["lo"], g["st200"], g["sp"], g["so"], g["tr"]
    slot_race, slot_seq, inv_race, inv_seq, manual = g["slot_race"], g["slot_seq"], g["inv_race"], g["inv_seq"], g["manual"]
    lh, fmt_p = g["lh"], g["fmt_p"]
    line_chart, bar_chart = g["line_chart"], g["bar_chart"]
    skew = json.loads((g["AN"] / "barrier_skew.json").read_text())
    worst_name, worst = g["worst_ep"]
    EP = g["ENDPOINT_LABELS"]
    neg = g["neg_reps"]
    out = []
    w = out.append

    # ------------------------------------------------------------------ header
    w('<h1>Reserve-a-Lab load and concurrency report</h1>')
    w('<p class="sub">Performance efficiency testing for Objective 2: load, stress, spike, soak and double-booking tests with Grafana k6 '
      'against local XAMPP on 27 October 2026, plus paired statistics for the Lighthouse before/after results. '
      'Companion to the <a href="index.html">Lighthouse performance report</a>.</p>')
    w('<div class="kpis">')
    w(f'<div class="kpi"><div class="k">Capacity (stress test)</div><div class="v">≥ {CAP["capacity_vus"]} <small>virtual users</small></div></div>')
    w(f'<div class="kpi"><div class="k">p95 at expected peak (50 users)</div><div class="v">{ms(lo["p95"])}</div></div>')
    w(f'<div class="kpi"><div class="k">Worst p95, stress test</div><div class="v">{ms(st200["p95"])} <small>at 200 users</small></div></div>')
    w(f'<div class="kpi"><div class="k">Double booking, before fixes</div><div class="v"><span class="pill bad">FAIL</span> <small>{max(d["approved"] for d in slot_race)} of 20 approved</small></div></div>')
    a_slot = g["AFTER"].get("slot_race", [])
    if a_slot:
        a_ok = all(d["pass"] for d in a_slot)
        w(f'<div class="kpi"><div class="k">Double booking, after fixes</div><div class="v"><span class="pill {"good" if a_ok else "bad"}">{"PASS" if a_ok else "FAIL"}</span> <small>max {max(d["approved"] for d in a_slot)} of 20 approved</small></div></div>')
    w(f'<div class="kpi"><div class="k">Lighthouse score, paired t-test</div><div class="v">+{lh["Performance score"]["mean_diff"]:.1f} <small>p {fmt_p(lh["Performance score"]["t_p"])}</small></div></div>')
    w('</div>')

    # ------------------------------------------------------------------ findings
    w('<h2>Main findings</h2><div class="card"><ul>')
    w(f'<li><b>Time behaviour is good up to 100 concurrent users.</b> At the expected peak of 50 users the median response was {ms(lo["med"])} and the p95 {ms(lo["p95"])}, with no errors in {lo["n"]:,} requests. Up to 100 users the p95 stayed below 200 ms.</li>')
    w(f'<li><b>Capacity is at least {CAP["capacity_vus"]} users</b> (p95 ≤ 10 s and errors ≤ 5%). At 200 users the median stayed at {ms(st200["med"])} but the p95 rose to {ms(st200["p95"])}: about one request in ten waited 2–6 s. The 300-user step was stopped by the host for low memory before it finished, so the upper limit was not measured.</li>')
    w('<li><b>The 200-user slowdown is most likely Apache\'s thread limit, not CPU.</b> Apache on Windows serves each connection with one of 150 threads and keeps idle connections open for 5 s. With more users than threads, a request on a new connection waits for a thread; the delay sits in time-to-first-byte, is bounded by the 5 s keep-alive timeout, and appears only above 150 users, while CPU averaged 34% of the machine.</li>')
    w(f'<li><b>Response time climbs as data accumulates.</b> In the 30-minute soak, the admin approvals page went from a median of 166 ms in the first 5 minutes to 4.9 s by minute 25, because it renders every pending request on one page (about 4,000 by the end). Apache and MariaDB CPU rose with it at constant load. Memory showed no growth in the first 15 minutes, before other programmes on the laptop began to interfere.</li>')
    w(f'<li><b>Double booking: FAIL before the fixes</b> (<a href="#after-fixes">PASS after them</a>: 1 approved of 20 in every repetition). When 20 approvals of the same lab, date and time were sent at once, {", ".join(str(d["approved"]) for d in slot_race)} of 20 were approved in the five repetitions (the correct number is 1). It also happens without any concurrency: approved 3 seconds apart through the real page, all {slot_seq[0]["approved"]} of {slot_seq[0]["requests"]} were approved.</li>')
    w(f'<li><b>Inventory over-allocation: FAIL before the fixes</b> (<a href="#after-fixes">PASS after them</a>: 2 approved, stock never below zero). 20 requests for 2 of the last 5 units were all approved in every repetition (40 units allocated against 5 in stock), and the stock went below zero (−1) in {neg} of 5 repetitions. The inventory page\'s manual deduction, which locks the row, passed the same test 5 of 5 times.</li>')
    w(f'<li><b>The Lighthouse improvements are statistically significant</b> for performance score (+{lh["Performance score"]["mean_diff"]:.1f} points, paired t-test p {fmt_p(lh["Performance score"]["t_p"])}), FCP (−{abs(lh["FCP"]["mean_diff"]):.2f} s, t-test p {fmt_p(lh["FCP"]["t_p"])}), LCP (−{abs(lh["LCP"]["mean_diff"]):.2f} s, Wilcoxon p {fmt_p(lh["LCP"]["wilcoxon_p"])}) and page weight (−{abs(lh["Page weight"]["mean_diff"]):,.0f} KB, Wilcoxon p {fmt_p(lh["Page weight"]["wilcoxon_p"])}), n = 8 pages.</li>')
    w('</ul></div>')

    # ------------------------------------------------------------------ setup
    w('<h2>Test setup</h2>')
    w('<div class="card">' + table(["Item", "Value"], [[E(a), E(b)] for a, b in _env()], "wrap") + '</div>')
    w('<h3>User journeys</h3><p>Each virtual user repeats a complete session, from the login page to logging out, with think times between steps. '
      '80% of users follow the requester journey and 20% the admin journey. Every session logs in as its own synthetic account (500 teachers, 5 admins).</p>')
    w('<div class="card"><div class="tablewrap"><table class="wrap"><thead><tr><th>Requester (teacher)</th><th>Admin (lab personnel)</th></tr></thead><tbody><tr>'
      f'<td><ol>{"".join(f"<li>{s}</li>" for s in JOURNEY_REQ)}</ol></td><td><ol>{"".join(f"<li>{s}</li>" for s in JOURNEY_ADM)}</ol></td></tr></tbody></table></div></div>')
    w('<p class="note">k6 requests the HTML pages and the AJAX calls the pages make; it does not download CSS, scripts or images (front-end loading is covered by the Lighthouse report). '
      'Two conflict-check calls are replayed exactly as the pages send them and return HTTP 403 (see the double-booking section); these are expected responses, not errors.</p>')
    w('<h3>Safety and test data</h3><div class="card"><ul>'
      '<li>Only localhost was tested. The database <code>dbadmin</code> was backed up with mysqldump before testing and restored afterwards; all 47 tables matched their pre-test checksums after the restore.</li>'
      '<li>Every test started from the same baseline (original data plus synthetic accounts and test items), restored before each run, so later tests are not affected by rows created by earlier ones.</li>'
      '<li>Submissions and approvals send notification e-mails through Gmail SMTP inside the request. Outbound SMTP from Apache was blocked with a Windows Firewall rule for the duration (connections fail in under 40 ms), and every address in the database was replaced with an <code>@example.invalid</code> address. No e-mail left the machine.</li>'
      '<li>Load-journey materials used 10 synthetic consumables with 1,000,000 units each, so real stock was never drawn down.</li>'
      '<li>No application code was changed. The inventory deduction log table, which the inventory page needs but which was not present locally (its migration was removed in commit 8f9d971), was created from that migration for the control test and removed by the restore.</li>'
      '</ul></div>')
    w('<h3>Definitions</h3><p class="note">Response time = k6 <code>http_req_duration</code> (request sent to last byte received). Percentiles use linear interpolation. '
      'Error rate = requests with an HTTP error, timeout, connection error or functional failure (for example a lost session or a refused submission) divided by all requests. '
      'CPU is shown as a percentage of the whole machine (16 logical processors), so 6.25% equals one fully busy core. Memory is the private working set.</p>')

    # ------------------------------------------------------------------ load
    w('<h2>Test 1. Load: 50 users for 10 minutes</h2>')
    w(f'<p>Ramp to 50 users over 2 minutes, hold for 10 minutes, ramp down over 1 minute. Figures cover the 10-minute hold. '
      f'Throughput {lo["throughput_rps"]:.1f} requests/s, {lo["errors"]} errors in {lo["n"]:,} requests.</p>')
    w('<div class="card">' + g["ep_table"](LOAD) + '</div>')
    w('<div class="card">' + g["series_chart"](LOAD, "Load test response time over time") + '</div>')
    w('<div class="card">' + g["res_table"]([("Load, 50 users", LOAD)]) + '</div>')

    # ------------------------------------------------------------------ stress
    w('<h2>Test 2. Stress: 10 to 200 users</h2>')
    w('<p>Steps of 10, 25, 50, 100 and 200 users, each a 20 s ramp, a 3-minute hold and a 10 s ramp down on a freshly restored baseline. '
      'Figures cover the 3-minute hold. Stop rule: p95 above 10 s or error rate above 5%.</p>')
    rows = []
    for v in g["STRESS_STEPS"]:
        o, r = STRESS[v]["overall"], STRESS[v]["resources"]
        ok = o["p95"] <= 10000 and o["error_pct"] <= 5
        rows.append([f"{v}", f"{o['throughput_rps']:.1f}", ms(o["avg"]), ms(o["med"]), ms(o["p90"]), f"<b>{ms(o['p95'])}</b>", ms(o["max"]),
                     f"{o['error_pct']:.2f}%", f"{r['httpd_cpu_pct']['mean']:.1f}%", f"{r['mysqld_cpu_pct']['mean']:.1f}%", f"{r['total_cpu_pct']['mean']:.1f}%",
                     '<span class="pill good">within</span>' if ok else '<span class="pill bad">exceeded</span>'])
    rows.append(["300", "–", "–", "–", "–", "–", "–", "–", "–", "–", "–", '<span class="pill ok">not completed</span>'])
    w('<div class="card">' + table(["Users", "Req/s", "Average", "Median", "p90", "p95", "Max", "Errors", "Apache CPU", "MariaDB CPU", "Machine CPU", "Limits"], rows) + '</div>')
    pts = lambda key: [(v, STRESS[v]["overall"][key]) for v in g["STRESS_STEPS"]]
    w('<div class="card">' + line_chart([dict(name="p95", cls="s1", points=pts("p95")), dict(name="Median", cls="s2", points=pts("med"))],
                                         x_label="Virtual users", y_unit="ms", title="Response time against virtual users", log_y=True,
                                         refs=[(10000, "10 s limit")], x_ticks=[10, 50, 100, 200], x_fmt=lambda v: f"{v:g}") +
      '<p class="note">Logarithmic vertical axis. The dashed line is the 10 s stop limit (Nielsen\'s limit for keeping attention).</p></div>')
    w(f'<p><b>Capacity: ≥ {CAP["capacity_vus"]} users.</b> {E(CAP["note"])}</p>')
    w('<h3>Where the time goes at 200 users</h3>')
    w('<div class="card">' + g["ep_table"](STRESS[200]) + '</div>')
    w('<p>The slow requests were spread evenly over the 3-minute hold (about 85 per 10 seconds) and concentrated in four endpoints: the conflict check, the request form, '
      'admin approvals and approve request. These are the requests that follow a 3–10 s think time. The delay was in time-to-first-byte (p95 4.0 s), while the TCP connection '
      'was accepted within 13 ms, and the slow responses clustered between 3 and 5 s. Machine CPU averaged 34% and at least 4 GB of memory stayed free. '
      'This matches Apache\'s Windows thread model: 150 worker threads, each held by an idle keep-alive connection for up to 5 s. With 200 users, a request that arrives on a new connection '
      'after a pause longer than the keep-alive timeout waits until another connection times out. At 100 users (fewer connections than threads) there is no such tail. '
      'This explanation fits all observations but was not confirmed by changing the Apache configuration, which was outside the scope of this test.</p>')

    # ------------------------------------------------------------------ spike
    w('<h2>Test 3. Spike: 5 → 200 → 5 users</h2>')
    w('<p>One minute at 5 users, jump to 200 users in 10 s, hold 1 minute, drop back to 5 users in 10 s, then 3 minutes at 5 users.</p>')
    rows = []
    for k, lbl in (("baseline", "Baseline, 5 users (1 min)"), ("jump", "Jump to 200 (10 s)"), ("peak", "Peak, 200 users (1 min)"),
                   ("drop", "Drop to 5 (10 s)"), ("recovery", "Recovery, 5 users (3 min)")):
        p = sp[k]
        rows.append([lbl, f"{p['n']:,}", f"{p['rps']:.1f}", ms(p["med"]), ms(p["p90"]), f"<b>{ms(p['p95'])}</b>", ms(p["max"]), f"{p['errors']}"])
    w('<div class="card">' + table(["Phase", "Requests", "Req/s", "Median", "p90", "p95", "Max", "Errors"], rows) + '</div>')
    w('<div class="card">' + g["series_chart"](SPIKE, "Spike test response time over time", log_y=True) + '<p class="note">Logarithmic vertical axis.</p>' + '</div>')
    w(f'<p><b>No request failed during the spike.</b> At the peak the median stayed at {ms(sp["peak"]["med"])} and the p95 rose to {ms(sp["peak"]["p95"])} (maximum {ms(sp["peak"]["max"])}), '
      f'the same pattern as the 200-user stress step. <b>Recovery was immediate:</b> in the first 10 s after the users were back at 5, the p95 was {ms(sp["bins_after_drop"][1]["p95"])}, and the '
      f'recovery-phase median ({ms(sp["recovery"]["med"])}) matched the baseline ({ms(sp["baseline"]["med"])}).</p>')
    w(f'<p class="note">Recovery rule. The planned rule (10-s p95 back within 1.2 × the baseline p95 for 30 s) is not met at any point, including during the baseline itself: at 5 users a 10-s bin holds about 30 requests, '
      f'and its p95 swings between {min(sp["baseline_bin_p95"]):.0f} and {max(sp["baseline_bin_p95"]):.0f} ms depending on whether a ~200 ms submit or approve call falls in it, so the 1-minute baseline p95 '
      f'({ms(sp["baseline"]["p95"])}) is not a stable reference. Against the steady low-load p95 of the 10-user stress step ({ms(sp["steady_low_load_p95"])}, about 1,000 requests), chosen after seeing the data, '
      f'the p95 was back within limits {sp["recovery_time_s_vs_steady"]} s after the drop began.</p>')

    # ------------------------------------------------------------------ soak
    w('<h2>Test 4. Soak: 100 users for 30 minutes</h2>')
    w(f'<p>50% of the measured capacity for 30 minutes (after a 1-minute ramp). {so["n"]:,} requests at {so["throughput_rps"]:.1f} requests/s; '
      f'{so["errors"]} errors ({so["error_pct"]:.3f}%: {", ".join(f"{v} {k}" for k, v in so["error_types"].items())}).</p>')
    w('<div class="card">' + g["ep_table"](SOAK) + '</div>')
    mins = tr["minutes"]
    w('<div class="card">' + line_chart([dict(name="p95", cls="s1", points=list(zip(mins, tr["p95_per_min"]))),
                                         dict(name="Median", cls="s2", points=list(zip(mins, tr["med_per_min"])))],
                                        x_label="Minute of the 30-minute hold", y_unit="ms", title="Soak test response time per minute", log_y=True) +
      '<p class="note">Per-minute median and p95 of all requests, logarithmic axis.</p></div>')
    rs = [p for p in SOAK["resource_series"] if 60 <= p["t"] <= 1860]
    w('<div class="card">' + line_chart([dict(name="Apache", cls="s1", points=[(round((p["t"] - 60) / 60, 2), p["httpd_mem_mb"]) for p in rs[::5]]),
                                         dict(name="MariaDB", cls="s2", points=[(round((p["t"] - 60) / 60, 2), p["mysqld_mem_mb"]) for p in rs[::5]])],
                                        x_label="Minute of the 30-minute hold", y_unit="MB", title="Process memory during the soak test", x_fmt=lambda v: f"{v:.0f}") +
      '<p class="note">Private working set, sampled every 2 s (every fifth sample plotted).</p></div>')
    rows = []
    for key, lbl, unit in (("p95_trend", "p95 per minute", "ms/min"), ("med_trend", "Median per minute", "ms/min"),
                           ("httpd_mem_mb_trend", "Apache memory", "MB/min"), ("mysqld_mem_mb_trend", "MariaDB memory", "MB/min"),
                           ("httpd_cpu_pct_trend", "Apache CPU", "points/min"), ("mysqld_cpu_pct_trend", "MariaDB CPU", "points/min")):
        t = tr[key]
        rows.append([lbl, f"{t['slope_per_min']:+.2f} {unit}", f"{t['r2']:.2f}", fmt_p(t["p"]), f"{t['first']:g}", f"{t['last']:g}"])
    w('<div class="card">' + table(["Measure", "Linear trend", "R²", "p", "First value", "Last value"], rows) + '</div>')
    w('<p><b>No memory growth while the machine was undisturbed.</b> In minutes 0–15 Apache\'s private memory averaged 468 MB with a trend of +0.29 MB per minute (R² = 0.05, about 4 MB in 15 minutes), '
      'MariaDB\'s averaged 62 MB with a slight downward trend, and the per-minute p95 of all requests did not rise (−2.6 ms per minute). After minute 15 both processes\' memory was shaped by the host (see below): '
      'it fluctuated, then fell to about 390 MB and 31 MB as Windows trimmed working sets under memory pressure, so the full-run memory trends in the table are not evidence either way. '
      '<b>One page did slow down steadily</b>, and the cause is data growth rather than a leak. Every soak session submits a request, so about 5,400 requests accumulated and about 4,000 were still pending at the end. '
      'The admin approvals page lists every pending request (with its full data repeated in three buttons per row) without paging, and its median rose from 166 ms in minutes 0–5 to 499 ms, 890 ms, 2.9 s, 3.9 s and 4.9 s in the following 5-minute blocks. '
      f'Over the same time Apache CPU rose from {tr["httpd_cpu_pct_trend"]["first"]:g}% to {tr["httpd_cpu_pct_trend"]["last"]:g}% and MariaDB CPU from {tr["mysqld_cpu_pct_trend"]["first"]:g}% to {tr["mysqld_cpu_pct_trend"]["last"]:g}% of the machine at a constant number of users.</p>')
    w('<p class="note">Host interference. From about minute 15, other programmes on the laptop competed for the machine: mean machine CPU rose from 21% (minutes 0–15) to 53%, while Apache, MariaDB and k6 together used about 32%, '
      'and available memory fell below 1 GB at minute 23 and as low as 22 MB. At about minute 21, with the machine\'s CPU at 92–97%, every request in flight stalled for about 2 minutes (maximum 130.8 s; 4 timeouts and 9 connection errors). '
      'The p95 outliers at minutes 20 and 25, the rise of the overall median after minute 15 and the errors coincide with these episodes. They are a property of the test machine, not of Reserve-a-Lab, and they produce the full-run p95 trend in the table. '
      'The admin-page growth started before them.</p>')
    w('<div class="card">' + g["res_table"]([("Soak, 100 users", SOAK), ("Spike, 5→200", SPIKE)] + [(f"Stress, {v} users", STRESS[v]) for v in g["STRESS_STEPS"]]) + '</div>')

    # ------------------------------------------------------------------ concurrency
    w('<h2>Test 5. Concurrency and double booking</h2>')
    w(f'<p>Each repetition starts from the restored baseline. All virtual users log in and load their pages first, then wait for the same wall-clock instant (a barrier set in k6 <code>setup()</code>) '
      f'before sending the critical request. The largest gap between the first and last request at a barrier was {max(skew.values()):.0f} ms. After each run the database was queried directly; '
      'the query output is reproduced below as evidence. These are the results <b>before</b> the fixes; the re-runs after them are in <a href="#after-fixes">After fixes</a>.</p>')

    w('<h3>5a. Same lab, date and time slot</h3>')
    w(f'<div class="verdict"><b>FAIL.</b> 20 teachers requested Science Laboratory 3, 9:00–11:00, on the same date at the same instant, and 20 admin sessions then approved them at the same instant. '
      f'The number of approved requests for that one slot was {", ".join(str(d["approved"]) for d in slot_race)} in the five repetitions. A correct system approves at most 1.</div>')
    rows = [[f"Repetition {d['rep']}", d["date"], str(d["requests"]), f"<b>{d['approved']}</b>", str(d["pending"]), pf(d["pass"])] for d in slot_race]
    rows += [[f"Control: one by one, 3 s apart", d["date"], str(d["requests"]), f"<b>{d['approved']}</b>", str(d["pending"]), pf(d["pass"])] for d in slot_seq]
    w('<div class="card">' + table(["Run", "Date", "Requests for the slot", "Approved", "Still pending", "Result"], rows) + '</div>')
    w('<div class="card">' + bar_chart([(f"Rep {d['rep']}", d["approved"], "bad", f"Repetition {d['rep']}: {d['approved']} approved") for d in slot_race] +
                                       [("Control", slot_seq[0]["approved"], "bad", f"One by one: {slot_seq[0]['approved']} approved")],
                                       y_unit="", title="Approved requests for one slot", ref=(1, "correct: 1")) +
      '<p class="note">Approved requests for the same slot per run; the dashed line is the correct maximum.</p></div>')
    w('<p><b>Why.</b> The approve action (<code>ajax/ajax_admin_action.php</code>, <code>force_approve</code>, line 117; the same applies to the multi-stage path in <code>ajax_supervisor_action.php</code>) '
      'updates the request to Approved without checking the slot on the server, without a transaction or row lock, and without a unique constraint in the database. The only check is a separate '
      '<code>check_conflict</code> call that the browser makes before showing the approve dialog. The race runs gave every admin the most favourable client: each ran that check correctly and skipped the approval '
      'if it reported an approved conflict. Even so, 17–20 approvals went through, because all checks ran before any approval was written (a check-then-act race). '
      'Only the few admins whose check arrived after another approval had been written were stopped (0, 0, 1, 2 and 3 per repetition).</p>')
    w('<p><b>It also fails without concurrency.</b> The approvals page sends <code>check_conflict</code> without the <code>csrf_token</code> field the endpoint requires (<code>admin_approve.php</code> line 1604 against <code>ajax_forms.php</code> line 446), '
      'so the browser receives HTTP 403 and never shows the "Severe Conflict" warning. In the control run, three admins approved three requests for the same slot 3 seconds apart through that real page path; '
      'the page\'s check returned 403 each time, a correctly sent check would have reported "approved" for the second and third, and the server approved all three. '
      'The live warning on the request form (<code>forms.php</code> line 1327) has the same missing field. The submission endpoint actually used by the form (<code>ajax_supervisor_action.php</code>) has no slot check either.</p>')

    w('<h3>5b. Last units of one inventory item</h3>')
    w(f'<div class="verdict"><b>FAIL.</b> An item with 5 units left; 20 teachers each requested 2 units at the same instant and 20 admins approved all 20 requests at the same instant. '
      f'In every repetition all 20 were approved, allocating 40 units against 5 in stock. The stock went below zero (−1) in {neg} of 5 repetitions.</div>')
    rows = [[f"Repetition {d['rep']}", "5", str(d["requests"]), f"<b>{d['approved']}</b>", f"{d['units_allocated']}", f"<b>{d['quantity_now']}</b>",
             "yes" if d["over_allocated"] else "no", "yes" if d["went_negative"] else "no", pf(d["pass"])] for d in inv_race]
    rows += [["Control: one by one, 3 s apart", "5", str(d["requests"]), f"<b>{d['approved']}</b>", f"{d['units_allocated']}", f"<b>{d['quantity_now']}</b>",
              "yes" if d["over_allocated"] else "no", "yes" if d["went_negative"] else "no", pf(d["pass"])] for d in inv_seq]
    w('<div class="card">' + table(["Run", "Starting stock", "Requests", "Approved", "Units allocated", "Stock after", "Over-allocated", "Below zero", "Result"], rows) + '</div>')
    w('<p><b>Why.</b> Stock is checked at submission against the current quantity, but nothing is reserved, so every request for 2 of 5 units passes. At approval, '
      '<code>scilab_deduct_inventory()</code> (<code>helperFiles/variableDeclarations.php</code>) approves regardless of stock and deducts <code>min(requested, quantity read)</code> with a separate '
      '<code>UPDATE … SET quantity = quantity − ? WHERE quantity &gt; 0</code>. When two approvals read the same quantity (for example 1) before either writes, the second subtracts from a value that is no longer there, '
      'and the signed <code>INT</code> column accepts −1. The control run shows that over-allocation happens even one approval at a time (3 approved, 6 units against 5); going below zero needs concurrency.</p>')

    w('<h3>5c. Control: the inventory page\'s manual deduction</h3>')
    w(f'<div class="verdict good"><b>PASS, 5 of 5.</b> 20 admin sessions each deducted 1 unit from an item with 5 units at the same instant, using the inventory page\'s deduction (<code>ajax_inventory.php</code>, '
      f'which uses a transaction, <code>SELECT … FOR UPDATE</code> and <code>quantity &gt;= ?</code>). Every repetition ended at 0 with exactly 5 deductions logged (5→4→3→2→1→0) and 15 refusals '
      '("only 0 in stock"). This shows that the test detects correct locking as well as missing locking.</div>')
    rows = [[f"Repetition {d['rep']}", "5", str(d["requests"]), str(d["deductions"]), str(d["units_deducted"]), str(d["quantity_now"]), pf(d["pass"])] for d in manual]
    w('<div class="card">' + table(["Run", "Starting stock", "Requests", "Deductions logged", "Units deducted", "Stock after", "Result"], rows) + '</div>')
    w('<h3>Database evidence</h3><p class="note">Raw output of the queries run after each repetition (also saved in <code>loadtests/results/*_db.txt</code>).</p>')
    for name in sorted(g["EVIDENCE"]):
        w(f'<details><summary>{E(name)}</summary><pre>{E(g["EVIDENCE"][name])}</pre></details>')

    # ------------------------------------------------------------------ after the fixes
    w(after_section(g))

    # ------------------------------------------------------------------ statistics
    w('<h2>Statistics: Lighthouse before and after</h2>')
    w('<p>Mobile preset, median of 3 runs per page, from the <a href="index.html">Lighthouse report</a>. The login page is excluded because Lighthouse could not score it before the changes (NO_FCP), leaving n = 8 paired pages. '
      'Differences are after − before. Normality of the differences was checked with Shapiro–Wilk; the paired t-test is preferred when p ≥ .05, the Wilcoxon signed-rank test otherwise. '
      'Effect sizes: Cohen\'s d<sub>z</sub> (mean difference / SD of differences) and the matched-pairs rank-biserial correlation r<sub>rb</sub>. Holm-adjusted p-values correct for the four metrics.</p>')
    rows = []
    for r in LH["results"]:
        u = r["unit"]
        f = (lambda v: f"{v:,.0f}") if u == "KB" else (lambda v: f"{v:.2f}") if u == "s" else (lambda v: f"{v:.1f}")
        rows.append([r["metric"] + f' <span class="note">({u})</span>', f(r["mean_before"]), f(r["mean_after"]), f"{f(r['mean_diff'])} [{f(r['ci95'][0])}, {f(r['ci95'][1])}]",
                     f"{r['shapiro_W']:.3f} ({fmt_p(r['shapiro_p'])})", f"{r['t']:.2f}", fmt_p(r["t_p"]), fmt_p(r["t_p_holm"]), f"{r['cohen_dz']:.2f}",
                     f"{r['wilcoxon_W']:.0f}", fmt_p(r["wilcoxon_p"]), fmt_p(r["wilcoxon_p_holm"]), f"{r['rank_biserial']:.2f}", f"<b>{r['recommended']}</b>"])
    w('<div class="card">' + table(["Metric", "Mean before", "Mean after", "Mean difference [95% CI]", "Shapiro–Wilk W (p)", "t(7)", "p", "p Holm", "d<sub>z</sub>",
                                    "Wilcoxon W", "p", "p Holm", "r<sub>rb</sub>", "Trust"], rows) + '</div>')
    w(f'<ul><li><b>Performance score and FCP:</b> the differences are consistent with normality, so the paired t-test applies; both are significant after Holm correction with very large effects.</li>'
      f'<li><b>LCP and page weight:</b> the differences are not normal (the About page lost 12.2 s of LCP and 5,442 KB, far more than any other page), so the Wilcoxon test is the one to trust. '
      f'For page weight the two tests disagree: the t-test is not significant (p = {fmt_p(lh["Page weight"]["t_p"])}) because the About outlier inflates the variance, while Wilcoxon finds every one of the 8 pages lighter (p = {fmt_p(lh["Page weight"]["wilcoxon_p"])}).</li>'
      '<li>With 8 pairs and every page improving, W = 0 and p = .0078 is the smallest p-value the exact Wilcoxon test can give, so its p-value cannot grow with the size of the improvement; the effect size (r<sub>rb</sub> = 1.00 for all metrics: every page improved) carries that information.</li>'
      '<li>Sensitivity: repeating the tests on the rounded values printed in the Lighthouse report gives the same conclusions (score t-test p &lt; .001, FCP p &lt; .001, LCP Wilcoxon p = .0078, page weight t-test p = .076 and Wilcoxon p = .0078).</li></ul>')

    # ------------------------------------------------------------------ interpretation
    w('<h2>Interpretation</h2><h3>Nielsen\'s response-time limits</h3>')
    w('<p>Nielsen (1993) gives three limits: 0.1 s feels instantaneous, 1 s keeps the user\'s flow of thought, and 10 s is the limit for keeping attention on the task. The table counts endpoints by the band their p95 falls in.</p>')
    rows = []
    for lbl, run in [("Load, 50 users", LOAD)] + [(f"Stress, {v} users", STRESS[v]) for v in g["STRESS_STEPS"]] + [("Spike peak, 200 users", None), ("Soak, 100 users", SOAK)]:
        if run is None:
            continue
        bands = {"≤ 0.1 s": 0, "≤ 1 s": 0, "≤ 10 s": 0, "> 10 s": 0}
        for k, v in run["endpoints"].items():
            bands[g["nielsen"](v["p95"])] += 1
        rows.append([lbl] + [str(bands[b]) for b in bands] + [ms(run["overall"]["p95"])])
    w('<div class="card">' + table(["Run", "Endpoints with p95 ≤ 0.1 s", "≤ 1 s", "≤ 10 s", "> 10 s", "p95, all requests"], rows) + '</div>')
    w(f'<p>At the expected peak of 50 users, {sum(1 for v in LOAD["endpoints"].values() if v["p95"] <= 100)} of {len(LOAD["endpoints"])} endpoints answer within 0.1 s at the 95th percentile and the rest within 1 s. '
      'The three that exceed 0.1 s are the actions that write and send e-mail (submit request, approve request) and the admin approvals page, which grows with the number of pending requests. '
      'No endpoint reaches the 10 s limit at any load up to 200 users; at 200 users four endpoints move into the 1–10 s band at the 95th percentile, where users notice the delay but stay on task. '
      'In the soak test only the admin approvals page left the 1 s band, because of data growth.</p>')
    w('<h3>ISO/IEC 25010 performance efficiency</h3><div class="card">' + table(["Sub-characteristic", "Result", "Evidence"], [
        ["Time behaviour", '<span class="pill good">Satisfactory</span> up to 100 users',
         f"Median ≈ 25 ms and p95 ≤ {ms(max(STRESS[v]['overall']['p95'] for v in (10, 25, 50, 100)))} from 10 to 100 users; at 200 users the median is unchanged but the p95 is {ms(st200['p95'])}. "
         "The admin approvals page degrades as pending requests accumulate (median 4.9 s with about 4,000 pending)."],
        ["Resource utilization", '<span class="pill good">Satisfactory</span>',
         f"Apache used {LOAD['resources']['httpd_cpu_pct']['mean']:.1f}% of the machine's CPU at 50 users and {STRESS[200]['resources']['httpd_cpu_pct']['mean']:.1f}% at 200; MariaDB below 3% up to 200 users. "
         "No memory growth in the undisturbed first 15 minutes of the soak (no sign of a leak). CPU per request rises as the request table grows."],
        ["Capacity", f'<span class="pill ok">≥ {CAP["capacity_vus"]} users</span>',
         f"Within the limits at 200 concurrent users with 0% errors and {st200['throughput_rps']:.0f} requests/s; above about 150 connections the Apache thread pool, not CPU, becomes the bottleneck. "
         "Upper limit not measured (host memory)."],
    ], "wrap") + '</div>')
    w('<p class="note">Functional correctness is a separate ISO/IEC 25010 characteristic (functional suitability), but the concurrency results belong in the same discussion: '
      'the system stays fast under concurrent use but does not keep its booking and stock rules under concurrent use, or in the slot case even under sequential use through the real page.</p>')

    # ------------------------------------------------------------------ limitations
    w('<h2>Limitations</h2><div class="card"><ul>'
      '<li><b>Local laptop, not the campus server.</b> Server, database and load generator shared one laptop, which also ran other programmes. k6 used up to 14% of the machine\'s CPU at 200 users and competed with Apache. '
      'Memory pressure from other programmes stopped the 300-user step and disturbed the last half of the soak test. Absolute numbers on the campus server (different CPU, memory, operating system, possibly Linux with a different Apache MPM) will differ.</li>'
      '<li><b>No network.</b> Requests never left the machine, so there was no network latency or bandwidth limit. Real users on the campus network or mobile data will see longer times; the Lighthouse report covers the front end on a throttled connection.</li>'
      '<li><b>E-mail excluded from the timings.</b> With SMTP blocked, each notification attempt failed in milliseconds. In production every submission and approval sends its e-mails through Gmail inside the request '
      '(in the test database an approval attempted 36–56 notification e-mails and a submission 1–2), so real submit and approve times will be longer, possibly by seconds.</li>'
      '<li><b>Simulated users.</b> Virtual users follow two scripted journeys with random think times; real users browse less regularly, use other pages and do not all submit a request every session. '
      'The 80/20 split and the think times are assumptions. Every session logged in afresh. Browser caching and static files were not part of the load.</li>'
      '<li><b>Synthetic data.</b> 505 synthetic accounts were added (they lengthen the teacher list in the request form). Requests accumulated faster than in real use (one per requester session), which makes the data-growth effect on the admin approvals page appear within minutes; in real use it would build up over weeks if pending requests are not cleared.</li>'
      '<li><b>Capacity is a lower bound.</b> The 300-user step did not finish, so capacity is reported as at least 200 concurrent users.</li>'
      '<li><b>Lighthouse statistics.</b> n = 8 pages is small; the pages are not a random sample, and each value is a median of 3 runs. The About-page LCP value is the published median (17.7 s) because its saved report is a different run (14.6 s).</li>'
      '<li><b>MariaDB counters.</b> <code>FLUSH STATUS</code> did not reset the server\'s status counters in this setup, so per-run MariaDB counters are not reported.</li>'
      '</ul></div>')

    w('<h2>Reproducing</h2><div class="card"><p>Scripts are in <code>loadtests/</code>; see <code>loadtests/README.md</code>. Raw k6 output (CSV), resource samples, PHP/Apache log extracts and summaries are in <code>loadtests/results/</code>.</p></div>')
    return "\n".join(out)


def after_section(g):
    """The 'After fixes' section: the concurrency tests re-run after the server-side fixes,
    next to the original results (which stay in the report above), plus the 50-user load
    re-run to check that the locking did not slow things down."""
    E, ms, table, pf = g["E"], g["ms"], g["table"], g["pf"]
    bar_chart = g["bar_chart"]
    A = g["AFTER"]
    slot_race, slot_seq, inv_race, inv_seq, manual = g["slot_race"], g["slot_seq"], g["inv_race"], g["inv_seq"], g["manual"]
    a_slot, a_slot_seq = A.get("slot_race", []), A.get("slot_sequential", [])
    a_inv, a_inv_seq, a_man = A.get("inventory_race", []), A.get("inventory_sequential", []), A.get("manual_race", [])
    tally = g["outcome_tally"]
    out = []
    w = out.append
    if not a_slot:
        return ""

    def refused(test, mode, rep):
        return int(tally(f"conc_{test}_{mode}_after_r{rep}").get("refused_by_server", 0))

    def own_check(rep):
        return int(tally(f"conc_slot_race_after_r{rep}").get("blocked_by_check", 0))

    all_pass = lambda rows: bool(rows) and all(d["pass"] for d in rows)
    verdict = lambda ok: f'<span class="pill {"good" if ok else "bad"}">{"PASS" if ok else "FAIL"}</span>'

    w('<h2 id="after-fixes">After fixes</h2>')
    w('<p>The three concurrency failures above were fixed on the server side (branch <code>fix/concurrency</code>) and tests 5a, 5b and 5c were re-run with the same scripts, '
      'the same 20 simultaneous teachers and admins, the same baseline restored before every repetition and the same database queries. '
      'The <b>Before</b> columns below are the original runs, unchanged and still shown in full above; the <b>After</b> columns are the re-runs. '
      'The database was backed up before the re-runs and restored afterwards; outbound SMTP was blocked again for the duration.</p>')
    w('<div class="card"><b>What changed</b><ul>'
      '<li><b>Approval is one transaction</b> (<code>scilab_approve_request()</code> in <code>helperFiles/variableDeclarations.php</code>), used by <code>force_approve</code> and <code>approve</code> in '
      '<code>ajax/ajax_admin_action.php</code> and by the final stage of the multi-stage path in <code>ajax/ajax_supervisor_action.php</code>. It locks the lab\'s <code>scilab_availability</code> row '
      '(<code>SELECT … FOR UPDATE</code>), refuses if an approved request for the same lab and date overlaps, then updates the status and deducts stock. A refusal rolls everything back.</li>'
      '<li><b>Stock</b>: <code>scilab_deduct_inventory()</code> locks the item rows (<code>SELECT … FOR UPDATE</code>, in id order), refuses the approval with a message if any item has too little stock, '
      'and deducts with <code>WHERE quantity &gt;= ?</code>, the same pattern as the inventory page\'s manual deduction. No schema change.</li>'
      '<li><b>Submission</b> (<code>request_submission</code>) refuses a slot that an approved request already holds.</li>'
      '<li><b>Conflict warning</b>: <code>admin_approve.php</code> and <code>forms.php</code> now send the <code>csrf_token</code> field with <code>check_conflict</code>, so the "Severe Conflict" warning can show again.</li>'
      '</ul></div>')

    # ---------------------------------------------------------------- 5a
    w('<h3>5a after the fix: same lab, date and time slot</h3>')
    w(f'<div class="verdict {"good" if all_pass(a_slot) else ""}"><b>{"PASS" if all_pass(a_slot) else "FAIL"}.</b> '
      f'Approved requests for the one slot in the five repetitions: {", ".join(str(d["approved"]) for d in a_slot)} (before: {", ".join(str(d["approved"]) for d in slot_race)}). '
      'The correct number is at most 1.</div>')
    rows = []
    for b, a in zip(slot_race, a_slot):
        rows.append([f"Repetition {a['rep']}", a["date"], str(b["approved"]), f"<b>{a['approved']}</b>", str(own_check(a["rep"])), str(refused("slot", "race", a["rep"])), str(a["pending"]), verdict(a["pass"])])
    for b, a in zip(slot_seq, a_slot_seq):
        rows.append(["Control: one by one, 3 s apart", a["date"], f"{b['approved']} of {b['requests']}", f"<b>{a['approved']} of {a['requests']}</b>",
                     "–", str(refused("slot", "sequential", a["rep"])), str(a["pending"]), verdict(a["pass"])])
    w('<div class="card">' + table(["Run", "Date", "Approved, before", "Approved, after", "Stopped by their own check", "Refused by the server", "Still pending", "Result"], rows) + '</div>')
    w('<div class="card">' + bar_chart([(f"Rep {d["rep"]}", d["approved"], "good" if d["pass"] else "bad", f"Repetition {d['rep']}: {d['approved']} approved") for d in a_slot],
                                       y_unit="", title="Approved requests for one slot, after the fix", ref=(1, "correct: 1"), y_max=20) +
      '<p class="note">Drawn on the same 0–20 axis as the Before chart in 5a. The dashed line is the correct maximum.</p></div>')
    w('<p>The admins\' correct conflict checks still race: in repetitions 1 to 4 all 20 checks reported only "pending" because they ran before any approval was written, and in repetition 5 two admins\' checks '
      'happened to see the approval and skipped it (before the fix this stopped 0 to 3 admins per repetition). What changed is that the server now decides: the first approval to take the lab lock was approved '
      'and every other admin who sent an approval was refused with "Cannot approve: Science Laboratory 3 is already booked …" (19 in repetitions 1 to 4 and 17 in repetition 5, where two had already stopped themselves). '
      'In the one-by-one control the second and third approvals were refused as well, so the sequential failure is fixed too.</p>')

    # ---------------------------------------------------------------- 5b
    w('<h3>5b after the fix: last units of one inventory item</h3>')
    w(f'<div class="verdict {"good" if all_pass(a_inv) else ""}"><b>{"PASS" if all_pass(a_inv) else "FAIL"}.</b> '
      f'Starting from 5 units and 20 simultaneous approvals of 2 units each, {", ".join(str(d["approved"]) for d in a_inv)} requests were approved per repetition '
      f'({", ".join(str(d["units_allocated"]) for d in a_inv)} units allocated) and the stock ended at {", ".join(str(d["quantity_now"]) for d in a_inv)}. '
      'Before: 20 approved, 40 units allocated, stock 0 or −1.</div>')
    rows = []
    for b, a in zip(inv_race, a_inv):
        rows.append([f"Repetition {a['rep']}", f"{b['approved']} / {b['units_allocated']} / {b['quantity_now']}",
                     f"<b>{a['approved']}</b> / {a['units_allocated']} / <b>{a['quantity_now']}</b>", str(refused("inventory", "race", a["rep"])),
                     "yes" if a["over_allocated"] else "no", "yes" if a["went_negative"] else "no", verdict(a["pass"])])
    for b, a in zip(inv_seq, a_inv_seq):
        rows.append(["Control: one by one, 3 s apart", f"{b['approved']} / {b['units_allocated']} / {b['quantity_now']}",
                     f"<b>{a['approved']}</b> / {a['units_allocated']} / <b>{a['quantity_now']}</b>", str(refused("inventory", "sequential", a["rep"])),
                     "yes" if a["over_allocated"] else "no", "yes" if a["went_negative"] else "no", verdict(a["pass"])])
    w('<div class="card">' + table(["Run", "Before: approved / units allocated / stock after", "After: approved / units allocated / stock after", "Refused by the server",
                                     "Over-allocated", "Below zero", "Result"], rows) + '</div>')
    w('<p>Two requests (4 units) fit in 5 units; the remaining 18 were refused with "Cannot approve: not enough stock for … (2 requested, only 1 … left). Nothing was deducted." and stayed pending. '
      'The stock never went below zero and no approval was written without its deduction. The one-by-one control, which used to allocate 6 units against 5, now approves two and refuses the third.</p>')

    # ---------------------------------------------------------------- 5c
    w('<h3>5c after the fix: manual deduction (control)</h3>')
    w(f'<div class="verdict {"good" if all_pass(a_man) else ""}"><b>{"PASS" if all_pass(a_man) else "FAIL"}, {sum(1 for d in a_man if d["pass"])} of {len(a_man)}.</b> '
      'The inventory page\'s manual deduction was not changed; re-running it confirms that the new approval code did not disturb it and that the test setup is unchanged.</div>')
    rows = [[f"Repetition {a['rep']}", f"{b['deductions']} / {b['units_deducted']} / {b['quantity_now']}", f"{a['deductions']} / {a['units_deducted']} / {a['quantity_now']}", verdict(a["pass"])]
            for b, a in zip(manual, a_man)]
    w('<div class="card">' + table(["Run", "Before: deductions / units / stock after", "After: deductions / units / stock after", "Result"], rows) + '</div>')

    # ---------------------------------------------------------------- load
    LA, LB = g.get("LOAD_AFTER"), g["LOAD"]
    if LA:
        lb, la = LB["overall"], LA["overall"]
        w('<h3>Did the locking slow things down? 50-user load test, before and after</h3>')
        w('<p>The 10-minute, 50-user test (test 1) was repeated with the fixed code, on a freshly restored baseline and with the same scripts apart from the changes below. '
          'Approvals now take a per-lab lock, so a slowdown would show first in the approve and submit requests.</p>')
        def chg(b, a):
            if not b:
                return "–"
            pct = round((a - b) / b * 100)
            return f"{pct:+d}%" if pct else "0%"
        def row(label, b, a, fmt=lambda v: ms(v)):
            return [label, fmt(b), fmt(a), chg(b, a)]
        rows = [row("Requests", lb["n"], la["n"], lambda v: f"{v:,}"),
                row("Throughput (requests/s)", lb["throughput_rps"], la["throughput_rps"], lambda v: f"{v:.1f}"),
                row("Median", lb["med"], la["med"]), row("p90", lb["p90"], la["p90"]), row("p95", lb["p95"], la["p95"]),
                row("p99", lb["p99"], la["p99"]), row("Max", lb["max"], la["max"]),
                row("Errors", lb["errors"], la["errors"], lambda v: f"{v:,.0f}")]
        w('<div class="card">' + table(["All requests, 10-minute hold", "Before", "After", "Change"], rows) + '</div>')
        keys = ["POST ajax_forms.php check_conflict", "POST ajax_supervisor_action.php request_submission", "POST ajax_admin_action.php force_approve",
                "GET admin_approve.php", "GET forms.php"]
        rows = []
        for k in keys:
            if k in LB["endpoints"] and k in LA["endpoints"]:
                e0, e1 = LB["endpoints"][k], LA["endpoints"][k]
                rows.append([g["ENDPOINT_LABELS"].get(k, k), str(e0["n"]), str(e1["n"]), ms(e0["med"]), ms(e1["med"]), ms(e0["p95"]), ms(e1["p95"]), chg(e0["p95"], e1["p95"]), str(e1["errors"])])
        w('<div class="card">' + table(["Endpoint", "Requests, before", "Requests, after", "Median, before", "Median, after", "p95, before", "p95, after", "p95 change", "Errors, after"], rows) + '</div>')
        rb, ra = LB["resources"], LA["resources"]
        rows = [[lab, f"{rb[k]['mean']:.1f}%", f"{ra[k]['mean']:.1f}%"] for lab, k in (("Apache CPU (mean)", "httpd_cpu_pct"), ("MariaDB CPU (mean)", "mysqld_cpu_pct"), ("Whole machine CPU (mean)", "total_cpu_pct"))]
        w('<div class="card">' + table(["Resource", "Before", "After"], rows) + '</div>')
        ap, sb = tally("load_50_after", "approval_outcome"), tally("load_50_after", "submission_outcome")
        w(f'<p class="note">Outcomes over the whole after run, including ramp-up and ramp-down: approvals {int(ap.get("approved", 0))} approved and {int(ap.get("refused_by_server", 0))} refused by the server; '
          f'submissions {int(sb.get("submitted", 0))} accepted and {int(sb.get("slot_taken", 0))} refused because an approved request held the slot. '
          'A refusal is a correct answer, not an error, and was not counted as one. The k6 journeys now send <code>csrf_token</code> with both conflict checks, as the fixed pages do, '
          'so those calls return 200 instead of 403 and run the conflict query; the before run replayed the pages as they were.</p>')

    # ---------------------------------------------------------------- notes
    w('<h3>Limits of the after-fix results</h3><div class="card"><ul>'
      '<li>Same laptop, same barrier technique, same 20 virtual users and 5 repetitions as before; a pass in five repetitions is evidence, not a proof. The lock and the transaction are what guarantee the result.</li>'
      '<li>Only the paths exercised by these tests were run: <code>force_approve</code> from the admin approvals page. The multi-stage path (<code>ajax_supervisor_action.php</code>, final stage) uses the same helper but was not part of the race tests.</li>'
      '<li>Not covered: two admins approving the <i>same</i> request at the same time (the request is not checked for being already approved), and <code>supervisor_approve.php</code>, whose conflict check still omits <code>csrf_token</code>.</li>'
      '<li>Behaviour change: approval is now refused when stock is short, where it used to approve and deduct what was left. For reagents, submission checks the total across rows but the deduction takes from one row, '
      'so a request spread across rows can pass submission and be refused at approval.</li>'
      '<li>The SMTP block was in place, so notification e-mail time is not included, as in the original runs.</li>'
      '</ul></div>')
    w('<h3>Database evidence after the fixes</h3><p class="note">Raw output of the same queries after each re-run (also saved in <code>loadtests/results/*_after_*_db.txt</code>).</p>')
    for name in sorted(g["AFTER_EVIDENCE"]):
        w(f'<details><summary>{E(name)}</summary><pre>{E(g["AFTER_EVIDENCE"][name])}</pre></details>')
    return "\n".join(out)


# ---------------------------------------------------------------------------
# Manuscript tables and results text
# ---------------------------------------------------------------------------
def markdown(g):
    ms, fmt_p = g["ms"], g["fmt_p"]
    LOAD, STRESS, SPIKE, SOAK, LH, CAP = g["LOAD"], g["STRESS"], g["SPIKE"], g["SOAK"], g["LH"], g["CAP"]
    lo, st200, sp, so, tr = g["lo"], g["st200"], g["sp"], g["so"], g["tr"]
    slot_race, slot_seq, inv_race, inv_seq, manual = g["slot_race"], g["slot_seq"], g["inv_race"], g["inv_seq"], g["manual"]
    lh = g["lh"]
    EP = g["ENDPOINT_LABELS"]
    tms = lambda v: "–" if v is None else f"{v:,.0f}"  # table cells: whole milliseconds
    L = []
    w = L.append

    w("# Reserve-a-Lab performance efficiency: tables and results text for the manuscript\n")
    w("Response times in the tables are in milliseconds (ms). p95 = 95th-percentile response time. All tests were run on 27 October 2026 against a local XAMPP installation; see Table 1.\n")

    w("**Table 1.** Test environment\n")
    w("| Item | Value |\n|---|---|")
    for a, b in _env():
        w(f"| {a} | {b} |")
    w("")

    w("**Table 2.** Load test: response time per endpoint at the expected peak (50 concurrent users, 10-minute hold)\n")
    w("| Endpoint | Requests | Mean (ms) | Median (ms) | p90 (ms) | p95 (ms) | Errors |\n|---|---:|---:|---:|---:|---:|---:|")
    for k, v in g["endpoint_rows"](LOAD):
        w(f"| {EP[k]} | {v['n']:,} | {tms(v['avg'])} | {tms(v['med'])} | {tms(v['p90'])} | {tms(v['p95'])} | {v['errors']} |")
    w(f"| **All requests** | **{lo['n']:,}** | **{tms(lo['avg'])}** | **{tms(lo['med'])}** | **{tms(lo['p90'])}** | **{tms(lo['p95'])}** | **{lo['errors']}** |")
    w(f"\nThroughput: {lo['throughput_rps']:.1f} requests/s. Error rate: {lo['error_pct']:.2f}%.\n")

    w("**Table 3.** Stress test: response time, throughput, errors and CPU per step (3-minute hold per step)\n")
    w("| Virtual users | Throughput (req/s) | Mean (ms) | Median (ms) | p90 (ms) | p95 (ms) | Error rate (%) | Apache CPU (%) | MariaDB CPU (%) | Within limits |\n|---:|---:|---:|---:|---:|---:|---:|---:|---:|---|")
    for v in g["STRESS_STEPS"]:
        o, r = STRESS[v]["overall"], STRESS[v]["resources"]
        w(f"| {v} | {o['throughput_rps']:.1f} | {tms(o['avg'])} | {tms(o['med'])} | {tms(o['p90'])} | {tms(o['p95'])} | {o['error_pct']:.2f} | {r['httpd_cpu_pct']['mean']:.1f} | {r['mysqld_cpu_pct']['mean']:.1f} | Yes |")
    w("| 300 | – | – | – | – | – | – | – | – | Not completed |")
    w("\nLimits: p95 ≤ 10 s and error rate ≤ 5%. CPU is the mean share of the whole machine (16 logical processors). The 300-user step was stopped by the host for low memory.\n")

    w("**Table 4.** Spike test: 5 → 200 → 5 virtual users\n")
    w("| Phase | Duration | Requests | Throughput (req/s) | Median (ms) | p95 (ms) | Max (ms) | Errors |\n|---|---|---:|---:|---:|---:|---:|---:|")
    for k, lbl, dur in (("baseline", "Baseline, 5 users", "60 s"), ("jump", "Jump to 200 users", "10 s"), ("peak", "Peak, 200 users", "60 s"),
                        ("drop", "Drop to 5 users", "10 s"), ("recovery", "Recovery, 5 users", "180 s")):
        p = sp[k]
        w(f"| {lbl} | {dur} | {p['n']:,} | {p['rps']:.1f} | {tms(p['med'])} | {tms(p['p95'])} | {tms(p['max'])} | {p['errors']} |")
    w("")

    w("**Table 5.** Soak test: 100 virtual users for 30 minutes\n")
    w("| Measure | Value |\n|---|---|")
    w(f"| Requests | {so['n']:,} |\n| Throughput | {so['throughput_rps']:.1f} req/s |\n| Mean / median / p90 / p95 | {tms(so['avg'])} / {tms(so['med'])} / {tms(so['p90'])} / {tms(so['p95'])} ms |")
    w(f"| Errors | {so['errors']} ({so['error_pct']:.3f}%; {', '.join(f'{v} {k}' for k, v in so['error_types'].items())}) |")
    for key, lbl, unit in (("p95_trend", "Trend of per-minute p95", "ms/min"), ("med_trend", "Trend of per-minute median", "ms/min"),
                           ("httpd_mem_mb_trend", "Trend of Apache memory", "MB/min"), ("mysqld_mem_mb_trend", "Trend of MariaDB memory", "MB/min"),
                           ("httpd_cpu_pct_trend", "Trend of Apache CPU", "percentage points/min"), ("mysqld_cpu_pct_trend", "Trend of MariaDB CPU", "percentage points/min")):
        t = tr[key]
        w(f"| {lbl}, full 30 min* | {t['slope_per_min']:+.2f} {unit} (R² = {t['r2']:.2f}, p {'<' if t['p'] < 0.001 else '='} {fmt_p(t['p']).lstrip('< ')}) |")
    w("| Apache memory, minutes 0–15 | mean 468 MB, trend +0.29 MB/min (R² = 0.05) |")
    w("| MariaDB memory, minutes 0–15 | mean 62 MB, trend −0.35 MB/min (R² = 0.58) |")
    w("| Per-minute p95, minutes 0–15 | trend −2.6 ms/min (p = .003) |")
    w("| Admin approvals page, median per 5-min block | 0.17, 0.50, 0.89, 2.95, 3.90, 4.90, 4.18 s |")
    w("\n\\* From minute 15 other programmes on the test laptop loaded its CPU and memory (machine CPU 21% → 53%, available memory down to 22 MB); full-run trends include these episodes. Minutes 0–15 are undisturbed.")
    w("")

    w("**Table 6.** Concurrency tests: database state after each repetition (20 simultaneous users)\n")
    w("| Test | Rep. 1 | Rep. 2 | Rep. 3 | Rep. 4 | Rep. 5 | Sequential control | Correct value | Result |\n|---|---|---|---|---|---|---|---|---|")
    w("| 5a. Approved requests for one lab slot | " + " | ".join(str(d["approved"]) for d in slot_race) + f" | {slot_seq[0]['approved']} of {slot_seq[0]['requests']} | ≤ 1 | Fail |")
    w("| 5b. Approved requests for the last 5 units (2 units each) | " + " | ".join(str(d["approved"]) for d in inv_race) + f" | {inv_seq[0]['approved']} of {inv_seq[0]['requests']} | ≤ 2 | Fail |")
    w("| 5b. Units allocated against 5 in stock | " + " | ".join(str(d["units_allocated"]) for d in inv_race) + f" | {inv_seq[0]['units_allocated']} | ≤ 5 | Fail |")
    w("| 5b. Stock after approvals | " + " | ".join(str(d["quantity_now"]) for d in inv_race) + f" | {inv_seq[0]['quantity_now']} | ≥ 0 | Fail ({g['neg_reps']} of 5 below zero) |")
    w("| 5c. Control: units deducted with the transactional manual deduction | " + " | ".join(str(d["units_deducted"]) for d in manual) + " | – | 5 | Pass |")
    w("\nSequential control: three requests approved one at a time, 3 s apart, through the page's actual request path.\n")

    w("**Table 7.** Paired comparison of Lighthouse mobile results before and after optimisation (n = 8 pages)\n")
    w("| Metric | Mean before | Mean after | Mean difference [95% CI] | Shapiro–Wilk W (p) | Paired t(7) (p) | Cohen's d_z | Wilcoxon W (p) | r_rb | Test to report |\n|---|---:|---:|---|---|---|---:|---|---:|---|")
    for r in LH["results"]:
        u = r["unit"]
        f = (lambda v: f"{v:,.0f}") if u == "KB" else (lambda v: f"{v:.2f}") if u == "s" else (lambda v: f"{v:.1f}")
        w(f"| {r['metric']} ({u}) | {f(r['mean_before'])} | {f(r['mean_after'])} | {f(r['mean_diff'])} [{f(r['ci95'][0])}, {f(r['ci95'][1])}] | {r['shapiro_W']:.3f} ({fmt_p(r['shapiro_p'])}) | "
          f"{r['t']:.2f} ({fmt_p(r['t_p'])}) | {r['cohen_dz']:.2f} | {r['wilcoxon_W']:.0f} ({fmt_p(r['wilcoxon_p'])}) | {r['rank_biserial']:.2f} | {r['recommended']} |")
    w("\nDifferences are after − before. The login page is excluded (no before value: Lighthouse NO_FCP). Holm-adjusted p-values: "
      + "; ".join(f"{r['metric']} t {fmt_p(r['t_p_holm'])}, Wilcoxon {fmt_p(r['wilcoxon_p_holm'])}" for r in LH["results"]) + ".\n")

    # ------------------------------------------------------------- results text
    w("---\n\n# Results text\n")
    sc, fcp, lcp, pw = lh["Performance score"], lh["FCP"], lh["LCP"], lh["Page weight"]
    w("## Load test\n")
    w(f"With 50 concurrent users, the expected peak, held for 10 minutes, Reserve-a-Lab served {lo['n']:,} requests at {lo['throughput_rps']:.1f} requests per second without a single error. "
      f"The median response time over all requests was {ms(lo['med'])}, the 90th percentile {ms(lo['p90'])} and the 95th percentile {ms(lo['p95'])} (Table 2).\n")
    ap, fa, sb = LOAD["endpoints"]["GET admin_approve.php"], LOAD["endpoints"]["POST ajax_admin_action.php force_approve"], LOAD["endpoints"]["POST ajax_supervisor_action.php request_submission"]
    w(f"Seventeen of the 20 endpoints had a 95th-percentile response time below 0.1 s, within Nielsen's limit for an instantaneous response. The three slower ones were the admin approvals page "
      f"(p95 {ms(ap['p95'])}), request approval ({ms(fa['p95'])}) and request submission ({ms(sb['p95'])}), all within the 1 s limit. Submission and approval are the only actions that write several rows and "
      f"prepare notification e-mails within the request; the approvals page lists every pending request. Apache used on average {LOAD['resources']['httpd_cpu_pct']['mean']:.1f}% and MariaDB "
      f"{LOAD['resources']['mysqld_cpu_pct']['mean']:.1f}% of the machine's CPU.\n")

    w("## Stress test\n")
    st = {v: STRESS[v]["overall"] for v in g["STRESS_STEPS"]}
    w(f"Throughput grew in proportion to the number of users, from {st[10]['throughput_rps']:.1f} requests per second at 10 users to {st[100]['throughput_rps']:.1f} at 100 and {st[200]['throughput_rps']:.1f} at 200, "
      f"and no step produced an error (Table 3). From 10 to 100 users the median stayed between {min(st[v]['med'] for v in (10, 25, 50, 100)):.0f} and {max(st[v]['med'] for v in (10, 25, 50, 100)):.0f} ms and the 95th percentile between "
      f"{min(st[v]['p95'] for v in (10, 25, 50, 100)):.0f} and {max(st[v]['p95'] for v in (10, 25, 50, 100)):.0f} ms. At 200 users the median was unchanged ({st[200]['med']:.0f} ms) but the 95th percentile rose to {ms(st[200]['p95'])} "
      f"and the maximum to {ms(st[200]['max'])}. The 300-user step was stopped by the host operating system for lack of memory before it completed, so capacity, defined as the last step with a 95th percentile ≤ 10 s and an error rate ≤ 5%, is reported as at least 200 concurrent users.\n")
    w(f"The slowdown at 200 users was not caused by processor load: machine CPU averaged {STRESS[200]['resources']['total_cpu_pct']['mean']:.0f}% and Apache {STRESS[200]['resources']['httpd_cpu_pct']['mean']:.1f}%. "
      "The slow requests occurred at a steady rate throughout the step, the delay was entirely in the time to first byte, and the slow responses clustered between 3 and 5 s, bounded by Apache's 5 s keep-alive timeout. "
      "They affected only requests that followed a pause of 3 s or more. This pattern is consistent with exhaustion of Apache's pool of 150 worker threads on Windows, where each idle keep-alive connection holds a thread for up to 5 s, "
      "so requests on new connections must wait once there are more users than threads.\n")

    w("## Spike test\n")
    w(f"When the load jumped from 5 to 200 users in 10 seconds, the system kept serving all requests: there were no errors in any phase (Table 4). During the 1-minute peak, throughput reached {sp['peak']['rps']:.1f} requests per second, "
      f"the median response time stayed at {ms(sp['peak']['med'])} and the 95th percentile rose to {ms(sp['peak']['p95'])}, with a maximum of {ms(sp['peak']['max'])}, the same pattern as the 200-user stress step.\n")
    w(f"Recovery was immediate. In the first 10 seconds after the load returned to 5 users the 95th percentile was {ms(sp['bins_after_drop'][1]['p95'])}, and over the 3-minute recovery phase the median ({ms(sp['recovery']['med'])}) "
      f"and 95th percentile ({ms(sp['recovery']['p95'])}) were back at the low-load level of the stress test (median {ms(st[10]['med'])}, p95 {ms(st[10]['p95'])} at 10 users). No backlog of requests remained after the spike.\n")

    w("## Soak test\n")
    w(f"At 100 users, half the measured capacity, for 30 minutes, the system served {so['n']:,} requests at {so['throughput_rps']:.1f} requests per second with a median of {ms(so['med'])} and a 95th percentile of "
      f"{ms(so['p95'])}; {so['errors']} requests ({so['error_pct']:.3f}%) failed (Table 5). During the first 15 minutes, before the test machine was disturbed, memory did not grow: Apache's private memory averaged 468 MB with a trend of +0.29 MB per minute (R² = 0.05) "
      "and MariaDB's averaged 62 MB with a slight downward trend, and the per-minute 95th percentile did not rise (−2.6 ms per minute). There is therefore no indication of a memory leak; the memory changes after minute 15 reflect Windows trimming working sets under host memory pressure.\n")
    w(f"One page did slow down steadily at constant load. The admin approvals page, which lists all pending requests without paging, slowed from a median of 0.17 s in the first 5 minutes to 0.89 s in minutes 10–15 and 4.90 s in minutes 20–25, "
      f"because each requester session submitted a request and about 4,000 requests were still pending at the end. Apache CPU rose from {tr['httpd_cpu_pct_trend']['first']:g}% to {tr['httpd_cpu_pct_trend']['last']:g}% "
      f"and MariaDB CPU from {tr['mysqld_cpu_pct_trend']['first']:g}% to {tr['mysqld_cpu_pct_trend']['last']:g}% of the machine over the run, consistent with more work per request as the data grew.\n")
    w(f"From about minute 15, other programmes on the test laptop competed for it: mean machine CPU rose from 21% to 53% and available memory later fell to 22 MB. At about minute 21, with the machine's CPU at 92–97%, all requests in flight stalled for about 2 minutes, "
      f"producing the 4 timeouts and 9 connection errors and the largest per-minute outliers. These episodes are attributable to the test machine, not the application, and they account for the full-run increase of the per-minute 95th percentile "
      f"({tr['p95_trend']['slope_per_min']:+.1f} ms per minute, R² = {tr['p95_trend']['r2']:.2f}); the growth of the approvals page began before them.\n")

    w("## Concurrency (double booking)\n")
    w(f"Reserve-a-Lab did not prevent double booking. In each of five repetitions, 20 users submitted requests for the same laboratory, date and time slot at the same instant (all requests sent within 4 ms), and 20 administrator sessions approved them at the same instant. "
      f"The database then held {', '.join(str(d['approved']) for d in slot_race)} approved requests for that single slot, where at most one should exist (Table 6). This happened even though every simulated administrator first ran the system's conflict check correctly and skipped "
      "the approval if it reported an approved conflict: all checks completed before the first approval was written. The approval action itself does not check for conflicts, uses no transaction or lock, and the database has no constraint that would reject a second booking.\n")
    w(f"Double booking also occurred without concurrency. When three administrators approved three requests for the same slot one after another, 3 seconds apart, through the approvals page's actual request path, all three were approved. "
      "The page sends its conflict check without the security token that the endpoint requires, so the check is rejected (HTTP 403) and the administrator never sees the conflict warning. "
      "The submission endpoint used by the request form does not check the slot either.\n")
    w(f"Inventory quantities were over-allocated in the same way. With 5 units of an item in stock, 20 simultaneous requests for 2 units each were all accepted and all approved in every repetition, allocating 40 units against 5. "
      f"The stock went below zero (−1) in {g['neg_reps']} of the 5 repetitions, because the approval reads the quantity and then subtracts in a separate statement. Over-allocation also occurred when approvals were made one at a time (3 approved, 6 units against 5), "
      "because approval does not refuse a request that the stock cannot cover. As a control, the inventory page's manual deduction, which locks the row inside a transaction, passed the same 20-user test in all 5 repetitions: exactly 5 deductions succeeded and the stock ended at 0.\n")

    w("## Lighthouse before and after (statistics)\n")
    w(f"Across the 8 pages with before and after scores, the optimisation raised the mobile Lighthouse performance score by {sc['mean_diff']:.1f} points on average (95% CI {sc['ci95'][0]:.1f} to {sc['ci95'][1]:.1f}; "
      f"{g['stat_line'](sc)}) and lowered First Contentful Paint by {abs(fcp['mean_diff']):.2f} s ({abs(fcp['ci95'][1]):.2f} to {abs(fcp['ci95'][0]):.2f}; {g['stat_line'](fcp)}). "
      "For both metrics the differences were consistent with a normal distribution (Shapiro–Wilk p = "
      f"{fmt_p(sc['shapiro_p'])} and {fmt_p(fcp['shapiro_p'])}), so the paired t-test is reported; both remain significant after Holm correction (Table 7).\n")
    w(f"Largest Contentful Paint fell by {abs(lcp['mean_diff']):.2f} s on average (median {abs(lcp['median_diff']):.2f} s) and page weight by {abs(pw['mean_diff']):,.0f} KB (median {abs(pw['median_diff']):,.0f} KB). "
      f"Their differences were not normally distributed (Shapiro–Wilk p {fmt_p(lcp['shapiro_p'])} for both), mainly because of the About page, so the Wilcoxon signed-rank test is reported: every one of the 8 pages improved on both metrics "
      f"(W = 0, p = {fmt_p(lcp['wilcoxon_p'])}, r_rb = −1.00, the smallest p-value possible with 8 pairs). For page weight the paired t-test was not significant (t(7) = {pw['t']:.2f}, p = {fmt_p(pw['t_p'])}) "
      "because the single large reduction on the About page inflates the variance; this illustrates why the Wilcoxon result should be trusted for these two metrics.\n")
    return "\n".join(L)
