# Replace the dbadmin database with the contents of a mysqldump file.
# The database is dropped first, so tables created during the tests disappear too.
# Optional -Checksums: compare CHECKSUM TABLE of every table with a saved file.
param([Parameter(Mandatory)][string]$File, [string]$Checksums)
$mysql = 'C:\xampp\mysql\bin\mysql.exe'
if (-not (Test-Path $File)) { throw "Dump not found: $File" }
& $mysql -uroot -e 'DROP DATABASE IF EXISTS dbadmin;'
# cmd's < redirection passes the file through byte for byte (PowerShell piping would re-encode it)
cmd /c "`"$mysql`" -uroot < `"$File`""
if ($LASTEXITCODE -ne 0) { throw "Restore of $File failed (exit $LASTEXITCODE)" }

if ($Checksums) {
    $tables = & $mysql -uroot -N -e "SELECT GROUP_CONCAT(CONCAT('``',table_name,'``') ORDER BY table_name) FROM information_schema.tables WHERE table_schema='dbadmin' AND table_type='BASE TABLE'"
    $now = & $mysql -uroot -N dbadmin -e "CHECKSUM TABLE $tables;"
    $saved = Get-Content $Checksums
    $diff = Compare-Object $saved $now
    if ($diff) { $diff | Format-Table -AutoSize | Out-String | Write-Output; throw 'Checksum mismatch after restore' }
    "Restore verified: $($now.Count) tables, all checksums match $Checksums"
}
