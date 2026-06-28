<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_login();

$pdo  = db();
$user = current_user();
$msg  = '';
$err  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $eventId  = ($_POST['event_id'] ?? '') !== '' ? (int)$_POST['event_id'] : null;
        $source   = trim($_POST['source'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $amount   = (float)($_POST['amount'] ?? 0);
        $date     = $_POST['date'] ?? date('Y-m-d');
        $notes    = trim($_POST['notes'] ?? '');
        if ($source === '') { $err = 'Source is required.'; }
        elseif ($amount <= 0) { $err = 'Amount must be greater than 0.'; }
        else {
            $pdo->prepare("INSERT INTO finance_income (event_id,source,category,amount,date,notes) VALUES (?,?,?,?,?,?)")
                ->execute([$eventId, $source, $category, $amount, $date, $notes]);
            $msg = 'Income record added.';
        }
    }

    if ($action === 'edit') {
        $id       = (int)($_POST['id'] ?? 0);
        $eventId  = ($_POST['event_id'] ?? '') !== '' ? (int)$_POST['event_id'] : null;
        $source   = trim($_POST['source'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $amount   = (float)($_POST['amount'] ?? 0);
        $date     = $_POST['date'] ?? date('Y-m-d');
        $notes    = trim($_POST['notes'] ?? '');
        if ($id && $source !== '') {
            $pdo->prepare("UPDATE finance_income SET event_id=?,source=?,category=?,amount=?,date=?,notes=?,updated_at=NOW() WHERE id=?")
                ->execute([$eventId, $source, $category, $amount, $date, $notes, $id]);
            log_history('finance_income', $id, 'edit', '', $source, $user['username']);
            $msg = 'Income record updated.';
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $pdo->prepare("UPDATE finance_income SET is_deleted=1 WHERE id=?")->execute([$id]);
            log_history('finance_income', $id, 'soft_delete', '0', '1', $user['username']);
            $msg = 'Income record deleted (soft).';
        }
    }

    // Bulk spreadsheet save
    if ($action === 'bulk_save') {
        $ids     = $_POST['inc_id']     ?? [];
        $amounts = $_POST['inc_amount'] ?? [];
        $sources = $_POST['inc_source'] ?? [];
        $cats    = $_POST['inc_cat']    ?? [];
        $dates   = $_POST['inc_date']   ?? [];
        foreach ($ids as $k => $id) {
            $id = (int)$id;
            if (!$id) continue;
            $pdo->prepare("UPDATE finance_income SET source=?,category=?,amount=?,date=?,updated_at=NOW() WHERE id=?")
                ->execute([
                    trim($sources[$k] ?? ''),
                    trim($cats[$k]    ?? ''),
                    (float)($amounts[$k] ?? 0),
                    $dates[$k] ?? date('Y-m-d'),
                    $id
                ]);
        }
        $msg = 'Changes saved.';
    }
}

// Filters
$filterEvent = (int)($_GET['event_id'] ?? 0);
$filterCat   = trim($_GET['category'] ?? '');
$filterDate  = trim($_GET['date'] ?? '');
$filterText  = trim($_GET['q'] ?? '');

$where  = ['i.is_deleted = 0'];
$params = [];
if ($filterEvent) { $where[] = 'i.event_id = ?'; $params[] = $filterEvent; }
if ($filterCat)   { $where[] = 'i.category = ?'; $params[] = $filterCat; }
if ($filterDate)  { $where[] = 'i.date = ?';      $params[] = $filterDate; }
if ($filterText)  { $where[] = '(i.source LIKE ? OR i.notes LIKE ?)'; $params[] = "%$filterText%"; $params[] = "%$filterText%"; }

$whereStr = 'WHERE ' . implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT i.*, e.name AS event_name, e.color AS event_color
    FROM finance_income i
    LEFT JOIN finance_events e ON e.id = i.event_id
    {$whereStr}
    ORDER BY i.date DESC, i.id DESC
");
$stmt->execute($params);
$incomes = $stmt->fetchAll();
$total   = array_sum(array_column($incomes, 'amount'));

// Group by event for spreadsheet view
$grouped = [];
foreach ($incomes as $row) {
    $key = $row['event_name'] ?? 'Uncategorized';
    $grouped[$key][] = $row;
}

$eventsAll  = $pdo->query("SELECT id, name, color FROM finance_events WHERE is_archived=0 ORDER BY name")->fetchAll();
$categories = $pdo->query("SELECT DISTINCT category FROM finance_income WHERE category IS NOT NULL AND category != '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

$viewMode = $_GET['view'] ?? 'table';

layout_head('Income');
layout_sidebar('income');
?>

<?php if ($msg): ?><div class="alert alert-success">✅ <?= h($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger">⚠️ <?= h($err) ?></div><?php endif; ?>

<!-- Filter bar -->
<form method="GET" class="filter-bar">
  <input type="hidden" name="view" value="<?= h($viewMode) ?>">
  <input class="form-control" name="q" placeholder="🔍 Search…" value="<?= h($filterText) ?>">
  <select class="form-control" name="event_id" data-autosubmit>
    <option value="">All Events</option>
    <?php foreach ($eventsAll as $ev): ?>
      <option value="<?= $ev['id'] ?>" <?= $filterEvent==$ev['id']?'selected':'' ?>><?= h($ev['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <?php if (!empty($categories)): ?>
  <select class="form-control" name="category" data-autosubmit>
    <option value="">All Categories</option>
    <?php foreach ($categories as $cat): ?>
      <option value="<?= h($cat) ?>" <?= $filterCat===$cat?'selected':'' ?>><?= h($cat) ?></option>
    <?php endforeach; ?>
  </select>
  <?php endif; ?>
  <input class="form-control" type="date" name="date" value="<?= h($filterDate) ?>">
  <button class="btn btn-primary" type="submit">Filter</button>
  <a class="btn btn-secondary" href="?view=<?= h($viewMode) ?>">Clear</a>
</form>

<!-- Action bar -->
<div class="flex justify-between items-center mb-16" style="margin-bottom:16px;flex-wrap:wrap;gap:8px;">
  <div class="flex gap-8 items-center">
    <span style="font-size:13px;color:var(--text-muted);">
      <?= count($incomes) ?> record(s) &nbsp;·&nbsp; Total: <strong style="color:var(--success);"><?= format_money($total) ?></strong>
    </span>
    <a href="?view=table<?= $filterEvent?"&event_id=$filterEvent":'' ?>" class="btn btn-sm <?= $viewMode==='table'?'btn-primary':'btn-secondary' ?>">Table</a>
    <a href="?view=sheet<?= $filterEvent?"&event_id=$filterEvent":'' ?>" class="btn btn-sm <?= $viewMode==='sheet'?'btn-primary':'btn-secondary' ?>">Spreadsheet</a>
  </div>
  <button class="btn btn-primary" onclick="openModal('modalCreate')">+ Add Income</button>
</div>

<?php if ($viewMode === 'sheet'): ?>
<!-- ── Spreadsheet view ── -->
<form method="POST">
  <input type="hidden" name="action" value="bulk_save">
  <div class="card">
    <div class="card-header">
      <span class="card-title">Spreadsheet Editor</span>
      <button type="submit" class="btn btn-primary btn-sm">💾 Save All</button>
    </div>
    <?php if (empty($incomes)): ?>
      <div class="empty-state"><div class="empty-icon">💰</div><h3>No income records</h3></div>
    <?php else: ?>
    <div class="spreadsheet-wrap">
      <table class="spreadsheet">
        <thead><tr>
          <th style="min-width:90px;">Date</th>
          <th style="min-width:160px;">Source</th>
          <th style="min-width:120px;">Category</th>
          <th style="min-width:100px;">Event</th>
          <th style="min-width:110px;text-align:right;">Amount (₱)</th>
          <th style="min-width:60px;"></th>
        </tr></thead>
        <tbody>
          <?php
          $currentEvent = null;
          $eventTotal   = 0;
          $eventRows    = [];

          foreach ($grouped as $evName => $rows):
              $subtotal = array_sum(array_column($rows, 'amount'));
          ?>
          <tr class="section-header">
            <td colspan="4"><?= h($evName) ?></td>
            <td style="text-align:right;" class="ss-subtotal"><?= format_money($subtotal) ?></td>
            <td></td>
          </tr>
          <?php foreach ($rows as $row): ?>
          <tr>
            <td><input type="date" name="inc_date[]" value="<?= h($row['date']) ?>">
                <input type="hidden" name="inc_id[]" value="<?= $row['id'] ?>"></td>
            <td><input type="text" name="inc_source[]" value="<?= h($row['source']) ?>" placeholder="Source"></td>
            <td><input type="text" name="inc_cat[]" value="<?= h($row['category']) ?>" placeholder="Category"></td>
            <td style="padding:7px 10px;font-size:12px;color:var(--text-muted);"><?= h($row['event_name'] ?? '—') ?></td>
            <td><input type="number" name="inc_amount[]" class="ss-amount" value="<?= $row['amount'] ?>" step="0.01" style="text-align:right;"></td>
            <td style="padding:4px;">
              <form method="POST" id="sdel<?= $row['id'] ?>" style="display:inline;">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                <button type="button" class="btn btn-danger btn-sm btn-icon" onclick="confirmDelete('sdel<?= $row['id'] ?>')">🗑</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr style="background:var(--blue-50);">
            <td colspan="4" style="padding:10px 14px;font-weight:700;font-size:13px;color:var(--blue-700);">Grand Total</td>
            <td style="text-align:right;padding:10px 14px;font-weight:700;font-family:'DM Mono',monospace;color:var(--success);" id="ss-grand-total"><?= format_money($total) ?></td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>
    <?php endif; ?>
  </div>
</form>

<?php else: ?>
<!-- ── Table view ── -->
<div class="table-wrap">
  <?php if (empty($incomes)): ?>
    <div class="empty-state"><div class="empty-icon">💰</div><h3>No income records found</h3><p>Adjust your filters or add a new income entry.</p></div>
  <?php else: ?>
  <table class="data-table" id="incTable">
    <thead><tr>
      <th>Date</th><th>Source</th><th>Category</th><th>Event</th><th>Notes</th><th class="text-right">Amount</th><th>Actions</th>
    </tr></thead>
    <tbody>
      <?php foreach ($incomes as $row): ?>
      <tr>
        <td class="mono"><?= h($row['date']) ?></td>
        <td style="font-weight:500;"><?= h($row['source']) ?></td>
        <td><?= $row['category'] ? '<span class="badge badge-blue">'.h($row['category']).'</span>' : '<span class="text-muted">—</span>' ?></td>
        <td>
          <?php if ($row['event_name']): ?>
            <span class="badge" style="background:<?= h($row['event_color']) ?>22;color:<?= h($row['event_color']) ?>;">
              <span class="event-dot" style="background:<?= h($row['event_color']) ?>;"></span>
              <?= h($row['event_name']) ?>
            </span>
          <?php else: echo '<span class="text-muted">—</span>'; endif; ?>
        </td>
        <td style="font-size:12px;color:var(--text-muted);"><?= h($row['notes']) ?></td>
        <td class="amount-in text-right"><?= format_money($row['amount']) ?></td>
        <td>
          <div class="flex gap-8">
            <button class="btn btn-secondary btn-sm btn-icon" onclick="openModal('modalEditInc<?= $row['id'] ?>')">✏️</button>
            <form method="POST" id="idel<?= $row['id'] ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $row['id'] ?>">
              <button type="button" class="btn btn-danger btn-sm btn-icon" onclick="confirmDelete('idel<?= $row['id'] ?>')">🗑</button>
            </form>
          </div>
        </td>
      </tr>

      <!-- Edit modal -->
      <div class="modal-backdrop" id="modalEditInc<?= $row['id'] ?>">
        <div class="modal">
          <div class="modal-header">
            <span class="modal-title">Edit Income</span>
            <button class="btn btn-secondary btn-sm btn-icon" onclick="closeModal('modalEditInc<?= $row['id'] ?>')">✕</button>
          </div>
          <form method="POST">
            <div class="modal-body">
              <input type="hidden" name="action" value="edit">
              <input type="hidden" name="id" value="<?= $row['id'] ?>">
              <div class="form-grid">
                <div class="form-group full">
                  <label class="form-label">Source *</label>
                  <input class="form-control" name="source" value="<?= h($row['source']) ?>" required>
                </div>
                <div class="form-group">
                  <label class="form-label">Category</label>
                  <input class="form-control" name="category" value="<?= h($row['category']) ?>" list="cat-list-<?= $row['id'] ?>" placeholder="e.g. Donations">
                  <datalist id="cat-list-<?= $row['id'] ?>">
                    <?php foreach ($categories as $cat): ?><option value="<?= h($cat) ?>"><?php endforeach; ?>
                  </datalist>
                </div>
                <div class="form-group">
                  <label class="form-label">Event</label>
                  <select class="form-control" name="event_id">
                    <option value="">— None —</option>
                    <?php foreach ($eventsAll as $ev): ?>
                    <option value="<?= $ev['id'] ?>" <?= $row['event_id']==$ev['id']?'selected':'' ?>><?= h($ev['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Date</label>
                  <input class="form-control" type="date" name="date" value="<?= h($row['date']) ?>" required>
                </div>
                <div class="form-group">
                  <label class="form-label">Amount (₱)</label>
                  <input class="form-control" type="number" name="amount" value="<?= $row['amount'] ?>" step="0.01" min="0" required>
                </div>
                <div class="form-group full">
                  <label class="form-label">Notes</label>
                  <textarea class="form-control" name="notes" rows="2"><?= h($row['notes']) ?></textarea>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" onclick="closeModal('modalEditInc<?= $row['id'] ?>')">Cancel</button>
              <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr style="background:var(--surface-2);">
        <td colspan="5" style="padding:10px 14px;font-weight:600;font-size:13px;">Total</td>
        <td class="amount-in text-right" style="padding:10px 14px;font-size:15px;"><?= format_money($total) ?></td>
        <td></td>
      </tr>
    </tfoot>
  </table>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- Create modal -->
<div class="modal-backdrop" id="modalCreate">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Add Income</span>
      <button class="btn btn-secondary btn-sm btn-icon" onclick="closeModal('modalCreate')">✕</button>
    </div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="action" value="create">
        <div class="form-grid">
          <div class="form-group full">
            <label class="form-label">Source *</label>
            <input class="form-control" name="source" placeholder="e.g. Student Collection" required autofocus>
          </div>
          <div class="form-group">
            <label class="form-label">Category</label>
            <input class="form-control" name="category" placeholder="e.g. Donations" list="cat-list-new">
            <datalist id="cat-list-new">
              <?php foreach ($categories as $cat): ?><option value="<?= h($cat) ?>"><?php endforeach; ?>
            </datalist>
          </div>
          <div class="form-group">
            <label class="form-label">Event</label>
            <select class="form-control" name="event_id">
              <option value="">— None —</option>
              <?php foreach ($eventsAll as $ev): ?>
              <option value="<?= $ev['id'] ?>"><?= h($ev['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Date</label>
            <input class="form-control" type="date" name="date" data-today required>
          </div>
          <div class="form-group">
            <label class="form-label">Amount (₱) *</label>
            <input class="form-control" type="number" name="amount" step="0.01" min="0.01" placeholder="0.00" required>
          </div>
          <div class="form-group full">
            <label class="form-label">Notes</label>
            <textarea class="form-control" name="notes" rows="2" placeholder="Optional details…"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modalCreate')">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Income</button>
      </div>
    </form>
  </div>
</div>

<?php layout_foot(); ?>
