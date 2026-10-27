# Test 2, stress staircase: 10, 25, 50, 100, 200, 300, then +100 per step.
# Each step is one k6 run of stress.js (20 s ramp, 3 min hold, 10 s ramp down) on a
# freshly restored baseline. The stop rule is applied to the 3-minute hold window:
# stop after the first step whose p95 > 10 s or error rate > 5%.
# Capacity = the last step that stayed within both limits.
param([int[]]$Steps = @(10, 25, 50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 1000))
$root = Split-Path $PSScriptRoot -Parent
Set-Location $root
$all = @()
$capacity = $null
foreach ($vus in $Steps) {
    $name = "stress_$vus"
    & "$PSScriptRoot\run_test.ps1" -Name $name -Script stress.js -K6Env @("VUS=$vus") | Out-Host
    $s = python "$PSScriptRoot\step_stats.py" "results\raw\$name.csv.gz" 20 180 | ConvertFrom-Json
    $s | Add-Member vus $vus
    $breach = ($s.p95_ms -gt 10000) -or ($s.error_pct -gt 5)
    $s | Add-Member within_limits (-not $breach)
    $all += $s
    "== step $vus VUs: p95 $($s.p95_ms) ms, errors $($s.error_pct)%, $($s.throughput_rps) req/s -> " + $(if ($breach) { 'LIMIT EXCEEDED' } else { 'ok' })
    $all | ConvertTo-Json | Set-Content results\stress_steps.json -Encoding utf8
    if ($breach) { break }
    $capacity = $vus
}
"Capacity (last step within limits): $capacity VUs"
[ordered]@{ capacity_vus = $capacity; rule = 'p95 <= 10000 ms and error rate <= 5% over the 3-min hold window' } |
    ConvertTo-Json | Set-Content results\stress_capacity.json -Encoding utf8
