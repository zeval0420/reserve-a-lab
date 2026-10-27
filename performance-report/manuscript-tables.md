# Reserve-a-Lab performance efficiency: tables and results text for the manuscript

Response times in the tables are in milliseconds (ms). p95 = 95th-percentile response time. All tests were run on 27 October 2026 against a local XAMPP installation; see Table 1.

**Table 1.** Test environment

| Item | Value |
|---|---|
| Machine | Laptop, Intel Core i5-1240P (12 cores / 16 threads), 15.7 GB RAM, Windows 11 Home 10.0.26200 |
| Web stack | XAMPP 8.2.12: Apache 2.4.58 (mpm_winnt, ThreadsPerChild 150, KeepAlive On, KeepAliveTimeout 5 s), PHP 8.2.12 as Apache module, OPcache off |
| Database | MariaDB 12.3.2 (system tables still from 10.4.8), InnoDB, REPEATABLE-READ, autocommit on, max_connections 151, innodb_buffer_pool_size 16 MB |
| Load tool | Grafana k6 v2.2.0 (go1.26.5), on the same machine as the server |
| Resource sampling | Windows performance counters (Get-Counter) every 2 s: httpd.exe and mysqld.exe CPU and private working set, k6 CPU, total CPU, available memory |
| Date | 27 October 2026 |
| Target | http://localhost/reserve-a-lab/ only (scripts refuse any other host) |

**Table 2.** Load test: response time per endpoint at the expected peak (50 concurrent users, 10-minute hold)

| Endpoint | Requests | Mean (ms) | Median (ms) | p90 (ms) | p95 (ms) | Errors |
|---|---:|---:|---:|---:|---:|---:|
| Login page | 1,298 | 18 | 20 | 29 | 31 | 0 |
| Log in (AJAX) | 1,298 | 27 | 23 | 38 | 40 | 0 |
| Requester home | 981 | 28 | 30 | 40 | 43 | 0 |
| Home: store date | 981 | 13 | 13 | 15 | 16 | 0 |
| Home: day snapshot | 981 | 20 | 22 | 29 | 30 | 0 |
| Request form | 973 | 29 | 31 | 40 | 43 | 0 |
| Form: disabled dates | 973 | 25 | 24 | 38 | 40 | 0 |
| Form: subjects | 973 | 26 | 30 | 35 | 38 | 0 |
| Form: sections | 973 | 26 | 30 | 35 | 37 | 0 |
| Conflict check | 2,269 | 26 | 29 | 37 | 40 | 0 |
| Submit request | 976 | 177 | 186 | 234 | 244 | 0 |
| Request tracker | 979 | 24 | 26 | 35 | 38 | 0 |
| Log out | 1,298 | 2 | 2 | 4 | 5 | 0 |
| Admin home | 319 | 34 | 35 | 46 | 50 | 0 |
| Admin: lab availability | 319 | 20 | 18 | 31 | 33 | 0 |
| Admin: notifications | 319 | 24 | 27 | 35 | 37 | 0 |
| Admin: pending count | 318 | 22 | 28 | 33 | 34 | 0 |
| Admin approvals | 319 | 219 | 211 | 356 | 375 | 0 |
| Approve request | 318 | 200 | 211 | 254 | 264 | 0 |
| Inventory | 317 | 22 | 24 | 34 | 36 | 0 |
| **All requests** | **17,182** | **38** | **25** | **47** | **187** | **0** |

Throughput: 28.6 requests/s. Error rate: 0.00%.

**Table 3.** Stress test: response time, throughput, errors and CPU per step (3-minute hold per step)

| Virtual users | Throughput (req/s) | Mean (ms) | Median (ms) | p90 (ms) | p95 (ms) | Error rate (%) | Apache CPU (%) | MariaDB CPU (%) | Within limits |
|---:|---:|---:|---:|---:|---:|---:|---:|---:|---|
| 10 | 5.8 | 36 | 25 | 44 | 188 | 0.00 | 0.4 | 0.1 | Yes |
| 25 | 14.4 | 37 | 25 | 45 | 192 | 0.00 | 1.1 | 0.2 | Yes |
| 50 | 28.6 | 36 | 26 | 45 | 176 | 0.00 | 2.1 | 0.4 | Yes |
| 100 | 57.6 | 34 | 24 | 44 | 158 | 0.00 | 4.3 | 0.9 | Yes |
| 200 | 94.2 | 404 | 24 | 202 | 4,145 | 0.00 | 10.8 | 2.5 | Yes |
| 300 | – | – | – | – | – | – | – | – | Not completed |

Limits: p95 ≤ 10 s and error rate ≤ 5%. CPU is the mean share of the whole machine (16 logical processors). The 300-user step was stopped by the host for low memory.

**Table 4.** Spike test: 5 → 200 → 5 virtual users

| Phase | Duration | Requests | Throughput (req/s) | Median (ms) | p95 (ms) | Max (ms) | Errors |
|---|---|---:|---:|---:|---:|---:|---:|
| Baseline, 5 users | 60 s | 233 | 3.9 | 27 | 51 | 305 | 0 |
| Jump to 200 users | 10 s | 1,009 | 100.9 | 22 | 46 | 3,056 | 0 |
| Peak, 200 users | 60 s | 5,765 | 96.1 | 22 | 3,106 | 9,581 | 0 |
| Drop to 5 users | 10 s | 315 | 31.5 | 28 | 2,624 | 3,470 | 0 |
| Recovery, 5 users | 180 s | 521 | 2.9 | 25 | 207 | 278 | 0 |

**Table 5.** Soak test: 100 virtual users for 30 minutes

| Measure | Value |
|---|---|
| Requests | 92,763 |
| Throughput | 51.5 req/s |
| Mean / median / p90 / p95 | 88 / 26 / 112 / 192 ms |
| Errors | 13 (0.014%; 4 timeout, 9 connection) |
| Trend of per-minute p95, full 30 min* | +11.25 ms/min (R² = 0.14, p = .042) |
| Trend of per-minute median, full 30 min* | +0.89 ms/min (R² = 0.29, p = .0028) |
| Trend of Apache memory, full 30 min* | -1.34 MB/min (R² = 0.07, p < .001) |
| Trend of MariaDB memory, full 30 min* | -1.19 MB/min (R² = 0.82, p < .001) |
| Trend of Apache CPU, full 30 min* | +0.43 percentage points/min (R² = 0.41, p < .001) |
| Trend of MariaDB CPU, full 30 min* | +0.30 percentage points/min (R² = 0.55, p < .001) |
| Apache memory, minutes 0–15 | mean 468 MB, trend +0.29 MB/min (R² = 0.05) |
| MariaDB memory, minutes 0–15 | mean 62 MB, trend −0.35 MB/min (R² = 0.58) |
| Per-minute p95, minutes 0–15 | trend −2.6 ms/min (p = .003) |
| Admin approvals page, median per 5-min block | 0.17, 0.50, 0.89, 2.95, 3.90, 4.90, 4.18 s |

\* From minute 15 other programmes on the test laptop loaded its CPU and memory (machine CPU 21% → 53%, available memory down to 22 MB); full-run trends include these episodes. Minutes 0–15 are undisturbed.

**Table 6.** Concurrency tests: database state after each repetition (20 simultaneous users)

| Test | Rep. 1 | Rep. 2 | Rep. 3 | Rep. 4 | Rep. 5 | Sequential control | Correct value | Result |
|---|---|---|---|---|---|---|---|---|
| 5a. Approved requests for one lab slot | 20 | 20 | 19 | 18 | 17 | 3 of 3 | ≤ 1 | Fail |
| 5b. Approved requests for the last 5 units (2 units each) | 20 | 20 | 20 | 20 | 20 | 3 of 3 | ≤ 2 | Fail |
| 5b. Units allocated against 5 in stock | 40 | 40 | 40 | 40 | 40 | 6 | ≤ 5 | Fail |
| 5b. Stock after approvals | 0 | -1 | 0 | 0 | -1 | 0 | ≥ 0 | Fail (2 of 5 below zero) |
| 5c. Control: units deducted with the transactional manual deduction | 5 | 5 | 5 | 5 | 5 | – | 5 | Pass |

Sequential control: three requests approved one at a time, 3 s apart, through the page's actual request path.

**Table 7.** Paired comparison of Lighthouse mobile results before and after optimisation (n = 8 pages)

| Metric | Mean before | Mean after | Mean difference [95% CI] | Shapiro–Wilk W (p) | Paired t(7) (p) | Cohen's d_z | Wilcoxon W (p) | r_rb | Test to report |
|---|---:|---:|---|---|---|---:|---|---:|---|
| Performance score (points) | 60.9 | 86.0 | 25.1 [23.2, 27.0] | 0.839 (.074) | 30.96 (< .001) | 10.95 | 0 (.0078) | 1.00 | paired t-test |
| LCP (s) | 8.67 | 3.67 | -5.00 [-7.46, -2.54] | 0.545 (< .001) | -4.81 (.0019) | -1.70 | 0 (.0078) | -1.00 | Wilcoxon signed-rank |
| FCP (s) | 4.83 | 2.62 | -2.22 [-2.47, -1.96] | 0.902 (.304) | -20.82 (< .001) | -7.36 | 0 (.0078) | -1.00 | paired t-test |
| Page weight (KB) | 1,743 | 497 | -1,246 [-2,665, 173] | 0.463 (< .001) | -2.08 (.077) | -0.73 | 0 (.0078) | -1.00 | Wilcoxon signed-rank |

Differences are after − before. The login page is excluded (no before value: Lighthouse NO_FCP). Holm-adjusted p-values: Performance score t < .001, Wilcoxon .031; LCP t .0039, Wilcoxon .031; FCP t < .001, Wilcoxon .031; Page weight t .077, Wilcoxon .031.

---

# Results text

## Load test

With 50 concurrent users, the expected peak, held for 10 minutes, Reserve-a-Lab served 17,182 requests at 28.6 requests per second without a single error. The median response time over all requests was 25 ms, the 90th percentile 47 ms and the 95th percentile 187 ms (Table 2).

Seventeen of the 20 endpoints had a 95th-percentile response time below 0.1 s, within Nielsen's limit for an instantaneous response. The three slower ones were the admin approvals page (p95 375 ms), request approval (264 ms) and request submission (244 ms), all within the 1 s limit. Submission and approval are the only actions that write several rows and prepare notification e-mails within the request; the approvals page lists every pending request. Apache used on average 2.3% and MariaDB 0.6% of the machine's CPU.

## Stress test

Throughput grew in proportion to the number of users, from 5.8 requests per second at 10 users to 57.6 at 100 and 94.2 at 200, and no step produced an error (Table 3). From 10 to 100 users the median stayed between 24 and 26 ms and the 95th percentile between 158 and 192 ms. At 200 users the median was unchanged (24 ms) but the 95th percentile rose to 4.14 s and the maximum to 6.65 s. The 300-user step was stopped by the host operating system for lack of memory before it completed, so capacity, defined as the last step with a 95th percentile ≤ 10 s and an error rate ≤ 5%, is reported as at least 200 concurrent users.

The slowdown at 200 users was not caused by processor load: machine CPU averaged 34% and Apache 10.8%. The slow requests occurred at a steady rate throughout the step, the delay was entirely in the time to first byte, and the slow responses clustered between 3 and 5 s, bounded by Apache's 5 s keep-alive timeout. They affected only requests that followed a pause of 3 s or more. This pattern is consistent with exhaustion of Apache's pool of 150 worker threads on Windows, where each idle keep-alive connection holds a thread for up to 5 s, so requests on new connections must wait once there are more users than threads.

## Spike test

When the load jumped from 5 to 200 users in 10 seconds, the system kept serving all requests: there were no errors in any phase (Table 4). During the 1-minute peak, throughput reached 96.1 requests per second, the median response time stayed at 22 ms and the 95th percentile rose to 3.11 s, with a maximum of 9.58 s, the same pattern as the 200-user stress step.

Recovery was immediate. In the first 10 seconds after the load returned to 5 users the 95th percentile was 72 ms, and over the 3-minute recovery phase the median (25 ms) and 95th percentile (207 ms) were back at the low-load level of the stress test (median 25 ms, p95 188 ms at 10 users). No backlog of requests remained after the spike.

## Soak test

At 100 users, half the measured capacity, for 30 minutes, the system served 92,763 requests at 51.5 requests per second with a median of 26 ms and a 95th percentile of 192 ms; 13 requests (0.014%) failed (Table 5). During the first 15 minutes, before the test machine was disturbed, memory did not grow: Apache's private memory averaged 468 MB with a trend of +0.29 MB per minute (R² = 0.05) and MariaDB's averaged 62 MB with a slight downward trend, and the per-minute 95th percentile did not rise (−2.6 ms per minute). There is therefore no indication of a memory leak; the memory changes after minute 15 reflect Windows trimming working sets under host memory pressure.

One page did slow down steadily at constant load. The admin approvals page, which lists all pending requests without paging, slowed from a median of 0.17 s in the first 5 minutes to 0.89 s in minutes 10–15 and 4.90 s in minutes 20–25, because each requester session submitted a request and about 4,000 requests were still pending at the end. Apache CPU rose from 4.9% to 11.3% and MariaDB CPU from 0.7% to 8.5% of the machine over the run, consistent with more work per request as the data grew.

From about minute 15, other programmes on the test laptop competed for it: mean machine CPU rose from 21% to 53% and available memory later fell to 22 MB. At about minute 21, with the machine's CPU at 92–97%, all requests in flight stalled for about 2 minutes, producing the 4 timeouts and 9 connection errors and the largest per-minute outliers. These episodes are attributable to the test machine, not the application, and they account for the full-run increase of the per-minute 95th percentile (+11.3 ms per minute, R² = 0.14); the growth of the approvals page began before them.

## Concurrency (double booking)

Reserve-a-Lab did not prevent double booking. In each of five repetitions, 20 users submitted requests for the same laboratory, date and time slot at the same instant (all requests sent within 4 ms), and 20 administrator sessions approved them at the same instant. The database then held 20, 20, 19, 18, 17 approved requests for that single slot, where at most one should exist (Table 6). This happened even though every simulated administrator first ran the system's conflict check correctly and skipped the approval if it reported an approved conflict: all checks completed before the first approval was written. The approval action itself does not check for conflicts, uses no transaction or lock, and the database has no constraint that would reject a second booking.

Double booking also occurred without concurrency. When three administrators approved three requests for the same slot one after another, 3 seconds apart, through the approvals page's actual request path, all three were approved. The page sends its conflict check without the security token that the endpoint requires, so the check is rejected (HTTP 403) and the administrator never sees the conflict warning. The submission endpoint used by the request form does not check the slot either.

Inventory quantities were over-allocated in the same way. With 5 units of an item in stock, 20 simultaneous requests for 2 units each were all accepted and all approved in every repetition, allocating 40 units against 5. The stock went below zero (−1) in 2 of the 5 repetitions, because the approval reads the quantity and then subtracts in a separate statement. Over-allocation also occurred when approvals were made one at a time (3 approved, 6 units against 5), because approval does not refuse a request that the stock cannot cover. As a control, the inventory page's manual deduction, which locks the row inside a transaction, passed the same 20-user test in all 5 repetitions: exactly 5 deductions succeeded and the stock ended at 0.

## Lighthouse before and after (statistics)

Across the 8 pages with before and after scores, the optimisation raised the mobile Lighthouse performance score by 25.1 points on average (95% CI 23.2 to 27.0; t(7) = 30.96, p < .001, d_z = 10.95; Wilcoxon W = 0, p = .0078, r_rb = 1.00) and lowered First Contentful Paint by 2.22 s (1.96 to 2.47; t(7) = -20.82, p < .001, d_z = -7.36; Wilcoxon W = 0, p = .0078, r_rb = -1.00). For both metrics the differences were consistent with a normal distribution (Shapiro–Wilk p = .074 and .304), so the paired t-test is reported; both remain significant after Holm correction (Table 7).

Largest Contentful Paint fell by 5.00 s on average (median 4.15 s) and page weight by 1,246 KB (median 655 KB). Their differences were not normally distributed (Shapiro–Wilk p < .001 for both), mainly because of the About page, so the Wilcoxon signed-rank test is reported: every one of the 8 pages improved on both metrics (W = 0, p = .0078, r_rb = −1.00, the smallest p-value possible with 8 pairs). For page weight the paired t-test was not significant (t(7) = -2.08, p = .077) because the single large reduction on the About page inflates the variance; this illustrates why the Wilcoxon result should be trusted for these two metrics.
