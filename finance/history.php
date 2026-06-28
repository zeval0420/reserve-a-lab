<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_login();

$pdo = db();

$filterTable = trim($_GET['table'] ?? '');
$filterUser  = trim($_GET['user'] ?? '');

$where  = ['1=1'];
$params = [];
if ($filterTable) { $where[] = 'h.table_name = ?'; $params[] = $filterTable; }
if ($filterUser)  { $where[] = 'h.changed_by LIKE ?'; $params[] = "%$filterUser%"; }

$whereStr = 'WHERE ' . implode(' AND ', $where);

$stmt = $pdo->prepare("SELECT * FROM finance_history {$whereStr} ORDER BY changed_at DESC LIMIT 200");
$stmt->execute($params);
$rows = $stmt->fetchAll();

layout_head('Edit History');
layout_sidebar('history');
?>

<form method="GET" class="filter-bar">
  <select class="form-control" name="table" data-autosubmit>
    <option value="">All Tables</option>
    <option value="finance_income"   <?= $filterTable==='finance_income'?'selected':'' ?>>Income</option>
    <option value="finance_expenses" <?= $filterTable==='finance_expenses'?'selected':'' ?>>Expenses</option>
    <option value="finance_events"   <?= $filterTable==='finance_events'?'selected':'' ?>>Events</option>
  </select>
  <input class="form-control" name="user" placeholder="Changed by…" value="<?= h($filterUser) ?>">
  <button class="btn btn-primary" type="submit">Filter</button>
  <a class="btn btn-secondary" href="?">Clear</a>
</form>

<div class="table-wrap">
  <?php if (empty($rows)): ?>
    <div class="empty-state"><div class="empty-icon">🕑</div><h3>No history yet</h3><p>Changes to records will appear here.</p></div>
  <?php else: ?>
  <table class="data-table">
    <thead><tr>
      <th>Timestamp</th><th>Table</th><th>Record ID</th><th>Field</th><th>Old Value</th><th>New Value</th><th>Changed By</th>
    </tr></thead>
    <tbody>
      <?php foreach ($rows as $row): ?>
      <tr>
        <td class="mono" style="white-space:nowrap;"><?= h($row['changed_at']) ?></td>
        <td><span class="badge badge-blue"><?= h(str_replace('finance_','',$row['table_name'])) ?></span></td>
        <td class="mono">#<?= $row['record_id'] ?></td>
        <td style="font-weight:500;"><?= h($row['field']) ?></td>
        <td style="font-size:12px;color:var(--text-muted);max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= h($row['old_value']) ?>"><?= h($row['old_value']) ?: '—' ?></td>
        <td style="font-size:12px;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= h($row['new_value']) ?>"><?= h($row['new_value']) ?: '—' ?></td>
        <td><span class="badge badge-gray"><?= h($row['changed_by']) ?></span></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<?php layout_foot(); ?>
