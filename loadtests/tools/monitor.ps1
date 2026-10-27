# Sample Apache (httpd.exe), MariaDB (mysqld.exe) and k6 CPU / memory every few
# seconds and append to a CSV until the process is stopped.
# CPU columns are % of the whole machine (process % / logical processors), so
# 100 means every core is busy. Memory is the private working set in MB, summed
# over all httpd processes (parent + worker child).
param([Parameter(Mandatory)][string]$Out, [int]$Interval = 2)

$cores = [Environment]::ProcessorCount
$counters = '\Process(httpd*)\% Processor Time', '\Process(httpd*)\Working Set - Private',
            '\Process(mysqld)\% Processor Time', '\Process(mysqld)\Working Set - Private',
            '\Process(k6*)\% Processor Time', '\Processor(_Total)\% Processor Time', '\Memory\Available MBytes'
'timestamp,httpd_cpu_pct,httpd_mem_mb,mysqld_cpu_pct,mysqld_mem_mb,k6_cpu_pct,total_cpu_pct,available_mem_mb' |
    Set-Content -Path $Out -Encoding ascii

while ($true) {
    $s = (Get-Counter -Counter $counters -SampleInterval $Interval -MaxSamples 1 -ErrorAction SilentlyContinue).CounterSamples
    if (-not $s) { continue }
    function sum($pattern) { ($s | Where-Object { $_.Path -like $pattern } | Measure-Object CookedValue -Sum).Sum }
    $row = '{0},{1:F2},{2:F1},{3:F2},{4:F1},{5:F2},{6:F2},{7:F0}' -f (Get-Date -Format 'yyyy-MM-ddTHH:mm:ss.fff'),
        ((sum '*\process(httpd*)\% processor time') / $cores), ((sum '*\process(httpd*)\working set - private') / 1MB),
        ((sum '*\process(mysqld)\% processor time') / $cores), ((sum '*\process(mysqld)\working set - private') / 1MB),
        ((sum '*\process(k6*)\% processor time') / $cores), (sum '*\processor(_total)\% processor time'),
        (sum '*\memory\available mbytes')
    Add-Content -Path $Out -Value $row -Encoding ascii
}
