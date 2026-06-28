<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$pdo = db();

$totIncome   = (float) $pdo->query("SELECT COALESCE(SUM(amount),0) FROM finance_income   WHERE is_deleted=0")->fetchColumn();
$totExpenses = (float) $pdo->query("SELECT COALESCE(SUM(amount),0) FROM finance_expenses WHERE is_deleted=0")->fetchColumn();
$balance     = $totIncome - $totExpenses;

$events = $pdo->query("
    SELECT e.*,
        COALESCE(SUM(i.amount),0) AS income,
        COALESCE(SUM(x.amount),0) AS expenses
    FROM finance_events e
    LEFT JOIN finance_income   i ON i.event_id=e.id AND i.is_deleted=0
    LEFT JOIN finance_expenses x ON x.event_id=e.id AND x.is_deleted=0
    WHERE e.is_archived=0
    GROUP BY e.id
    ORDER BY e.created_at DESC
")->fetchAll();

$recent = $pdo->query("
    SELECT 'income' AS type, i.date, i.source AS description, i.amount, i.category, e.name AS event_name, e.color
    FROM finance_income i LEFT JOIN finance_events e ON e.id=i.event_id WHERE i.is_deleted=0
    UNION ALL
    SELECT 'expense', x.date, x.description, x.amount, NULL, e.name, e.color
    FROM finance_expenses x LEFT JOIN finance_events e ON e.id=x.event_id WHERE x.is_deleted=0
    ORDER BY date DESC, amount DESC LIMIT 20
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Financial Transparency — School Finance</title>
  <link rel="stylesheet" href="/assets/css/app.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js" defer></script>
  <script src="/assets/js/app.js" defer></script>
</head>
<body>

<!-- Banner -->
<div class="public-banner">
  <span>🌐 <strong>Public Financial Dashboard</strong> — Read-only transparency view</span>
  <a href="/login.php">Staff Login →</a>
</div>

<div style="max-width:1100px;margin:0 auto;padding:24px 16px;">

  <!-- Header -->
  <div style="text-align:center;margin-bottom:32px;">
    <div style="width:52px;height:52px;background:linear-gradient(135deg,#3B82F6,#1D4ED8);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:26px;margin:0 auto 12px;">💼</div>
    <h1 style="font-size:24px;font-weight:700;color:var(--text-primary);">School Finance Report</h1>
    <p style="color:var(--text-muted);font-size:14px;margin-top:4px;">Public financial transparency dashboard</p>
  </div>

  <!-- Stat cards -->
  <div class="stat-grid" style="margin-bottom:24px;">
    <div class="stat-card blue">
      <div class="stat-label">Current Balance</div>
      <div class="stat-value" data-count="<?= abs($balance) ?>" data-prefix="<?= $balance<0?'-₱':'₱' ?>">₱0.00</div>
      <div class="stat-meta">As of <?= date('M d, Y') ?></div>
      <div class="stat-icon">💰</div>
    </div>
    <div class="stat-card green">
      <div class="stat-label">Total Income</div>
      <div class="stat-value" data-count="<?= $totIncome ?>" data-prefix="₱">₱0.00</div>
      <div class="stat-icon">📈</div>
    </div>
    <div class="stat-card red">
      <div class="stat-label">Total Expenses</div>
      <div class="stat-value" data-count="<?= $totExpenses ?>" data-prefix="₱">₱0.00</div>
      <div class="stat-icon">📉</div>
    </div>
    <div class="stat-card amber">
      <div class="stat-label">Events / Projects</div>
      <div class="stat-value" data-count="<?= count($events) ?>">0</div>
      <div class="stat-icon">📅</div>
    </div>
  </div>

  <!-- Events summary -->
  <?php if (!empty($events)): ?>
  <div class="card" style="margin-bottom:24px;">
    <div class="card-header"><span class="card-title">Events / Projects Summary</span></div>
    <div class="card-body" style="padding:0;">
      <div class="table-wrap" style="border:none;border-radius:0;">
        <table class="data-table">
          <thead><tr>
            <th>Event</th><th>Status</th><th class="text-right">Income</th><th class="text-right">Expenses</th><th class="text-right">Balance</th><th>Utilization</th>
          </tr></thead>
          <tbody>
            <?php foreach ($events as $ev):
                $bal = $ev['income'] - $ev['expenses'];
                $pct = $ev['income'] > 0 ? min(100, round($ev['expenses']/$ev['income']*100)) : 0;
                $bCls = $pct>=90?'red':($pct>=60?'amber':'green');
                $sCls = match($ev['status']){'Active'=>'badge-green','Completed'=>'badge-blue',default=>'badge-gray'};
            ?>
            <tr>
              <td>
                <div class="flex items-center gap-8">
                  <?php if ($ev['icon']): ?><span><?= h($ev['icon']) ?></span><?php endif; ?>
                  <span style="font-weight:500;"><?= h($ev['name']) ?></span>
                </div>
              </td>
              <td><span class="badge <?= $sCls ?>"><?= h($ev['status']) ?></span></td>
              <td class="amount-in text-right"><?= format_money($ev['income']) ?></td>
              <td class="amount-out text-right"><?= format_money($ev['expenses']) ?></td>
              <td class="text-right" style="font-weight:600;color:<?= $bal>=0?'var(--success)':'var(--danger)' ?>;font-family:'DM Mono',monospace;"><?= format_money($bal) ?></td>
              <td style="min-width:120px;">
                <div class="progress-bar-wrap"><div class="progress-bar <?= $bCls ?>" style="width:<?= $pct ?>%"></div></div>
                <div style="font-size:11px;color:var(--text-muted);margin-top:2px;"><?= $pct ?>%</div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Recent transactions -->
  <div class="card">
    <div class="card-header"><span class="card-title">Recent Transactions</span></div>
    <?php if (empty($recent)): ?>
      <div class="empty-state"><div class="empty-icon">🧾</div><h3>No transactions yet</h3></div>
    <?php else: ?>
    <div class="table-wrap" style="border:none;border-radius:0;">
      <table class="data-table">
        <thead><tr>
          <th>Date</th><th>Description</th><th>Event</th><th>Type</th><th class="text-right">Amount</th>
        </tr></thead>
        <tbody>
          <?php foreach ($recent as $row): ?>
          <tr>
            <td class="mono"><?= h($row['date']) ?></td>
            <td><?= h($row['description']) ?><?= $row['category']?' <span class="badge badge-gray" style="font-size:10px;">'.h($row['category']).'</span>':'' ?></td>
            <td>
              <?php if ($row['event_name']): ?>
                <span class="badge" style="background:<?= h($row['color']) ?>22;color:<?= h($row['color']) ?>;">
                  <span class="event-dot" style="background:<?= h($row['color']) ?>;"></span>
                  <?= h($row['event_name']) ?>
                </span>
              <?php else: echo '<span class="text-muted">—</span>'; endif; ?>
            </td>
            <td>
              <?= $row['type']==='income'
                ? '<span class="badge badge-green">Income</span>'
                : '<span class="badge badge-red">Expense</span>' ?>
            </td>
            <td class="<?= $row['type']==='income'?'amount-in':'amount-out' ?> text-right">
              <?= $row['type']==='income'?'+':'-' ?><?= format_money($row['amount']) ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <p style="text-align:center;margin-top:24px;font-size:12px;color:var(--text-muted);">
    Data updated in real-time &nbsp;·&nbsp; <?= date('F d, Y H:i') ?>
  </p>
</div>

</body>
</html>
