# Load and concurrency tests (Grafana k6)

Performance-efficiency tests for Reserve-a-Lab (Objective 2). Results:
`performance-report/load-report.html` and `performance-report/manuscript-tables.md`.

**Target: localhost only.** `lib/common.js` refuses any `BASE_URL` whose host is not
localhost. Run everything from this folder in Windows PowerShell; requires k6 (v2.2.0
used), Python 3 with numpy and scipy, and XAMPP's Apache and MariaDB running.

## Before testing (safety)

1. Back up the database (outside the web root):
   `mysqldump -uroot --single-transaction --triggers --hex-blob --databases dbadmin > C:\xampp\db-backups\dbadmin_pre_loadtest_<ts>.sql`
   and save `CHECKSUM TABLE` output for every table (`checksums_pre_<ts>.txt`).
2. Block outbound SMTP from Apache/PHP (elevated PowerShell):
   `tools\firewall_smtp.ps1 -Action block`, then check with `php tools\smtp_probe.php`
   (must print `BLOCKED`). Submissions and approvals send e-mail through Gmail inside the request.
   Do not use the hosts file for this: on this machine the write was blocked and left the file empty.
3. Create the fixtures and the baseline every test restores:
   `python tools\make_fixtures.py` (writes `sql\fixtures.sql` and the shared password in `.env`),
   load it with `mysql -uroot dbadmin < sql\fixtures.sql`, then dump the result to
   `C:\xampp\db-backups\dbadmin_fixture_baseline.sql`.
   Fixtures: 500 teacher and 5 admin accounts (`lt.*@example.invalid`), 10 high-stock
   consumables, the `LT Last Units` / `LT Manual Deduct` items, the deduction-log table;
   every real e-mail address is rewritten to `@example.invalid`.

## Tests

| Test | Command |
|---|---|
| Smoke (1 journey each) | `k6 run -e THINK_SCALE=0 -e RUN_NAME=smoke smoke.js` |
| 1. Load, 50 users, 10 min | `tools\run_test.ps1 -Name load_50 -Script load.js -K6Env PEAK=50` |
| 2. Stress staircase | `tools\stress.ps1` (10, 25, 50, 100, 200, 300, ... until p95 > 10 s or errors > 5%) |
| 3. Spike 5 → CAP → 5 | `tools\run_test.ps1 -Name spike_200 -Script spike.js -K6Env CAP=200` |
| 4. Soak, 30 min | `tools\run_test.ps1 -Name soak_100 -Script soak.js -K6Env VUS=100` |
| 5a. Same lab slot | `tools\concurrency.ps1 -Test slot -Reps 5` and `-Mode sequential -Reps 1 -N 3` |
| 5b. Last units | `tools\concurrency.ps1 -Test inventory -Reps 5` and `-Mode sequential -Reps 1 -N 3` |
| 5c. Manual deduction (control) | `tools\concurrency.ps1 -Test manual -Reps 5` |

`run_test.ps1` restores the fixture baseline, starts `tools\monitor.ps1` (httpd/mysqld/k6
CPU and memory every 2 s), runs k6 and saves raw output to `results\raw\`:
`<run>.csv.gz` (every k6 sample), `<run>_resources.csv`, PHP/Apache log lines written during
the run; plus `results\<run>_summary.json`, `<run>_console.txt` and `<run>_server.json`.
`concurrency.ps1` also saves the database query output after each repetition
(`results\conc_*_db.txt` / `_db.json`).

## Analysis and report

```
python analysis\analyze.py            # results\analysis\<run>.json for every run
python analysis\lighthouse_stats.py   # paired t-test / Wilcoxon on the Lighthouse data
python analysis\build_report.py       # performance-report\load-report.html + manuscript-tables.md
```

## After testing

1. Restore and verify: `tools\db_restore.ps1 -File <pre-test dump> -Checksums <checksums file>`
   (drops `dbadmin` first, so tables created during the tests go too).
2. Remove the SMTP block (elevated): `tools\firewall_smtp.ps1 -Action unblock`.

`.htaccess` here denies web access to this folder.
