<?php
/**
 * Authenticated Dashboard
 */
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireLogin();

$summary  = getFinancialSummary();
$events   = getEventSummaries();
$recent   = getRecentTransactions(8);

// Monthly trend data (last 6 months)
$db = getDB();
$monthlySQL = "
    SELECT
        DATE_FORMAT(transaction_date, '%Y-%m') AS month,
        SUM(CASE WHEN type='income' THEN amount ELSE 0 END) AS income,
        SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) AS expenses
    FROM (
        SELECT 'income' AS type, amount, transaction_date FROM sf_income WHERE deleted_at IS NULL
        UNION ALL
        SELECT 'expense' AS type, amount, transaction_date FROM sf_expenses WHERE deleted_at IS NULL
    ) t
    WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
    ORDER BY month ASC
";
$monthly = $db->query($monthlySQL)->fetchAll();

// Event distribution for pie chart
$eventDist = array_map(fn($e) => [
    'name'     => $e['name'],
    'expenses' => (float)$e['total_expenses'],
    'color'    => $e['color'],
], array_filter($events, fn($e) => $e['total_expenses'] > 0));

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
require_once 'includes/layout.php';
?>

<!-- Stat Cards -->
<div class="stat-grid">
  <div class="stat-card balance animate-in">
    <div class="stat-label">Current Balance</div>
    <div class="stat-value <?= $summary['balance'] >= 0 ? 'blue' : 'negative' ?>"
         data-counter="<?= $summary['balance'] ?>">
      <?= formatCurrency($summary['balance']) ?>
    </div>
    <div class="stat-icon"><i class="ri-wallet-3-line"></i></div>
    <div class="stat-change <?= $summary['balance'] >= 0 ? 'up' : 'down' ?>">
      <i class="ri-arrow-<?= $summary['balance'] >= 0 ? 'up' : 'down' ?>-line"></i>
      <?= $summary['balance'] >= 0 ? 'Surplus' : 'Deficit' ?>
    </div>
  </div>

  <div class="stat-card income animate-in animate-in-delay-1">
    <div class="stat-label">Total Income</div>
    <div class="stat-value positive" data-counter="<?= $summary['total_income'] ?>">
      <?= formatCurrency($summary['total_income']) ?>
    </div>
    <div class="stat-icon"><i class="ri-arrow-up-circle-line"></i></div>
  </div>

  <div class="stat-card expense animate-in animate-in-delay-2">
    <div class="stat-label">Total Expenses</div>
    <div class="stat-value negative" data-counter="<?= $summary['total_expenses'] ?>">
      <?= formatCurrency($summary['total_expenses']) ?>
    </div>
    <div class="stat-icon"><i class="ri-arrow-down-circle-line"></i></div>
  </div>

  <div class="stat-card events animate-in animate-in-delay-3">
    <div class="stat-label">Active Events</div>
    <div class="stat-value" style="color:var(--purple-500)">
      <?= count(array_filter($events, fn($e) => $e['status'] === 'Active')) ?>
    </div>
    <div class="stat-icon"><i class="ri-calendar-event-line"></i></div>
  </div>
</div>

<!-- Charts + Recent Transactions -->
<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;margin-bottom:24px" class="chart-grid">

  <!-- Monthly Chart -->
  <div class="card animate-in animate-in-delay-1">
    <div class="card-header">
      <div>
        <div class="card-title">Monthly Overview</div>
        <div class="card-subtitle">Income vs Expenses — last 6 months</div>
      </div>
    </div>
    <div class="card-body">
      <div class="chart-container" style="height:220px">
        <canvas id="monthlyChart"></canvas>
      </div>
    </div>
  </div>

  <!-- Expense Distribution Pie -->
  <div class="card animate-in animate-in-delay-2">
    <div class="card-header">
      <div>
        <div class="card-title">Expense by Event</div>
        <div class="card-subtitle">Distribution</div>
      </div>
    </div>
    <div class="card-body">
      <div class="chart-container" style="height:180px">
        <canvas id="pieChart"></canvas>
      </div>
      <div style="margin-top:12px;display:flex;flex-direction:column;gap:6px">
        <?php foreach ($eventDist as $ed): ?>
        <div style="display:flex;align-items:center;gap:8px;font-size:12px">
          <span style="width:10px;height:10px;border-radius:50%;background:<?= htmlspecialchars($ed['color']) ?>;flex-shrink:0"></span>
          <span style="flex:1;color:var(--text-secondary)"><?= htmlspecialchars($ed['name']) ?></span>
          <span class="mono" style="color:var(--text-primary);font-weight:600"><?= formatCurrency($ed['expenses']) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<!-- Event Budget Utilization -->
<div class="card animate-in animate-in-delay-2" style="margin-bottom:24px">
  <div class="card-header">
    <div>
      <div class="card-title">Budget Utilization by Event</div>
      <div class="card-subtitle">Expenses vs Income per event</div>
    </div>
    <a href="events.php" class="btn btn-secondary btn-sm"><i class="ri-arrow-right-line"></i> View All</a>
  </div>
  <div class="card-body">
    <?php if (empty($events)): ?>
    <div class="empty-state"><i class="ri-calendar-2-line"></i><h3>No events yet</h3><p>Create your first event to get started.</p></div>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:16px">
      <?php foreach ($events as $ev): ?>
      <?php
        $utilization = $ev['total_income'] > 0 ? min(100, ($ev['total_expenses'] / $ev['total_income']) * 100) : 0;
        $statusClass = ['Planned'=>'badge-planned','Active'=>'badge-active','Completed'=>'badge-completed'][$ev['status']] ?? 'badge-planned';
      ?>
      <div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
          <span class="event-dot" style="background:<?= htmlspecialchars($ev['color']) ?>"></span>
          <span style="font-weight:600;font-size:13.5px;flex:1"><?= htmlspecialchars($ev['name']) ?></span>
          <span class="badge <?= $statusClass ?>"><?= $ev['status'] ?></span>
          <span class="mono text-sm" style="color:var(--text-secondary)"><?= round($utilization) ?>%</span>
        </div>
        <div class="progress-bar">
          <div class="progress-fill" style="width:<?= $utilization ?>%;background:<?= $ev['color'] ?>"></div>
        </div>
        <div style="display:flex;justify-content:space-between;margin-top:4px;font-size:11px;color:var(--text-secondary)">
          <span>Income: <strong class="amount-income"><?= formatCurrency($ev['total_income']) ?></strong></span>
          <span>Expenses: <strong class="amount-expense"><?= formatCurrency($ev['total_expenses']) ?></strong></span>
          <span>Balance: <strong style="color:<?= $ev['balance'] >= 0 ? 'var(--green-600)' : 'var(--red-500)' ?>"><?= formatCurrency($ev['balance']) ?></strong></span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Recent Transactions -->
<div class="card animate-in animate-in-delay-3">
  <div class="card-header">
    <div>
      <div class="card-title">Recent Transactions</div>
      <div class="card-subtitle">Latest financial activity</div>
    </div>
    <a href="transactions.php" class="btn btn-secondary btn-sm"><i class="ri-list-check"></i> View All</a>
  </div>
  <div class="card-body" style="padding:0">
    <?php if (empty($recent)): ?>
    <div class="empty-state"><i class="ri-receipt-line"></i><h3>No transactions yet</h3></div>
    <?php else: ?>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Type</th>
            <th>Description</th>
            <th>Event</th>
            <th>Date</th>
            <th class="text-right">Amount</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recent as $tx): ?>
          <tr>
            <td>
              <span class="badge <?= $tx['type'] === 'income' ? 'badge-income' : 'badge-expense' ?>">
                <i class="ri-arrow-<?= $tx['type'] === 'income' ? 'up' : 'down' ?>-line"></i>
                <?= ucfirst($tx['type']) ?>
              </span>
            </td>
            <td><?= htmlspecialchars($tx['description']) ?></td>
            <td>
              <div style="display:flex;align-items:center;gap:6px">
                <span class="event-dot" style="background:<?= htmlspecialchars($tx['color']) ?>"></span>
                <span class="text-sm"><?= htmlspecialchars($tx['event_name']) ?></span>
              </div>
            </td>
            <td class="text-sm text-muted"><?= formatDate($tx['transaction_date']) ?></td>
            <td class="text-right mono <?= $tx['type'] === 'income' ? 'amount-income' : 'amount-expense' ?>">
              <?= $tx['type'] === 'income' ? '+' : '-' ?><?= formatCurrency($tx['amount']) ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<style>
  @media(max-width:900px){.chart-grid{grid-template-columns:1fr!important}}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  const gridColor = isDark ? 'rgba(255,255,255,.06)' : 'rgba(0,0,0,.06)';
  const textColor = isDark ? '#94A3B8' : '#64748B';

  // Monthly Bar Chart
  const monthly = <?= json_encode($monthly) ?>;
  const mCtx = document.getElementById('monthlyChart')?.getContext('2d');
  if (mCtx && monthly.length) {
    new Chart(mCtx, {
      type: 'bar',
      data: {
        labels: monthly.map(m => {
          const [y,mo] = m.month.split('-');
          return new Date(y, mo-1).toLocaleString('default', {month:'short', year:'2-digit'});
        }),
        datasets: [
          { label: 'Income',   data: monthly.map(m=>m.income),   backgroundColor: '#22C55E', borderRadius: 5 },
          { label: 'Expenses', data: monthly.map(m=>m.expenses), backgroundColor: '#EF4444', borderRadius: 5 },
        ]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { labels: { color: textColor, font: { family: 'Plus Jakarta Sans', size: 11 }, boxWidth: 12 } } },
        scales: {
          x: { grid: { color: gridColor }, ticks: { color: textColor, font: { size: 11 } } },
          y: { grid: { color: gridColor }, ticks: { color: textColor, font: { size: 11 }, callback: v => '₱'+v.toLocaleString() } }
        }
      }
    });
  }

  // Pie Chart
  const dist = <?= json_encode(array_values($eventDist)) ?>;
  const pCtx = document.getElementById('pieChart')?.getContext('2d');
  if (pCtx && dist.length) {
    new Chart(pCtx, {
      type: 'doughnut',
      data: {
        labels: dist.map(d=>d.name),
        datasets: [{ data: dist.map(d=>d.expenses), backgroundColor: dist.map(d=>d.color), borderWidth: 2,
          borderColor: isDark ? '#1E293B' : '#fff' }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        cutout: '65%',
        plugins: { legend: { display: false } }
      }
    });
  }
});

// Animated counters
document.querySelectorAll('[data-counter]').forEach(el => {
  const target = parseFloat(el.getAttribute('data-counter') || 0);
  const start = performance.now();
  const duration = 1000;
  const sym = '₱';
  function update(now) {
    const p = Math.min((now - start) / duration, 1);
    const eased = 1 - Math.pow(1-p, 3);
    const val = target * eased;
    el.textContent = sym + Math.abs(val).toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
    if (p < 1) requestAnimationFrame(update);
  }
  requestAnimationFrame(update);
});
</script>

<?php

function formatCurrency($amount, $symbol = '₱') {
    return $symbol . number_format(abs((float)$amount), 2);
}

function formatDate($dateStr) {
    if (!$dateStr) return '—';
    return date('M j, Y', strtotime($dateStr));
}

require_once 'includes/layout_end.php';
?>
