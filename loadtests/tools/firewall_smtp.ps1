# Block (or unblock) outbound SMTP from Apache/PHP for the duration of the load
# tests, so the application's notification e-mails never reach Gmail.
# Needs an elevated PowerShell.  Usage: firewall_smtp.ps1 -Action block|unblock
param([ValidateSet('block', 'unblock')][string]$Action = 'block')
$name = 'reserve-a-lab-loadtest-block-smtp'
Get-NetFirewallRule -DisplayName $name -ErrorAction SilentlyContinue | Remove-NetFirewallRule
if ($Action -eq 'block') {
    foreach ($prog in 'C:\xampp\apache\bin\httpd.exe', 'C:\xampp\php\php.exe') {
        New-NetFirewallRule -DisplayName $name -Direction Outbound -Action Block -Protocol TCP `
            -RemotePort 25, 465, 587 -Program $prog -Profile Any | Out-Null
    }
}
Get-NetFirewallRule -DisplayName $name -ErrorAction SilentlyContinue |
    Select-Object DisplayName, Enabled, Direction, Action |
    Out-File 'C:\xampp\htdocs\reserve-a-lab\loadtests\results\firewall_state.txt' -Encoding utf8
