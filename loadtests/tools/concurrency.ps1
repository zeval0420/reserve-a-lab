# Run a concurrency test (5a slot, 5b inventory, 5c manual) for several repetitions.
# Before each repetition the fixture baseline is restored (identical starting data);
# after it, the database is queried directly and the raw output saved as evidence in
# results\<name>_db.txt, plus a machine-readable results\<name>_db.json.
# Example: tools\concurrency.ps1 -Test slot -Reps 5
param(
    [Parameter(Mandatory)][ValidateSet('slot', 'inventory', 'manual')][string]$Test,
    [ValidateSet('race', 'sequential')][string]$Mode = 'race',
    [int]$Reps = 5,
    [int]$N = 20,
    [int]$Stock = 5,
    [int]$Qty = 2
)
$root = Split-Path $PSScriptRoot -Parent
Set-Location $root
$mysql = 'C:\xampp\mysql\bin\mysql.exe'
$baseline = 'C:\xampp\db-backups\dbadmin_fixture_baseline.sql'
function Sql($q) { & $mysql -uroot dbadmin --table -e $q }
function SqlScalar($q) { (& $mysql -uroot dbadmin -N -e $q) -join '' }

for ($rep = 1; $rep -le $Reps; $rep++) {
    $name = "conc_${Test}_${Mode}_r$rep"
    & "$PSScriptRoot\db_restore.ps1" -File $baseline
    $lastId = SqlScalar "SELECT id FROM scilab_inventory WHERE productID='LT-LAST'"
    $manualId = SqlScalar "SELECT id FROM scilab_inventory WHERE productID='LT-MANUAL'"
    Sql "UPDATE scilab_inventory SET quantity=$Stock WHERE id IN ($lastId, $manualId)" | Out-Null

    switch ($Test) {
        'slot' {
            $date = '2027-03-{0:D2}' -f (14 + $rep)
            $envs = @("N=$N", "MODE=$Mode", "DATE=$date", 'LAB=Science Laboratory 3')
            $script = 'concurrency_slot.js'
        }
        'inventory' { $envs = @("N=$N", "MODE=$Mode", "QTY=$Qty"); $script = 'concurrency_inventory.js' }
        'manual' { $envs = @("N=$N", "ITEM_ID=$manualId"); $script = 'concurrency_manual.js' }
    }
    & "$PSScriptRoot\run_test.ps1" -Name $name -Script $script -K6Env $envs -NoRestore | Out-Host

    $evidence = @("# $name  ($(Get-Date -Format o))", "# k6 env: $($envs -join ' ')", '')
    switch ($Test) {
        'slot' {
            $q1 = "SELECT id, requesterEmployeeID, scilabName, inclusiveDate, inclusiveTime, statusScilabPersonnel, lab_personnel_approved_at FROM scilab_form_requests WHERE scilabName='Science Laboratory 3' AND inclusiveDate='$date' ORDER BY id;"
            $q2 = "SELECT COUNT(*) AS requests_for_slot, COALESCE(SUM(statusScilabPersonnel='Approved'),0) AS approved_for_slot, COALESCE(SUM(statusScilabPersonnel='Pending'),0) AS still_pending FROM scilab_form_requests WHERE scilabName='Science Laboratory 3' AND inclusiveDate='$date' AND inclusiveTime='9:00 AM to 11:00 AM';"
            $evidence += "mysql> $q1"; $evidence += Sql $q1; $evidence += "mysql> $q2"; $evidence += Sql $q2
            $row = (& $mysql -uroot dbadmin -N -e $q2) -split "`t"
            $json = [ordered]@{ test = 'slot'; mode = $Mode; rep = $rep; date = $date; requests = [int]$row[0]; approved = [int]$row[1]; pending = [int]$row[2]; pass = ([int]$row[1] -le 1) }
        }
        'inventory' {
            $q1 = "SELECT f.id, f.requesterEmployeeID, f.statusScilabPersonnel, m.item, m.quantity AS qty_requested FROM scilab_form_requests f JOIN scilab_material_requests m ON m.formID=f.id WHERE m.item='LT Last Units' ORDER BY f.id;"
            $q2 = "SELECT id, item, quantity AS quantity_now FROM scilab_inventory WHERE id=$lastId;"
            $q3 = "SELECT COUNT(*) AS approved_requests, COALESCE(SUM(m.quantity),0) AS units_allocated_to_approved, $Stock AS starting_stock FROM scilab_form_requests f JOIN scilab_material_requests m ON m.formID=f.id WHERE m.item='LT Last Units' AND f.statusScilabPersonnel='Approved';"
            foreach ($q in $q1, $q2, $q3) { $evidence += "mysql> $q"; $evidence += Sql $q }
            $qtyNow = [int](SqlScalar "SELECT quantity FROM scilab_inventory WHERE id=$lastId")
            $r3 = (& $mysql -uroot dbadmin -N -e $q3) -split "`t"
            $json = [ordered]@{ test = 'inventory'; mode = $Mode; rep = $rep; starting_stock = $Stock; qty_per_request = $Qty; requests = $N
                quantity_now = $qtyNow; approved = [int]$r3[0]; units_allocated = [int]$r3[1]
                went_negative = ($qtyNow -lt 0); over_allocated = ([int]$r3[1] -gt $Stock)
                pass = (($qtyNow -ge 0) -and ([int]$r3[1] -le $Stock)) }
        }
        'manual' {
            $q1 = "SELECT id, item, quantity AS quantity_now FROM scilab_inventory WHERE id=$manualId;"
            $q2 = "SELECT COUNT(*) AS deductions_logged, COALESCE(SUM(quantity_deducted),0) AS units_deducted, MIN(quantity_after) AS lowest_quantity_after FROM scilab_inventory_deductions WHERE inventory_id=$manualId;"
            $q3 = "SELECT quantity_before, quantity_after, deducted_by, deducted_at FROM scilab_inventory_deductions WHERE inventory_id=$manualId ORDER BY id;"
            foreach ($q in $q1, $q2, $q3) { $evidence += "mysql> $q"; $evidence += Sql $q }
            $qtyNow = [int](SqlScalar "SELECT quantity FROM scilab_inventory WHERE id=$manualId")
            $r2 = (& $mysql -uroot dbadmin -N -e $q2) -split "`t"
            $json = [ordered]@{ test = 'manual'; rep = $rep; starting_stock = $Stock; requests = $N; quantity_now = $qtyNow
                deductions = [int]$r2[0]; units_deducted = [int]$r2[1]
                pass = (($qtyNow -ge 0) -and ([int]$r2[1] -le $Stock) -and ([int]$r2[1] + $qtyNow -eq $Stock)) }
        }
    }
    $evidence | Set-Content "results\${name}_db.txt" -Encoding utf8
    $json | ConvertTo-Json | Set-Content "results\${name}_db.json" -Encoding utf8
    "== $name : " + ($json | ConvertTo-Json -Compress)
}
