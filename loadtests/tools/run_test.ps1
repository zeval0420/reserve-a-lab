# Run one k6 test with resource sampling and server-side bookkeeping.
#   1. restore the fixture baseline (unless -NoRestore), so every test starts from the same data
#   2. reset MariaDB status counters, note PHP / Apache log sizes
#   3. start tools/monitor.ps1, run k6 (raw CSV + summary JSON), stop the monitor
#   4. save MariaDB status counters and the PHP / Apache log lines written during the run
# Example: tools\run_test.ps1 -Name load -Script load.js
param(
    [Parameter(Mandatory)][string]$Name,
    [Parameter(Mandatory)][string]$Script,
    [string[]]$K6Env = @(),
    [switch]$NoRestore
)
$ErrorActionPreference = 'Continue'
$root = Split-Path $PSScriptRoot -Parent
Set-Location $root
$mysql = 'C:\xampp\mysql\bin\mysql.exe'
$k6 = 'C:\Program Files\k6\k6.exe'
$baseline = 'C:\xampp\db-backups\dbadmin_fixture_baseline.sql'
$phpLog = 'C:\xampp\php\logs\php_error_log'
$apacheLog = 'C:\xampp\apache\logs\error.log'
New-Item -ItemType Directory -Force results\raw | Out-Null

if (-not $NoRestore) { & "$PSScriptRoot\db_restore.ps1" -File $baseline }
& $mysql -uroot -e 'FLUSH STATUS;'
$phpStart = (Get-Item $phpLog).Length
$apacheStart = (Get-Item $apacheLog).Length

$resources = "results\raw\${Name}_resources.csv"
$mon = Start-Process powershell.exe -WindowStyle Hidden -PassThru -ArgumentList '-NoProfile', '-ExecutionPolicy', 'Bypass',
    '-File', "$PSScriptRoot\monitor.ps1", '-Out', $resources
Start-Sleep -Seconds 6   # a few idle samples before the load starts

$kargs = @('run', '--quiet', '-e', "RUN_NAME=$Name", '--out', "csv=results/raw/$Name.csv.gz")
foreach ($e in $K6Env) { $kargs += @('-e', $e) }
$kargs += $Script
# Windows PowerShell 5.1 does not quote array arguments for Start-Process
$kargs = $kargs | ForEach-Object { if ($_ -match '\s') { '"' + $_ + '"' } else { $_ } }
$start = Get-Date
$p = Start-Process -FilePath $k6 -ArgumentList $kargs -NoNewWindow -Wait -PassThru `
    -RedirectStandardOutput "results\${Name}_console.txt" -RedirectStandardError "results\raw\${Name}_k6_stderr.txt"
$end = Get-Date
Start-Sleep -Seconds 6
Stop-Process -Id $mon.Id -Force -ErrorAction SilentlyContinue

$statusRows = & $mysql -uroot -N -e "SHOW GLOBAL STATUS WHERE Variable_name IN ('Max_used_connections','Threads_connected','Threads_created','Aborted_connects','Aborted_clients','Connection_errors_max_connections','Innodb_row_lock_waits','Innodb_row_lock_time','Innodb_row_lock_time_max','Innodb_deadlocks','Questions','Slow_queries','Innodb_buffer_pool_reads','Innodb_buffer_pool_read_requests')"
$status = [ordered]@{}
foreach ($row in $statusRows) { $k, $v = $row -split "`t"; $status[$k] = [double]$v }

function Read-Delta($path, $from) {
    $fs = [IO.File]::Open($path, 'Open', 'Read', 'ReadWrite')
    try { [void]$fs.Seek($from, 'Begin'); (New-Object IO.StreamReader($fs)).ReadToEnd() -split "`r?`n" | Where-Object { $_ } }
    finally { $fs.Close() }
}
$phpLines = @(Read-Delta $phpLog $phpStart)
$apacheLines = @(Read-Delta $apacheLog $apacheStart)
$phpLines | Set-Content "results\raw\${Name}_php_errors.log"
$apacheLines | Set-Content "results\raw\${Name}_apache_errors.log"
# Group PHP log lines by message type (text before the first variable part)
$phpTypes = [ordered]@{}
$phpLines | ForEach-Object { ($_ -replace '^\[[^\]]*\]\s*', '') -replace '\s+(to|for)\s+\S+@\S+.*$', '' -replace ':.*$', '' } |
    Group-Object | Sort-Object Count -Descending | ForEach-Object { $phpTypes[$_.Name] = $_.Count }

[ordered]@{
    name = $Name; script = $Script; k6_env = $K6Env; k6_exit = $p.ExitCode
    start = $start.ToString('o'); end = $end.ToString('o'); duration_s = [math]::Round(($end - $start).TotalSeconds, 1)
    mariadb_status = $status; php_log_lines = $phpLines.Count; php_log_types = $phpTypes
    apache_error_lines = $apacheLines.Count
} | ConvertTo-Json -Depth 4 | Set-Content "results\${Name}_server.json" -Encoding utf8

Get-Content "results\${Name}_console.txt"
"k6 exit $($p.ExitCode); $([math]::Round(($end - $start).TotalMinutes, 1)) min; Max_used_connections $($status['Max_used_connections']); PHP log lines $($phpLines.Count)"
